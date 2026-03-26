<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class TeamStatisticsController extends Controller
{
    public function getTeamStatistics($teamID){

        $teamHomeStatistics = DB::select('select
                                              SUM(CASE WHEN home_team_score IS NOT NULL then 1 else 0 end) AS gameCount,
                                              SUM(CASE WHEN g.home_team_id=t.id AND home_team_score > away_team_score OR g.away_team_id=t.id AND home_team_score < away_team_score then 1 else 0 end) AS won,
                                              SUM(CASE WHEN g.home_team_id=t.id AND home_team_score < away_team_score OR g.away_team_id=t.id AND home_team_score > away_team_score then 1 else 0 end) AS lost,
                                              ROUND(SUM(CASE t.id WHEN g.home_team_id THEN home_team_score WHEN g.away_team_id THEN away_team_score END),2) AS pointsScored,
                                              ROUND(SUM(CASE t.id WHEN g.home_team_id THEN away_team_score WHEN g.away_team_id THEN home_team_score END),2) AS pointsAllowed
                                          from teams AS t
                                            LEFT JOIN games AS g ON t.id=g.home_team_id OR t.id=g.away_team_id
                                          where
                                            t.id = '.$teamID.'
                                          group by t.id
                                     ');
        return $teamHomeStatistics[0];
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
