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

            $memberIds = \App\Models\LeagueMember::where('league_id', $league->id)
                ->where('is_guest', false)
                ->pluck('user_id');

            $leaguePredictions = DB::table('prediction_results')
                ->where('game_id', $gameID)
                ->whereIn('user_id', $memberIds)
                ->whereNotNull('home_team_score')
                ->whereNotNull('away_team_score')
                ->select('home_team_score', 'away_team_score')
                ->get();

            $total = $leaguePredictions->count();
            if ($total === 0) {
                continue;
            }

            $homeWins = $leaguePredictions->filter(fn($p) => $p->home_team_score > $p->away_team_score)->count();
            $draws    = $leaguePredictions->filter(fn($p) => $p->home_team_score == $p->away_team_score)->count();
            $awayWins = $leaguePredictions->filter(fn($p) => $p->home_team_score < $p->away_team_score)->count();

            $homeOdds = $homeWins > 0 ? round(log($total / $homeWins, 2), 4) : 0.0;
            $drawOdds = $draws    > 0 ? round(log($total / $draws,    2), 4) : 0.0;
            $awayOdds = $awayWins > 0 ? round(log($total / $awayWins, 2), 4) : 0.0;

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
        $predictionResults = PredictionResult::where('game_id', $gameID)
            ->whereNotNull('home_team_score')
            ->whereNotNull('away_team_score')
            ->get();
        $total             = count($predictionResults);

        if ($total === 0) {
            return (object) ['homeOdds' => 0.0, 'drawOdds' => 0.0, 'awayOdds' => 0.0];
        }

        $homeCount = 0;
        $drawCount = 0;
        $awayCount = 0;

        foreach ($predictionResults as $p) {
            $homeCount += $this->calculateHomeOdds($p->home_team_score, $p->away_team_score);
            $drawCount += $this->calculateDrawOdds($p->home_team_score, $p->away_team_score);
            $awayCount += $this->calculateAwayOdds($p->home_team_score, $p->away_team_score);
        }

        return (object) [
            'homeOdds' => $homeCount > 0 ? round(log($total / $homeCount, 2), 4) : 0.0,
            'drawOdds' => $drawCount > 0 ? round(log($total / $drawCount, 2), 4) : 0.0,
            'awayOdds' => $awayCount > 0 ? round(log($total / $awayCount, 2), 4) : 0.0,
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
