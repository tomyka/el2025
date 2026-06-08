<?php

namespace App\Http\Controllers;

use App\Models\GameOdds;
use App\Models\PredictionResult;

class GameOddsController extends Controller
{

    public function updateGameOdds($gameID): void
    {
        $calculated = $this->calculateGameOdds($gameID);

        $gameOdd            = GameOdds::firstOrNew(['game_id' => $gameID]);
        $gameOdd->game_id   = $gameID;
        $gameOdd->home_odds = $calculated->homeOdds;
        $gameOdd->draw_odds = $calculated->drawOdds;
        $gameOdd->away_odds = $calculated->awayOdds;
        $gameOdd->save();
    }

    private function calculateGameOdds($gameID): object
    {
        $predictionResults      = PredictionResult::where('game_id', $gameID)->where('generated', 0)->get();
        $predictionResultCCount = count($predictionResults);

        if ($predictionResultCCount === 0) {
            return (object) ['homeOdds' => 1.0, 'drawOdds' => 1.0, 'awayOdds' => 1.0];
        }

        $homeOddsCount = 0;
        $drawOddsCount = 0;
        $awayOddsCount = 0;

        foreach ($predictionResults as $predictionResult) {
            $homeOddsCount += $this->calculateHomeOdds($predictionResult->home_team_score, $predictionResult->away_team_score);
            $drawOddsCount += $this->calculateDrawOdds($predictionResult->home_team_score, $predictionResult->away_team_score);
            $awayOddsCount += $this->calculateAwayOdds($predictionResult->home_team_score, $predictionResult->away_team_score);
        }

        return (object) [
            'homeOdds' => 2 - $homeOddsCount / $predictionResultCCount,
            'drawOdds' => 2 - $drawOddsCount / $predictionResultCCount,
            'awayOdds' => 2 - $awayOddsCount / $predictionResultCCount,
        ];
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
