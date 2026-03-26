<?php

namespace App\Http\Controllers;

use App\Models\PointResult;
use App\Models\PointSurvival;
use Illuminate\Support\Facades\DB;

class PointController extends Controller
{

    public function getAllUserPoints($groupID){
        $users=DB::table('users')->join('user_groups','users.id','=','user_groups.user_id')->where('user_groups.group_id','=',$groupID)->where('user_groups.guest','<=',session('guest'))->select('users.id','users.username')->get();

        $pointStandingController = new PointStandingController();
        $pointsResultController = new PointResultController();
        $pointSurvivalController = new PointSurvivalController();

        foreach ($users as $user){
            $userGamePoints = array_sum(array_column($pointsResultController->getUserProfilePoints($user->id),'full_points'));
            $userGameBingo = array_sum(array_column($pointsResultController->getUserProfilePoints($user->id),'bingo_points'));
            $gameCount = count($pointsResultController->getUserProfilePoints($user->id));
            $standingPoints = $pointStandingController->getStandingsUserPoints($user->id);
            $survivalPoints = $pointSurvivalController->getPredictionSurvivalUserPoints($user->id);


            $userAllPoints[] = [
                'userID'            => $user->id,
                'username'          => $user->username,
                'userGamePoints'    => round((($userGamePoints=="")?0:$userGamePoints),1),
                'userGameBingo'     => (($userGameBingo=="")?0:$userGameBingo),
                'averagePoints'     => (($gameCount==0)?0:round($userGamePoints/$gameCount,1)),
                'standingPoints'    => $standingPoints,
                'survivalPoints'    => (($survivalPoints=="")?0:$survivalPoints)
            ];
        }

        if (!empty($userAllPoints)) {
            usort($userAllPoints, function ($a, $b) {
                return $b['userGamePoints'] + $b['standingPoints']->total_points + $b['survivalPoints'] <=> $a['userGamePoints'] + $a['standingPoints']->total_points + $a['survivalPoints'];
            });
        }
        else {
            $points = [];
        }

        return $userAllPoints;
    }


    public function getPointEventTotal($eventID, $groupID){
        $users=DB::table('users')->join('user_groups','users.id','=','user_groups.user_id')->where('user_groups.group_id','=',$groupID)->where('user_groups.guest','<=',session('guest'))->select('users.id','users.username')->get();

        foreach ($users as $user){
            $pointResultUserEvent = $this->getPointPredictionUserEvent($user->id,$eventID);
            $pointSurvivalUserEvent = $this->getPointSurvivalUserEvent($user->id,$eventID);

            $predictionGamesRoundPoints[] = [
                'userID'            => $user->id,
                'username'          => $user->username,
                'pointResult'       => $pointResultUserEvent,
                'pointSurvival'     => $pointSurvivalUserEvent
            ];
        }
        if (isset($predictionGamesRoundPoints)) {
            usort($predictionGamesRoundPoints, function ($a, $b) {
                return $b['pointResult']->full_points + $b['pointSurvival'] <=> $a['pointResult']->full_points + $a['pointSurvival'];
            });
        }

        return $predictionGamesRoundPoints;
    }

    public function getPointPredictionUserEvent($userID, $eventID){
        $points = DB::table('events')
            ->join('games','events.id','=','games.event_id')
            ->join('point_results','games.id','=','point_results.game_id')
            ->where('point_results.user_id','=',$userID)
            ->where('events.id','=',$eventID)
            ->selectRaw('
        IFNULL(ROUND(SUM(full_points), 1), 0) AS full_points,
        IFNULL(ROUND(AVG(full_points), 1), 0) AS avg_points,
        IFNULL(SUM(CASE WHEN winner_points > 0 THEN 1 ELSE 0 END), 0) AS correct_guess
    ')->first();
        return $points;
    }

    public function getPointSurvivalUserEvent($userID, $eventID){
        $points = PointSurvival::where('user_id',$userID)->where('event_id',$eventID)->first();
        return $points->survival_points ?? 0;
    }
    public function getPredictionStandingsUserPoints($userID){
        $PredictionStandingsUserPoints = DB::select('SELECT
                                            t.id,
                                             t.team
                                          	,SUM(IFNULL(pos.group_position_points,0)) AS group_position_points
                                            ,SUM(IFNULL(pos.last16_points,0)) AS last16_points
                                            ,SUM(IFNULL(pos.quarterfinal_points,0)) AS quarterfinal_points
                                            ,SUM(IFNULL(pos.final_points,0)) AS final_points
                                          FROM point_standings AS pos
                                                JOIN teams AS t on t.id=pos.team_id
                                          WHERE
                                            	pos.user_id = '.$userID.'
                                          GROUP BY t.team, t.id
                                          ORDER BY t.id');

        return $PredictionStandingsUserPoints;
    }



}
