<?php

namespace App\Http\Controllers;

use App\Models\PointStanding;
use App\Models\PredictionStanding;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class PointStandingController extends Controller
{
    public function getStandingsUserPoints($userID){
        $userPredictionStandingPoints = DB::table('point_standings')
            ->select(DB::raw('SUM(IFNULL(group_position_points,0)) as group_position_points, SUM(IFNULL(last32_points,0)) as last32_points, SUM(IFNULL(last16_points,0)) as last16_points, SUM(IFNULL(quarterfinal_points,0)) as quarterfinal_points, SUM(IFNULL(semifinal_points,0)) as semifinal_points, SUM(IFNULL(final_points,0)) as final_points, SUM(IFNULL(group_position_points,0)+IFNULL(last32_points,0)+IFNULL(last16_points,0)+IFNULL(quarterfinal_points,0)+IFNULL(semifinal_points,0)+IFNULL(final_points,0)) as total_points'))
            ->where('user_id',$userID)
            ->first();

        if (empty($userPredictionStandingPoints)){
            $userPredictionStandingPoints = new \stdClass();
            $userPredictionStandingPoints->group_position_points = '0';
            $userPredictionStandingPoints->last32_points = '0';
            $userPredictionStandingPoints->last16_points = '0';
            $userPredictionStandingPoints->quarterfinal_points = '0';
            $userPredictionStandingPoints->semifinal_points = '0';
            $userPredictionStandingPoints->final_points = '0';
            $userPredictionStandingPoints->total_points = '0';
        }

        return $userPredictionStandingPoints;
    }

    public function updateStandingPoints()
    {
        // Clear the table first to avoid duplicates
        PointStanding::truncate();

        $teams = Team::all();
        $numberOfTeams = $teams->count();

        foreach ($teams as $team) {

            $predictionStandings = PredictionStanding::where('team_id', $team->id)->get();

            foreach ($predictionStandings as $predictionStanding) {
                $pointStanding = new PointStanding();

                $pointStanding->team_id = $team->id;
                $pointStanding->user_id = $predictionStanding->user_id;
                $pointStanding->group_position_points = $this->calculateGroupPositionPoints(
                    $team->group_position,
                    $predictionStanding->group_position,
                    $numberOfTeams
                );
                $pointStanding->last32_points = $this->calculateKnockoutPoints(
                    $team->last32,
                    $predictionStanding->last32,
                    40
                );
                $pointStanding->last16_points = $this->calculateKnockoutPoints(
                    $team->last16,
                    $predictionStanding->last16,
                    60
                );
                $pointStanding->quarterfinal_points = $this->calculateKnockoutPoints(
                    $team->quarterfinal,
                    $predictionStanding->quarterfinal,
                    90
                );
                $pointStanding->final_points = $this->calculateFinalPoints(
                    $team->final,
                    $predictionStanding->final
                );

                $pointStanding->save();
            }
        }

        return redirect()->route('admin.teams');
    }

    private function calculateFinalPoints($teamFinal, $predictedFinal)
    {
        $pointsMatrix = [
            4 => [4 => 420, 3 => 420, 2 => 360, 1 => 240],
            3 => [4 => 420, 3 => 420, 2 => 360, 1 => 240],
            2 => [4 => 360, 3 => 360, 2 => 600, 1 => 480],
            1 => [4 => 240, 3 => 240, 2 => 480, 1 => 720],
        ];

        return $pointsMatrix[$teamFinal][$predictedFinal] ?? 0;
    }

    private function calculateGroupPositionPoints($teamPosition, $predictedPosition, $numberOfTeams){
        if ($teamPosition != 0) {
            $teamGroupPositionPoints = ($numberOfTeams - 1 - ABS($teamPosition - $predictedPosition)) * 10;
        }
        else {
            $teamGroupPositionPoints = 0;
        }

        return $teamGroupPositionPoints;
    }

    private function calculateKnockoutPoints($teamResult, $predictedResult, $points){
        return ($teamResult == 1 && $predictedResult == 1) ? $points : 0;
    }

}
