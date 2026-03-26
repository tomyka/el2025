<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Game;
use App\Models\GameOdds;
use App\Models\Group;
use App\Models\PointResult;
use Illuminate\Support\Facades\DB;
use stdClass;

class PointResultController extends Controller
{
    public function deletePointResultGamePoints ($gameID): void
    {
        DB::statement('DELETE FROM point_results WHERE game_id='.$gameID);
    }

    public function insertPointResultUser ($userID, $gameID, $points): void
    {
        $PointResult = new PointResult();
        $PointResult->user_id = $userID;
        $PointResult->game_id = $gameID;
        $PointResult->odds = $points->odds;
        $PointResult->winner_points = $points->winnerPoints;
        $PointResult->difference_points = $points->differencePoints;
        $PointResult->bingo_points = $points->bingoPoints;
        $PointResult->odds_points = $points->oddsPoints;
        $PointResult->full_points = $points->fullPoints;
        $PointResult->save();
    }

    public function getUserProfilePoints($userID): array
    {
        $pointResult=[];
        $pointResultArray = PointResult::where('user_id','=',$userID)->select('winner_points','difference_points','bingo_points','odds_points','full_points')->get();
        foreach ($pointResultArray as $pointResultValue) {
            $pointResult[] = [
                'full_points'    => round($pointResultValue->full_points,0),
                'bingo_points'        => (($pointResultValue->bingo_points!=0)?1:0)
            ] ;
        }
        return $pointResult;
    }

    public function updateGamePoints($gameID): void
    {
        $groups = Group::all();
        $game = Game::where('id',$gameID)->firstOrFail();
        $event = Event::where('id',$game->event_id)->firstOrFail();

        $homeTeamScore = $game->home_team_score;
        $awayTeamScore = $game->away_team_score;

        $PointResultController = new PointResultController();
        $PointResultController->deletePointResultGamePoints($gameID);

        foreach ($groups as $group){
            $predictionResults = DB::table('prediction_results')->join('user_groups','prediction_results.user_id','=','user_groups.user_id')->where('user_groups.group_id','=',$group->id)->where('prediction_results.game_id','=',$gameID)->get();
            $gameOdds = GameOdds::where('game_id',$gameID)->firstOrFail();

            foreach ($predictionResults as $predictionResult){
                $odds = $this->getGameOdds($predictionResult->home_team_score,$predictionResult->away_team_score, $gameOdds, $predictionResult->generated);
                $winnerPoints = $this->getWinnerPoints($homeTeamScore,$awayTeamScore, $predictionResult->home_team_score,$predictionResult->away_team_score);
                $oddsPoints = $this->getOddsPoints($odds, $winnerPoints);
                $differencePoints = $this->getDifferencePoints($homeTeamScore,$awayTeamScore,$predictionResult->home_team_score,$predictionResult->away_team_score);
                $bingoPoints = $this->getBingoPoints($homeTeamScore,$awayTeamScore,$predictionResult->home_team_score,$predictionResult->away_team_score);
                $points = $this->calculateGamePoints($winnerPoints, $differencePoints, $oddsPoints, $bingoPoints, $odds, $event->rate);
                $PointResultController->insertPointResultUser($predictionResult->user_id, $gameID, $points);
            }
        }
    }

    public function calculateGamePoints($winnerPoints, $differencePoints, $oddsPoints, $bingoPoints, $odds, $rate): stdClass
    {

        $points = new stdClass();
        $points->winnerPoints = $winnerPoints * $rate;
        $points->differencePoints = $differencePoints *$rate;
        $points->bingoPoints = $bingoPoints * $rate;
        $points->oddsPoints = $oddsPoints * $rate;
        $points->odds = $odds;
        $points->fullPoints = $points->winnerPoints + $points->differencePoints + $points->bingoPoints + $points->oddsPoints;

        return $points;
    }

    public function getWinnerPoints($homeScore, $awayScore, $homeScorePrediction, $awayScorePrediction): int
    {
        if ($homeScore>$awayScore && $homeScorePrediction>$awayScorePrediction || $homeScore<$awayScore && $homeScorePrediction<$awayScorePrediction) {
            $winnerPoints = 50;
        }
        else {
            $winnerPoints = 0;
        }
        return $winnerPoints;
    }

    public function getGameOdds($homeScorePrediction, $awayScorePrediction, $gameOdds, $generated){
        if ($generated == 1) {
            $odds = 1;
        } else {
            if ($homeScorePrediction>$awayScorePrediction) {
                $odds = $gameOdds->home_odds;
            } else {
                $odds = $gameOdds->away_odds;
            }
        }
        return $odds;
    }

    public function getDifferencePoints ($homeScore, $awayScore, $homeScorePrediction, $awayScorePrediction): int
    {
        return 50 - abs(($homeScore-$awayScore)-($homeScorePrediction-$awayScorePrediction));
    }

    public function getOddsPoints($odds, $winnerPoints): float|int
    {
        if ($winnerPoints==50) {
            $oddsPoints = $winnerPoints * ($odds - 1);
        }
        else {
            $oddsPoints = 0;
        }
        return $oddsPoints;
    }

    public function getBingoPoints($homeScore, $awayScore, $homeScorePrediction, $awayScorePrediction): int
    {
        if (($homeScore == $homeScorePrediction) && ($awayScore == $awayScorePrediction)) {
            $bingoPoints = 50;
        }
        elseif (($homeScore-$homeScorePrediction) == ($awayScore-$awayScorePrediction)){
            $bingoPoints = 20;
        }
        else {
            $bingoPoints = 0;
        }
        return $bingoPoints;
    }
}
