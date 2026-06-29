<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class TeamStatisticsController extends Controller
{
    public function getTeamStatistics($teamID): ?object
    {
        $games = DB::table('games')
            ->where(function ($q) use ($teamID) {
                $q->where('home_team_id', $teamID)->orWhere('away_team_id', $teamID);
            })
            ->whereNotNull('home_team_score')
            ->select('home_team_id', 'home_team_score', 'away_team_score')
            ->get();

        if ($games->isEmpty()) {
            return null;
        }

        $stats = ['gameCount' => 0, 'won' => 0, 'lost' => 0, 'pointsScored' => 0.0, 'pointsAllowed' => 0.0];
        foreach ($games as $game) {
            $isHome   = (int) $game->home_team_id === (int) $teamID;
            $scored   = $isHome ? (int) $game->home_team_score : (int) $game->away_team_score;
            $conceded = $isHome ? (int) $game->away_team_score : (int) $game->home_team_score;
            $stats['gameCount']++;
            $stats['pointsScored']  += $scored;
            $stats['pointsAllowed'] += $conceded;
            if ($scored > $conceded) $stats['won']++;
            if ($scored < $conceded) $stats['lost']++;
        }

        return (object) $stats;
    }

    public function prepareTeamStatistics($predictionResults){
        if (!empty($predictionResults)) {
            foreach ($predictionResults as $predictionResult){
                $homeTeamStats = $this->getTeamStatistics($predictionResult->home_team_id);
                $awayTeamStats = $this->getTeamStatistics($predictionResult->away_team_id);
                $predictionResultWithStats[]=[
                    'gameDetails'=>$predictionResult,
                    'homeTeamStats'=>$homeTeamStats,
                    'awayTeamStats'=>$awayTeamStats,
                ];
            }
        }
        else {
            $predictionResultWithStats = [];
        }
        return $predictionResultWithStats;
    }
}
