<?php

namespace App\Http\Controllers;

use App\Models\GameOdds;
use App\Models\PredictionResult;

class GameOddsController extends Controller
{

    public function updateGameOdds ($gameID){
        $gameOdds = GameOdds::where('game_id',$gameID)->get();

        foreach ($gameOdds as $gameOdd) {
            $gameOdd->home_odds = $this->calculateGameOdds($gameID)->homeOdds;
            $gameOdd->draw_odds = $this->calculateGameOdds($gameID)->drawOdds;
            $gameOdd->away_odds = $this->calculateGameOdds($gameID)->awayOdds;
            $gameOdd->save();
        }
    }

    private function calculateGameOdds($gameID){
        $predictionResults = PredictionResult::where('game_id',$gameID)->where('generated',0)->get();
        $predictionResultCCount = count($predictionResults);
        $homeOddsCount = 0;
        $drawOddsCount = 0;
        $awayOddsCount = 0;

        foreach ($predictionResults as $predictionResult){
            $homeOddsCount += $this->calculateHomeOdds($predictionResult->home_team_score,$predictionResult->away_team_score);
            $drawOddsCount += $this->calculateDrawOdds($predictionResult->home_team_score,$predictionResult->away_team_score);
            $awayOddsCount += $this->calculateAwayOdds($predictionResult->home_team_score,$predictionResult->away_team_score);
        }

        $gameOdds = (object)
            [
                'homeOdds' => 2-$homeOddsCount/$predictionResultCCount,
                'drawOdds' => 2-$drawOddsCount/$predictionResultCCount,
                'awayOdds' => 2-$awayOddsCount/$predictionResultCCount
            ];

        return $gameOdds;
    }

    private function calculateHomeOdds ($homeScore,$awayScore) {
            if ($homeScore > $awayScore) {
                return 1;
            }
            else {
                return 0;
            }
    }
    private function calculateDrawOdds ($homeScore,$awayScore) {
        if ($homeScore == $awayScore) {
            return 1;
        }
        else {
            return 0;
        }
    }

    private function calculateAwayOdds ($homeScore, $awayScore) {
        if ($homeScore < $awayScore) {
            return 1;
        }
        else {
            return 0;
        }
    }
}
