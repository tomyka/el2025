<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Game;
use App\Models\GameOdds;
use App\Models\Group;
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
            ->whereIn('point_results.game_id', $gameIDs)
            ->select(
                'point_results.id',
                'point_results.user_id',
                'point_results.game_id',
                'point_results.winner_points',
                'prediction_results.generated'
            )
            ->get();

        // Build lookup: user_id → game_id → {id, correct}
        $lookup  = [];
        $userIDs = [];
        foreach ($rows as $row) {
            $correct = $row->winner_points > 0 && !$row->generated;
            $lookup[$row->user_id][$row->game_id] = ['id' => $row->id, 'correct' => $correct];
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
                $updates[$entry['id']] = $streak;
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

    public function getUserProfilePoints(int $userID): array
    {
        return PointResult::where('user_id', $userID)
            ->select('winner_points', 'difference_points', 'bingo_points', 'odds_points', 'full_points', 'streak_bonus')
            ->get()
            ->map(fn ($r) => [
                'full_points'   => round($r->full_points, 1),
                'streak_bonus'  => round($r->streak_bonus ?? 0, 1),
                'bingo_points'  => $r->bingo_points != 0 ? 1 : 0,
            ])
            ->all();
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

        foreach (Group::all() as $group) {
            $predictionResults = DB::table('prediction_results')
                ->join('user_groups', 'prediction_results.user_id', '=', 'user_groups.user_id')
                ->where('user_groups.group_id', '=', $group->id)
                ->where('prediction_results.game_id', '=', $gameID)
                ->get();

            foreach ($predictionResults as $predictionResult) {
                $tablePoints  = $this->scoring->getTablePoints($homeTeamScore, $awayTeamScore, $predictionResult->home_team_score, $predictionResult->away_team_score, $pointsLookup);
                $winnerDir    = $this->scoring->getWinnerPoints($homeTeamScore, $awayTeamScore, $predictionResult->home_team_score, $predictionResult->away_team_score);
                $winnerBonus  = $winnerDir > 0 ? 5.0 : 0.0;
                $bingoPoints  = $this->scoring->getBingoPoints($homeTeamScore, $awayTeamScore, (int) $predictionResult->home_team_score, (int) $predictionResult->away_team_score);
                $odds         = $this->scoring->getGameOdds($predictionResult->home_team_score, $predictionResult->away_team_score, $gameOdds, $predictionResult->generated);
                $oddsPoints   = $this->scoring->getOddsPoints($odds, $winnerDir);
                $points       = $this->scoring->calculateGamePoints($winnerBonus, $tablePoints, $oddsPoints, $bingoPoints, $odds, $event->rate);

                $this->insertPointResultUser($predictionResult->user_id, $gameID, $points);
            }
        }
    }
}
