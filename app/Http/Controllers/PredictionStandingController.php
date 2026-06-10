<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePredictionStandingRequest;
use App\Models\PredictionStanding;
use App\Models\Team;
use Illuminate\Support\Facades\DB;


class PredictionStandingController extends Controller
{
    public function getPredictionStandingsUser() {
        if (session('userID')!='') {
            $predictionStandings = DB::table('prediction_standings')->join('teams', 'prediction_standings.team_id', '=', 'teams.id')->where('prediction_standings.user_id', session('userID'))->select('teams.team', 'teams.group_name', 'teams.link', 'prediction_standings.*')->get();
            return view('prediction.standings')->with('predictionStandings', $predictionStandings);
        }
        else {
                return redirect('/');
            }
    }

    public function updatePredictionStandingsUser(UpdatePredictionStandingRequest $request)
    {
        $predictionStanding = PredictionStanding::where('id', $request->input('prediction_standingID'))
            ->where('user_id', session('userID'))
            ->firstOrFail();
        $predictionStanding->group_position = $request->input('groupPosition');
        $predictionStanding->last32         = $request->input('last32');
        $predictionStanding->last16         = $request->input('last16');
        $predictionStanding->quarterfinal   = $request->input('quarterfinal');
        $predictionStanding->semifinal      = $request->input('semifinal');
        $predictionStanding->final          = $request->input('final');
        $predictionStanding->save();

        return response()->json(['success' => true]);
    }

    public function insertPredictionStandingsUser($user_id)
    {
        $teams = Team::all();
        if ($teams->count() > 0) {
            foreach ($teams as $team) {
                $predictionStandings[] = [
                    'user_id' => $user_id
                    , 'team_id' => $team->id
                ];
            }
            $predictionStanding = new PredictionStanding();
            $predictionStanding:: insert($predictionStandings);
        }
    }

    public function getPredictionStandingSummary() {
        $groupID = session('leagueID');
        $teams = Team::all();

        $predictionStandingController = new PredictionStandingController();
        $predictionStandings = $predictionStandingController->getPredictionStandingProfile($groupID);


        return view('summary.standings')->with('predictionStandings',$predictionStandings)->with('teams',$teams);
    }
   public function getPredictionStandingProfile($groupID){
        $predictionStandingProfile = DB::select('SELECT
                                 u.username
                                ,t.id as team_id
                                ,t.team
                                ,ps.group_position
                                ,IFNULL(pos.group_position_points,0) AS group_position_points
                                ,ps.last32
                                ,IFNULL(pos.last32_points,0) AS last32_points
                                ,ps.last16
                                ,IFNULL(pos.last16_points,0) AS last16_points
                                ,ps.quarterfinal
                                ,pos.quarterfinal_points AS quarterfinal_points
                                ,ps.semifinal
                                ,IFNULL(pos.semifinal_points,0) AS semifinal_points
                                ,ps.final
                                ,pos.final_points AS final_points
                              FROM users AS u
                                JOIN prediction_standings AS ps ON u.id=ps.user_id
                                LEFT JOIN point_standings AS pos ON u.id = pos.user_id and ps.team_id=pos.team_id
                                JOIN teams AS t ON ps.team_id=t.id
                                JOIN league_members AS lm ON u.id=lm.user_id
                              WHERE lm.league_id = ?',
            [$groupID]);

        return $predictionStandingProfile;
    }

    public function getPredictionStandingTop4($groupID){
        $predictionStandingTop4 = DB::select('select
    team AS team
  ,SUM(CASE WHEN ps.final = 1 then 1 else 0 END) as firstPlacePrediction
  ,SUM(CASE WHEN ps.final = 2 then 1 else 0 END) as secondPlacePrediction
  ,SUM(CASE WHEN ps.final = 3 then 1 else 0 END) as thirdPlacePrediction
  ,SUM(CASE WHEN ps.final = 4 then 1 else 0 END) as fourthPlacePrediction
  from prediction_standings AS ps
    join teams as t on ps.team_id=t.id
    join league_members AS lm on ps.user_id=lm.user_id
  where ps.final is not null and lm.league_id = ?
group by team
  order by
     firstPlacePrediction desc
    ,secondPlacePrediction desc
    ,thirdPlacePrediction desc',
            [$groupID]);

        return $predictionStandingTop4;
    }

}
