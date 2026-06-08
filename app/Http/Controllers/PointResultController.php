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
        $pointResult                     = new PointResult();
        $pointResult->user_id            = $userID;
        $pointResult->game_id            = $gameID;
        $pointResult->odds               = $points->odds;
        $pointResult->winner_points      = $points->winnerPoints;
        $pointResult->difference_points  = $points->differencePoints;
        $pointResult->bingo_points       = $points->bingoPoints;
        $pointResult->odds_points        = $points->oddsPoints;
        $pointResult->full_points        = $points->fullPoints;
        $pointResult->save();
    }

    public function getUserProfilePoints(int $userID): array
    {
        return PointResult::where('user_id', $userID)
            ->select('winner_points', 'difference_points', 'bingo_points', 'odds_points', 'full_points')
            ->get()
            ->map(fn ($r) => [
                'full_points'  => round($r->full_points, 1),
                'bingo_points' => $r->bingo_points != 0 ? 1 : 0,
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
