<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Game;
use App\Models\GameOdds;
use App\Models\PointResult;
use App\Models\PointsCalculation;
use App\Services\ScoringService;
use Illuminate\Support\Facades\DB;

class PointResultController extends Controller
{
    public function __construct(private readonly ScoringService $scoring) {}

    public function deletePointResultGamePoints(int $gameID): void
    {
        DB::statement('DELETE FROM point_results WHERE game_id = ?', [$gameID]);
    }

    public function insertPointResultUser(int $userID, int $gameID, object $points): void
    {
        DB::table('point_results')->updateOrInsert(
            ['user_id' => $userID, 'game_id' => $gameID],
            [
                'winner_points'     => $points->winnerPoints,
                'difference_points' => $points->differencePoints,
                'bingo_points'      => $points->bingoPoints,
                'odds'              => $points->odds,
                'odds_points'       => $points->oddsPoints,
                'full_points'       => $points->fullPoints,
                'streak_bonus'      => 0,
                'updated_at'        => now(),
            ]
        );
    }

    public function recalculateStreaks(): void
    {
        $gameIDs = DB::table('games')
            ->whereNotNull('home_team_score')
            ->whereNotNull('away_team_score')
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        if (empty($gameIDs)) return;

        // Load all point_results for scored games, joined with prediction_results
        // to determine whether each prediction was genuine (not auto-generated)
        $rows = DB::table('point_results')
            ->leftJoin('prediction_results', function ($join) {
                $join->on('point_results.user_id', '=', 'prediction_results.user_id')
                     ->on('point_results.game_id', '=', 'prediction_results.game_id');
            })
            ->join('games', 'point_results.game_id', '=', 'games.id')
            ->join('events', 'games.event_id', '=', 'events.id')
            ->whereIn('point_results.game_id', $gameIDs)
            ->select(
                'point_results.id',
                'point_results.user_id',
                'point_results.game_id',
                'point_results.winner_points',
                'prediction_results.generated',
                'events.rate'
            )
            ->get();

        // Build lookup: user_id → game_id → {id, correct, rate}
        $lookup  = [];
        $userIDs = [];
        foreach ($rows as $row) {
            $correct = $row->winner_points > 0 && !$row->generated;
            $lookup[$row->user_id][$row->game_id] = ['id' => $row->id, 'correct' => $correct, 'rate' => (float) $row->rate];
            $userIDs[$row->user_id] = true;
        }

        // Walk games in ID order per user, accumulate streak
        $updates = [];
        foreach (array_keys($userIDs) as $userID) {
            $streak = 0;
            foreach ($gameIDs as $gameID) {
                if (!isset($lookup[$userID][$gameID])) {
                    $streak = 0;
                    continue;
                }
                $entry  = $lookup[$userID][$gameID];
                $streak = $entry['correct'] ? $streak + 1 : 0;
                $updates[$entry['id']] = max(0, $streak - 1) * $entry['rate'];
            }
        }

        if (empty($updates)) return;

        // Single batch update via CASE WHEN
        $cases  = '';
        $ids    = [];
        foreach ($updates as $id => $bonus) {
            $cases .= " WHEN {$id} THEN {$bonus}";
            $ids[]  = $id;
        }
        $idList = implode(',', $ids);
        DB::statement("UPDATE point_results SET streak_bonus = CASE id {$cases} END WHERE id IN ({$idList})");
    }

    public function getUserProfilePoints(int $userID, ?int $leagueId = null): array
    {
        $rows = DB::table('point_results as pr')
            ->join('games as g', 'pr.game_id', '=', 'g.id')
            ->join('events as e', 'g.event_id', '=', 'e.id')
            ->join('teams as ht', 'g.home_team_id', '=', 'ht.id')
            ->join('teams as at', 'g.away_team_id', '=', 'at.id')
            ->where('pr.user_id', $userID)
            ->select(
                'pr.game_id',
                'pr.winner_points',
                'pr.difference_points',
                'pr.bingo_points',
                'pr.odds_points',
                'pr.full_points',
                'pr.streak_bonus',
                'g.home_team_score',
                'g.away_team_score',
                'ht.team as home_team',
                'at.team as away_team',
                'g.game_date',
                'e.rate'
            )
            ->get();

        $useLeagueOdds = false;
        if ($leagueId !== null) {
            $league = \App\Models\League::find($leagueId);
            if ($league && $league->use_league_odds) {
                $memberCount = \App\Models\LeagueMember::where('league_id', $leagueId)
                    ->where('is_guest', false)
                    ->count();
                $useLeagueOdds = $memberCount >= 20;
            }
        }

        $leagueOddsMap = [];
        if ($useLeagueOdds) {
            $gameIds = $rows->pluck('game_id');
            $leagueOddsMap = DB::table('league_game_odds')
                ->where('league_id', $leagueId)
                ->whereIn('game_id', $gameIds)
                ->get()
                ->keyBy('game_id');
        }

        $profile = [];
        foreach ($rows as $row) {
            $oddsPointsLeague = (float) $row->odds_points;
            $fullPointsLeague = (float) $row->full_points;

            if ($useLeagueOdds && isset($leagueOddsMap[$row->game_id])) {
                $lo = $leagueOddsMap[$row->game_id];

                if ($row->home_team_score !== null && $row->away_team_score !== null) {
                    if ($row->home_team_score > $row->away_team_score) {
                        $leagueOddsRate = (float) $lo->home_odds;
                    } elseif ($row->home_team_score == $row->away_team_score) {
                        $leagueOddsRate = (float) $lo->draw_odds;
                    } else {
                        $leagueOddsRate = (float) $lo->away_odds;
                    }

                    $oddsPointsLeague = $row->winner_points > 0
                        ? round((float) $row->winner_points * $leagueOddsRate, 1)
                        : 0.0;

                    $fullPointsLeague = round(
                        (float) $row->winner_points
                        + (float) $row->difference_points
                        + (float) $row->bingo_points
                        + $oddsPointsLeague,
                        1
                    );
                }
            }

            $profile[] = [
                'game_id'            => $row->game_id,
                'home_team'          => $row->home_team,
                'away_team'          => $row->away_team,
                'game_date'          => $row->game_date,
                'winner_points'      => $row->winner_points,
                'difference_points'  => $row->difference_points,
                'bingo_points'       => $row->bingo_points != 0 ? 1 : 0,
                'odds_points'        => $row->odds_points,
                'odds_points_league' => $oddsPointsLeague,
                'full_points'        => round((float) $row->full_points, 1),
                'full_points_league' => $fullPointsLeague,
                'streak_bonus'       => round((float) ($row->streak_bonus ?? 0), 1),
                'rate'               => $row->rate,
            ];
        }

        return $profile;
    }

    public function updateGamePoints(int $gameID): void
    {
        $game  = Game::where('id', $gameID)->firstOrFail();
        $event = Event::where('id', $game->event_id)->firstOrFail();

        $homeTeamScore = $game->home_team_score;
        $awayTeamScore = $game->away_team_score;

        $this->deletePointResultGamePoints($gameID);

        // Preload all 66 lookup rows once — keyed by "{homeDiff}_{awayDiff}"
        $pointsLookup = PointsCalculation::all()
            ->keyBy(fn($r) => "{$r->home_score_difference}_{$r->away_score_difference}");

        $gameOdds = GameOdds::where('game_id', $gameID)->first() ?? tap(new GameOdds(), function ($o) {
            $o->home_odds = 1.0;
            $o->draw_odds = 1.0;
            $o->away_odds = 1.0;
        });

        $predictionResults = DB::table('prediction_results')
            ->where('game_id', $gameID)
            ->get();

        foreach ($predictionResults as $predictionResult) {
            $tablePoints  = $this->scoring->getTablePoints($homeTeamScore, $awayTeamScore, $predictionResult->home_team_score, $predictionResult->away_team_score, $pointsLookup);
            $winnerDir    = $this->scoring->getWinnerPoints($homeTeamScore, $awayTeamScore, $predictionResult->home_team_score, $predictionResult->away_team_score);
            $winnerBonus  = $winnerDir > 0 ? 5.0 : 0.0;
            $bingoPoints  = $this->scoring->getBingoPoints($homeTeamScore, $awayTeamScore, (int) $predictionResult->home_team_score, (int) $predictionResult->away_team_score);
            $odds         = $this->scoring->getGameOdds($predictionResult->home_team_score, $predictionResult->away_team_score, $gameOdds, $predictionResult->generated);
            $oddsPoints   = $this->scoring->getOddsPoints($odds, $winnerBonus);
            $points       = $this->scoring->calculateGamePoints($winnerBonus, $tablePoints, $oddsPoints, $bingoPoints, $odds, $event->rate);

            $this->insertPointResultUser($predictionResult->user_id, $gameID, $points);
        }
    }
}
