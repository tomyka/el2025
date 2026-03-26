<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Http\Controllers\PredictionStandingsController;
use DB;


class ChartController extends Controller
{
    public function getChartData() {
        $pointStandingController = new PointStandingController();
    $history_rounds = 5;
    $labels = DB::table('events')->join('games','games.event_id','=','events.id')->where('event_id','>=',session('eventID')-$history_rounds)->whereNotNull('games.home_team_score')->distinct()->pluck('events.id');
    $users = DB::table('user_groups')->where('group_id','=',session('groupID'))->join('colors','user_groups.user_id','=','colors.id')->join('users','user_groups.user_id','=','users.id')->select('users.id','users.username','colors.color_code')->get();
    $userResults = DB::select('select
                                      e.id as event_id,
	                                  pr.user_id,
				                      SUM(full_points)+SUM(IFNULL(ps.survival_points,0)) AS points
                        			from prediction_results as pr
                                      join games as g ON pr.game_id=g.id
                                      join events as e ON g.event_id=e.id
                              			left join point_results as por on pr.user_id=por.user_id AND pr.game_id=por.game_id
                                        left join point_survivals AS ps ON pr.user_id=ps.user_id AND e.id=ps.event_id AND (g.home_team_id=ps.team_id OR g.away_team_id=ps.team_id)
                                    where g.home_team_score IS NOT NULL
                                    group by pr.user_id,e.id
                                   ');

        foreach ($users as $user){
            $runningtotal = $pointStandingController->getStandingsUserPoints($user->id)->total_points;
            $data = array();
            foreach ($userResults as $userResult) {
                if ($user->id == $userResult->user_id) {
                    $runningtotal += $userResult->points;
                    if ($userResult->event_id >= session('eventID')-$history_rounds) {
                        $data[] = Round($runningtotal, 2);
                    }
                }

            }
            $datasets[] = array(
                'data' => $data,
                'label' => $user->username,
                'backgroundColor' => $user->color_code,
                'borderColor' => $user->color_code,
                'fill' => false

            );
            reset ($data);
        }
        $json_datasets = json_encode($datasets);
        $chart_datasets =  preg_replace('/"([a-zA-Z]+[a-zA-Z0-9_]*)":/','$1:',$json_datasets);
        //return $labels;
        return view('summary.chart')->with('labels',$labels)->with('datasets',$chart_datasets);
    }

}
