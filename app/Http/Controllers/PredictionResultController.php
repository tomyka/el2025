<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\PredictionResult;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Validation\Factory;

class PredictionResultController extends Controller
{
    public function getPredictionResultsUser() {
        if (session('userID')!=''){
            $teams = Team::pluck('team','id');
            $userID = session('userID');
            $resultAmount = ((session('resultAmount'))==""?18:session('resultAmount'));
            $teamStatisticsController = new TeamStatisticsController();

            $predictionResults= $this->getPredictionGamesUserResultAmount($userID,$resultAmount);
            if (!empty($predictionResults)){
                $predictionResultsWithStats = $teamStatisticsController->prepareTeamStatistics($predictionResults);
                return view('prediction.results')->with('predictionResults',$predictionResultsWithStats)->with('teams',$teams);
            }
            else {
                return view('prediction.results');
            }
        }
        else {
            return redirect('/');
        }
    }

    public function updatePredictionResultUser(Request $request, Factory $validator)
    {
        $validation = $validator->make($request->all(), [
            'homeTeamScore' => 'nullable|integer|max:150|min:50',
            'awayTeamScore' => 'nullable|integer|max:150|min:50'
        ]);

        if ($validation->fails()) {
            return response()->json([
                'error' => true,
                'messages' => $validation->errors()->all()
            ], 400);
        }
        else {

            $userID = session('userID');
            $gameID = $request->input('gameID');
            $homeTeamScore = $request->input('homeTeamScore');
            $awayTeamScore = $request->input('awayTeamScore');
            $gameWinnerID = $request->input('gameWinnerID');

            $game = Game::where('id', $gameID)->first();
            $now = date('Y-m-d H:i:s');

            if ($game->game_date > $now) {
                $predictionResult = PredictionResult::find($request->input('prediction_gameID'));
                $predictionResult->home_team_score = (($homeTeamScore == "") ? null : $homeTeamScore);
                $predictionResult->away_team_score = (($awayTeamScore == "") ? null : $awayTeamScore);
                $predictionResult->game_winner_id = (($gameWinnerID == "") ? null : $gameWinnerID);
                $predictionResult->generated = 0;
                $predictionResult->save();


                if ($homeTeamScore != "" && $awayTeamScore != "") {
                    $auditPredictionGameController = new AuditPredictionGameController();
                    $auditPredictionGameController->insertAuditPredictionGame($userID, $gameID, $homeTeamScore, $awayTeamScore, $gameWinnerID);

                }

            }
            return response()->json([
                'success' => true
            ]);

        }

        }



    public function insertPredictionResultsUser($user_id)
    {
        $games = Game::all();
        if ($games->count() > 0) {
            foreach ($games as $game) {
                $predictionResults[] = [
                    'user_id' => $user_id
                    , 'game_id' => $game->id
                ];
            }
            $predictionResult = new PredictionResult();
            $predictionResult:: insert($predictionResults);
        }
    }

    public function getPredictionResultsUserGroupEventDay($eventID, $groupID, $userID){
        $predictionGamesUserProfileEventDay = DB::select('SELECT
                                		g.id,
                                  		ht.team AS home_team,
                                  		at.team AS away_team,
                                  		ht.id AS home_team_id,
                                  		at.id AS away_team_id,
                                  		g.home_team_score,
                                  		g.away_team_score,
                                  		prr.home_team_score AS p_home_team_score,
                                  		prr.away_team_score AS p_away_team_score,
                                  		IFNULL(por.difference_points,0) AS difference_points,
                                  		IFNULL(por.winner_points,0) AS winner_points,
                                  		IFNULL(por.bingo_points,0) AS bingo_points,
                                  		IFNULL(ROUND((CASE WHEN game_date > DATE_ADD(NOW(),INTERVAL '.session('timeDifference').' HOUR) THEN NULL ELSE por.odds END),4),0) AS odds,
                                  		IFNULL(por.odds_points,0) AS odds_points,
                                  		ROUND(IFNULL(por.full_points,0),2) AS full_points
                                  	FROM games AS g
                                  	    JOIN events AS e ON e.id=g.event_id
                                  		JOIN prediction_results AS prr ON g.id=prr.game_id
                                  		JOIN users AS u ON u.id=prr.user_id
                                  		JOIN user_groups AS ug ON u.id=ug.user_id
                                        JOIN user_settings AS us ON u.id=us.user_id
                                  		JOIN teams AS ht ON g.home_team_id=ht.id
                                  		JOIN teams AS at ON g.away_team_id=at.id
                                  		LEFT JOIN point_results AS por ON prr.user_id=por.user_id AND prr.game_id=por.game_id
                                  	WHERE
                                  	    u.id='.$userID.'
                                  		AND e.id='.$eventID.'
                                  		AND ug.group_id ='.$groupID.'
                                  	ORDER BY g.id ASC');

        return $predictionGamesUserProfileEventDay;
    }

    public function getPredictionGamesUserHistory($userID, $eventID)
    {
        $predictionGamesUserHistory = DB::select('select
                                		g.id as game_id,
                                 		g.home_team_id,
                                  		g.away_team_id,
                                  		ht.team as home_team,
                                  		at.team as away_team,
                                  		g.home_team_score,
                                  		g.away_team_score,
                                        g.game_winner_id,
                                  		pr.home_team_score as home_team_score_prediction,
                                  		pr.away_team_score as away_team_score_prediction,
                                        pr.game_winner_id as game_winner_id_prediction,
                                  	    ROUND(IFNULL(por.difference_points,0),2) AS difference_points,
                                  		ROUND(IFNULL(por.winner_points,0),2) AS winner_points,
                                  		ROUND(IFNULL(por.bingo_points,0),2) AS bingo_points,
                                  		IFNULL(ROUND((CASE WHEN game_date > DATE_ADD(NOW(),INTERVAL '.session('timeDifference').' HOUR) THEN NULL ELSE por.Odds END),4),0) AS odds,
                                  		ROUND(IFNULL(por.odds_points,0),2) AS odds_points,
                                  		ROUND(IFNULL(por.full_points,0),2) AS full_points
                                  	from games as g
                                  		join prediction_results as pr on g.id = pr.game_id
                                  		join users as u on u.id = pr.user_id
                                        join user_settings AS us on u.id = us.user_id
                                  		join teams as ht on g.home_team_id=ht.id
                                  		join teams as at on g.away_team_id=at.id
                                  		join events as e ON e.id = g.event_id
                                  		left join point_results as por on pr.user_id=por.user_id and pr.game_id=por.game_id
                                  	where
                                  		pr.user_id=' . $userID . '
                                  		and e.id < ' . $eventID . '
                                  	order by g.id asc');

        return $predictionGamesUserHistory;
    }

    public function getPredictionGamesProfile($groupID, $eventID){

        $predictionGames = DB::select('select
                                      pr.game_id,
                                      u.username,
	                                  pr.user_id,
                                      pr.home_team_score,
                                      pr.away_team_score,
                                      g.home_team_id,
                                      g.away_team_id,
                                      pr.game_winner_id,
				                      ROUND(IFNULL(por.difference_points,0),1) AS difference_points,
                                  		ROUND(IFNULL(por.winner_points,0),1) AS winner_points,
                                  		ROUND(IFNULL(por.bingo_points,0),1) AS bingo_points,
                                  		IFNULL(ROUND((CASE WHEN game_date > DATE_ADD(NOW(),INTERVAL '.session('timeDifference').' HOUR) THEN NULL ELSE por.Odds END),4),0) AS odds,
                                  		ROUND(IFNULL(por.odds_points,0),1) AS odds_points,
                                  		ROUND(IFNULL(por.full_points,0),1) AS full_points
                        			from prediction_results as pr
                                      join users as u ON u.id = pr.user_id
                                      join games as g ON g.id = pr.game_id
                                      join events AS e ON e.id = g.event_id
                              		  join user_groups as ug on pr.user_id=ug.user_id AND ug.group_id = '.$groupID.' and ug.guest <= '. session('guest').'
                              		  left join point_results as por on por.user_id=pr.user_id AND por.game_id=pr.game_id
                                    where g.game_date < DATE_ADD(NOW(), INTERVAL '.session('timeDifference').' HOUR) AND e.id <= '.(($eventID=="")?99:$eventID).'
                                    ORDER BY pr.user_id ASC, g.id DESC');

        return $predictionGames;
    }

    public function getPredictionGamesUserResultAmount($userID, $resultAmount){
        $predictionGamesUser = DB::select('SELECT
                 DISTINCT
                    prr.id,
                    g.id as game_id,
                    g.game_date,
                    ht.team AS home_team,
                    ht.link AS home_team_link,
                    ht.id AS home_team_id,
                    at.team AS away_team,
                    at.link AS away_team_link,
                    at.id AS away_team_id,
                    prr.home_team_score as home_team_score,
                    prr.away_team_score as away_team_score,
                    prr.game_winner_id as game_winner_id,
                    prr.prediction_date as prediction_date,
                    DATE_ADD(NOW(),INTERVAL '.session('timeDifference').' HOUR) as Now,
                    e.active,
                    e.rate
                from prediction_results as prr
                    join users u on prr.user_id=u.id
                    join games g on g.id=prr.game_id
                    join teams ht on g.home_team_id=ht.id
                    join teams at on g.away_team_id=at.id
                    join events e on e.id=g.event_id
                where
                    prr.user_id='.$userID.' and
                    g.game_date > DATE_ADD(NOW(),INTERVAL '.session('timeDifference').' HOUR)
                order by g.game_date asc, prr.id ASC
                LIMIT '.$resultAmount);

        return $predictionGamesUser;
    }

    public function getPredictionResultHistory(){

        $userID = session('userID');
        $eventID = session('eventID');

        $predictionResultController = new PredictionResultController();
        $predictionResultsHistory = $predictionResultController->getPredictionGamesUserHistory($userID, $eventID);

        return view('summary.history')->with('predictionResults', $predictionResultsHistory);
    }

    public function getPredictionResultSummary() {

        $groupID = session('groupID');
        $eventID = session('eventID');

        $games = DB::select('  select
                                g.id
                                ,ht.team AS home_team
                                ,at.team  AS away_team
                                ,g.home_team_id
                                ,g.away_team_id
                                ,g.home_team_score
                                ,g.away_team_score
                                ,g.game_winner_id
                              from games AS g
                                join teams AS ht ON g.home_team_id=ht.id
                                join teams AS at ON g.away_team_id=at.id
                              where g.game_date < DATE_ADD(NOW(), INTERVAL '.session('timeDifference').' HOUR)
                              order by g.id DESC
                              LIMIT 32
                              ');

        $predictionResultController = new PredictionResultController();
        $predictionResults = $predictionResultController->getPredictionGamesProfile($groupID, $eventID);



        return view('summary.results')->with('games',$games)->with('predictionResults',$predictionResults);
    }
}

