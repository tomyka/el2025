<?php

namespace App\Http\Controllers;

use App\Models\GameOdds;
use App\Models\PredictionResult;
use Illuminate\Support\Facades\DB;

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

        // Per-league odds for opt-in leagues with >= 20 non-guest members
        $optInLeagues = \App\Models\League::where('use_league_odds', true)->get();

        foreach ($optInLeagues as $league) {
            $memberCount = \App\Models\LeagueMember::where('league_id', $league->id)
                ->where('is_guest', false)
                ->count();

            if ($memberCount < 20) {
                continue;
            }

            $memberIds = \App\Models\LeagueMember::where('league_id', $league->id)->pluck('user_id');

            $leaguePredictions = DB::table('prediction_results')
                ->where('game_id', $gameID)
                ->whereIn('user_id', $memberIds)
                ->where('generated', 0)
                ->select('home_team_score', 'away_team_score')
                ->get();

            $total = $leaguePredictions->count();
            if ($total === 0) {
                continue;
            }

            $homeWins = $leaguePredictions->filter(fn($p) => $p->home_team_score > $p->away_team_score)->count();
            $draws    = $leaguePredictions->filter(fn($p) => $p->home_team_score == $p->away_team_score)->count();
            $awayWins = $leaguePredictions->filter(fn($p) => $p->home_team_score < $p->away_team_score)->count();

            $homeOdds = round(max(1.01, 2 - ($homeWins / $total)), 2);
            $drawOdds = round(max(1.01, 2 - ($draws    / $total)), 2);
            $awayOdds = round(max(1.01, 2 - ($awayWins / $total)), 2);

            DB::table('league_game_odds')->upsert(
                [
                    'league_id'  => $league->id,
                    'game_id'    => $gameID,
                    'home_odds'  => $homeOdds,
                    'draw_odds'  => $drawOdds,
                    'away_odds'  => $awayOdds,
                    'updated_at' => now(),
                ],
                ['league_id', 'game_id'],
                ['home_odds', 'draw_odds', 'away_odds', 'updated_at']
            );
        }
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
