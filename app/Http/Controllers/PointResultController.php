<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Game;
use App\Models\GameOdds;
use App\Models\Group;
use App\Models\PointResult;
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
                'full_points'  => round($r->full_points, 0),
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

        foreach (Group::all() as $group) {
            $predictionResults = DB::table('prediction_results')
                ->join('user_groups', 'prediction_results.user_id', '=', 'user_groups.user_id')
                ->where('user_groups.group_id', '=', $group->id)
                ->where('prediction_results.game_id', '=', $gameID)
                ->get();

            $gameOdds = GameOdds::where('game_id', $gameID)->firstOrFail();

            foreach ($predictionResults as $predictionResult) {
                $odds             = $this->scoring->getGameOdds($predictionResult->home_team_score, $predictionResult->away_team_score, $gameOdds, $predictionResult->generated);
                $winnerPoints     = $this->scoring->getWinnerPoints($homeTeamScore, $awayTeamScore, $predictionResult->home_team_score, $predictionResult->away_team_score);
                $differencePoints = $this->scoring->getDifferencePoints($homeTeamScore, $awayTeamScore, $predictionResult->home_team_score, $predictionResult->away_team_score);
                $bingoPoints      = $this->scoring->getBingoPoints($homeTeamScore, $awayTeamScore, $predictionResult->home_team_score, $predictionResult->away_team_score);
                $oddsPoints       = $this->scoring->getOddsPoints($odds, $winnerPoints);
                $points           = $this->scoring->calculateGamePoints($winnerPoints, $differencePoints, $oddsPoints, $bingoPoints, $odds, $event->rate);

                $this->insertPointResultUser($predictionResult->user_id, $gameID, $points);
            }
        }
    }
}
