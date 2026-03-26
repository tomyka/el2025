<?php

namespace App\Http\Controllers;

use App\Models\PointSurvival;
use App\Models\PredictionSurvival;
use Illuminate\Support\Facades\DB;

class PointSurvivalController extends Controller
{
    public function getPredictionSurvivalUserPoints($userID){
        $pointUserSurvival = PointSurvival::where('user_id', '=', $userID)->sum('survival_points');
        return $pointUserSurvival;
    }

    public function getPointSurvivalEventID($eventID){
        $predictionSurvivals = DB::table('users as u')
            ->crossJoin('events as e') // Simulate CROSS JOIN
            ->select(
                'u.id as user_id',
                'e.id as event_id',
                DB::raw("IFNULL(ps.survival_points, '') as survival_points"), // Handle null survival_points
                DB::raw("IFNULL(t.team, '') as team"), // Handle null survival_points
            )
            ->leftJoin('point_survivals as ps', function($join) {
                $join->on('e.id', '=', 'ps.event_id')
                    ->on('u.id', '=', 'ps.user_id'); // Left join on event_id and user_id
            })
            ->leftjoin('teams as t', 'ps.team_id', '=', 't.id') // Inner join on teams to get team name
            ->where('e.rate', 1) // Filter for events with rate = 1
            ->where('e.id', '<=', $eventID) // Filter for events with id <= 1
            ->orderBy('e.id') // Order by user_id
            ->orderBy('u.id') // Order by user_id
            ->get();
        return $predictionSurvivals;
    }

    public function updatePointSurvival($userID, $eventID, $teamID){

        PointSurvival::where('user_id', $userID)->where ('event_id', $eventID)->where('team_id', $teamID)->delete();

        $points = PredictionSurvival::where('user_id', $userID)->whereNotNull('event_id')->count()*10;

        $pointSurvival = new PointSurvival();
        $pointSurvival->user_id = $userID;
        $pointSurvival->event_id = $eventID;
        $pointSurvival->team_id = $teamID;
        $pointSurvival->survival_points = $points;
        $pointSurvival->save();

    }
}
