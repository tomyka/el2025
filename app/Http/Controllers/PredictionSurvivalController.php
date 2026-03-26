<?php

namespace App\Http\Controllers;


use App\Models\Team;
use App\Models\Game;
use App\Models\PointSurvival;
use App\Models\PredictionSurvival;
use App\Models\User;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\Factory;
use Illuminate\Support\Facades\DB;

class PredictionSurvivalController extends Controller
{
    public function getPredictionSurvivalUser() {
        if (session('userID')!='') {
            $userID = session('userID');
            $eventID = session('eventID');

            $predictionSurvivals = $this->getPredictionSurvivalUserEventID($userID, $eventID);
            $survivalPoints = $this->checkSurvivalEventStatus($userID, $eventID);


            return view('prediction.survival')->with('predictionSurvivals', $predictionSurvivals)->with('survivalPoints', $survivalPoints);
        }
        else {
            return redirect('/');
        }
    }

    public function updatePredictionSurvivalUser(Request $request, Factory $validator)
    {
        if ($request->input('teamID')!="") {

            $predictionSurvivalTeams = PredictionSurvival::where('user_id', session('userID'))->where('event_id', session('eventID'))->first();
            if ($predictionSurvivalTeams != "") {
                $predictionSurvivalTeams->event_id = null;
                $predictionSurvivalTeams->save();
            }

            $predictionSurvivalTeams = PredictionSurvival::where('user_id',session('userID'))->where('team_id',$request->input('teamID'))->first();
            $predictionSurvivalTeams->event_id = session('eventID');
            $predictionSurvivalTeams->save();

        }

    }

    public function getPredictionSurvivalSummary() {
        $eventID = session('eventID');
        $events = Event::where('id','<=',$eventID)->where('rate','=',1)->get();
        $users = User::all();

        $predictionSurvivalController = new PointSurvivalController();
        $predictionSurvivals = $predictionSurvivalController->getPointSurvivalEventID($eventID);

        return view('summary.survivals')->with('events',$events)->with('users',$users)->with('predictionSurvivals',$predictionSurvivals);
    }

    public function insertPredictionSurvivalUser($user_id)
    {
        $teams = Team::all();
        if  ($teams->count() > 0) {
            foreach ($teams as $team) {
                $predictionSurvivals[] = [
                    'user_id' => $user_id
                    , 'team_id' => $team->id
                ];
            }
            $predictionSurvival = new PredictionSurvival();
            $predictionSurvival:: insert($predictionSurvivals);
        }
    }

    public function insertPredictionSurvivalEvent($eventID)
    {
        $users = User::all();
        foreach ($users as $user) {
            $predictionSurvivals[] = [
                'user_id' => $user->id
                , 'event_id' => $eventID
            ];
        }
        $predictionSurvival = new PredictionSurvival();
        $predictionSurvival:: insert($predictionSurvivals);
    }

    public function getEventDaySurvivalStatus($userID, $eventID){
        $eventDaySurvivalStatus = PredictionSurvival::where('user_id','=',$userID)->where('id','=',$eventID)->count();
        return $eventDaySurvivalStatus;
    }

    public function getPredictionSurvivalUserEventID($userID, $eventID){
        $predictionSurvivalUserEvent = DB::select('
            SELECT distinct
   t.id as team_id
  ,t.team
  ,ps.event_id AS event_id
  ,ps.team_id AS survival_team_id
  ,CASE WHEN g.game_date > DATE_ADD(NOW(), INTERVAL '.session('timeDifference').' HOUR) AND (ps.event_id = '.$eventID.' or ps.event_id IS NULL) AND g.home_team_score IS NULL AND g.away_team_score IS NULL  THEN 1 ELSE 0 END AS active
  ,o.team AS opponent
  , CASE WHEN t.id=g.home_team_id THEN \'H\' ELSE \'A\' END AS location
FROM teams AS t
  JOIN games AS g ON (t.id=g.home_team_id OR t.id=g.away_team_id)
  JOIN events AS e ON e.id=g.event_id AND e.id = '.$eventID.'
  JOIN teams AS o ON o.id=CASE WHEN t.id=g.home_team_id THEN g.away_team_id WHEN t.id=g.away_team_id THEN g.home_team_id END
  LEFT JOIN prediction_survivals AS ps ON t.id=ps.team_id AND ps.user_id='.$userID.'
ORDER BY t.id'
        );

        return $predictionSurvivalUserEvent;
    }


    public function updatePredictionSurvivalGame($gameID)
    {
        $gameDetails = Game :: select('home_team_id','home_team_score','away_team_id','away_team_score', 'event_id')->where('id',$gameID)->first();
        if ($gameDetails->home_team_score > $gameDetails->away_team_score){
            $gameWinnerTeamID = $gameDetails->home_team_id;
            $gameLoserTeamID = $gameDetails->away_team_id;
        }
        else{
            $gameLoserTeamID = $gameDetails->home_team_id;
            $gameWinnerTeamID = $gameDetails->away_team_id;
        }

        $pointSurvivalController = new PointSurvivalController();
        $predictionSurvivals = PredictionSurvival :: where('event_id',$gameDetails->event_id)->get();

        foreach ($predictionSurvivals as $predictionSurvival) {

            if ($predictionSurvival->team_id == $gameLoserTeamID) {
                $this->resetUserSurvivalSequence($predictionSurvival->user_id);
                $pointSurvivalController->updatePointSurvival($predictionSurvival->user_id, $gameDetails->event_id, $predictionSurvival->team_id);
            }
            elseif ($predictionSurvival->team_id == $gameWinnerTeamID) {
                $pointSurvivalController->updatePointSurvival($predictionSurvival->user_id, $gameDetails->event_id, $predictionSurvival->team_id);
            }
        }
    }

    public function resetUserSurvivalSequence($userID){
        PredictionSurvival :: where('user_id',$userID)->update(['event_id'=>null]);
    }

    private function checkSurvivalEventStatus($userID,$eventID){
        $pointSurvival = PointSurvival::where('user_id',$userID)->where('event_id',$eventID)->first();

        return $pointSurvival ? $pointSurvival->survival_points : -1;
    }
}
