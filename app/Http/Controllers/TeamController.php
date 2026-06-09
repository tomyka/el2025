<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Foundation\Validation\ValidatesRequests;

class TeamController extends Controller
{
    public function getTeam(){
        $teams = Team::all();
        if ($teams->count() == 0){
            return view ('admin.teaminsert');
        }
        else {
            return view ('admin.teams', ['teams' =>$teams]);
        }
    }

    public function insertTeams(Request $request){
            $teams = array();

        for($i = 1; $i <=$request->input('teamCount'); $i++){
            $teams[] = array('team' => "team".$i);
            }
        Team::insert($teams);

        $predictionResultsController = new PredictionResultController();
        $predictionResultsController->insertPredictionResultsUser(session('userID'));

        $predictionStandingsController = new PredictionStandingController();
        $predictionStandingsController->insertPredictionStandingsUser(session('userID'));

        $predictionSurvivalTeams = new PredictionSurvivalTeamController();
        $predictionSurvivalTeams->insertPredictionSurvivalTeamsUser(session('userID'));


        return redirect()->route('admin.teams')->with('info','Inserted '.$request->input('teamCount'). ' teams');
    }

    public function updateTeams(Request $request){

        foreach ($request->input('teamID') as $i => $id) {
            $team = Team::find($id);
            if (!$team) continue;
            $team->group_position = $request->input('groupPosition')[$i];
            $team->last32         = isset($request->input('last32')[$i])        ? 1 : 0;
            $team->last16         = isset($request->input('last16')[$i])        ? 1 : 0;
            $team->quarterfinal   = isset($request->input('quarterfinal')[$i])  ? 1 : 0;
            $team->semifinal      = isset($request->input('semifinal')[$i])     ? 1 : 0;
            $team->final          = $request->input('final')[$i];
            $team->save();
        }
        return redirect()->route('admin.teams')->with('info','Teams updated');
    }

    public function updateTeamDetails(Request $request){
        $team = Team::findOrFail($request->input('teamID'));
        $team->team       = $request->input('teamName');
        $team->link       = $request->input('teamLink');
        $team->group_name = $request->input('groupName');
        $team->save();
        return redirect()->route('admin.teams')->with('info', 'Komanda atnaujinta');
    }


}
