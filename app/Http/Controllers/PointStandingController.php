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
            ->select(DB::raw('SUM(IFNULL(group_position_points,0)) as group_position_points, SUM(IFNULL(last16_points,0)) as last16_points, SUM(IFNULL(quarterfinal_points,0)) as quarterfinal_points, SUM(IFNULL(semifinal_points,0)) as semifinal_points, SUM(IFNULL(final_points,0)) as final_points, SUM(IFNULL(group_position_points,0)+IFNULL(last16_points,0)+IFNULL(quarterfinal_points,0)+IFNULL(semifinal_points,0)+IFNULL(final_points,0)) as total_points'))
            ->where('user_id',$userID)
            ->first();

        if (empty($userPredictionStandingPoints)){
            $userPredictionStandingPoints = new \stdClass();
            $userPredictionStandingPoints->group_position_points = '0';
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
        $quarterfinalPoints = 90;

        foreach ($teams as $team) {

            // Get the prediction standings associated with the current team
            $predictionStandings = PredictionStanding::where('team_id', $team->id)->get();

            foreach ($predictionStandings as $predictionStanding) {
                // Create a new PointStanding instance
                $pointStanding = new PointStanding();

                // Assign values to the attributes
                $pointStanding->team_id = $team->id;  // Use the actual team id
                $pointStanding->user_id = $predictionStanding->user_id;
                $pointStanding->group_position_points = $this->calculateGroupPositionPoints(
                    $team->group_position,
                    $predictionStanding->group_position,
                    $numberOfTeams
                );
                $pointStanding->quarterfinal_points = $this->calculateQuarterfinalPoints(
                    $team->quarterfinal,
                    $predictionStanding->quarterfinal,
                    $quarterfinalPoints
                );
                $pointStanding->final_points = $this->calculateFinalPoints(
                    $team->final,
                    $predictionStanding->final
                );

                // Save the new instance to the database
                $pointStanding->save();
            }
        }

        return redirect()->route('admin.teams');
    }

    private function calculateFinalPoints($teamFinal, $predictedFinal)
    {
        $pointsMatrix = [
            4 => [4 => 450, 3 => 330, 2 => 240, 1 => 180],
            3 => [4 => 330, 3 => 540, 2 => 360, 1 => 300],
            2 => [4 => 240, 3 => 360, 2 => 600, 1 => 480],
            1 => [4 => 180, 3 => 300, 2 => 480, 1 => 720],
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

    private function calculateQuarterfinalPoints($teamQuarterfinal, $predictedQuarterfinal, $quarterfinalPoints){
        if ($teamQuarterfinal==1 && $predictedQuarterfinal==1){
            $teamQuarterfinalPoints = $quarterfinalPoints;
        }
        else{
            $teamQuarterfinalPoints = 0;
        }

        return $teamQuarterfinalPoints;
    }

}
