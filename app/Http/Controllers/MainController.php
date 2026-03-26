<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Group;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;



class MainController extends Controller
{
    public function loadApp(){


        $teamStatisticsController = new TeamStatisticsController();
        $feeController = new FeeController();
        $predictionResults = new PredictionResultController();
        $pointController = new PointController();
        $predictionSurvivalController = new PredictionSurvivalController();
        $predictionStandingController = new PredictionStandingController();
        $messageController = new MessageController();
        $user = Auth::user();

        if (isset($user)) {
            $sessionController = new SessionController();
            $sessionController->setSession($user);
            $groupID = session('groupID');
            $userID = session('userID');
            $eventID = session('eventID');
            $points = $pointController->getAllUserPoints($groupID);
            $messages = $messageController->getProfileMessages($groupID);
            $predictionResults = $predictionResults->getPredictionResultsUserGroupEventDay($eventID,$groupID, $userID);
            $previousRoundPoints = $pointController->getPointEventTotal($eventID-1, $groupID);
            $predictionResultsWithStats = $teamStatisticsController->prepareTeamStatistics($predictionResults);
            $standings = $predictionStandingController->getPredictionStandingTop4( $groupID);
            $eventDaySurvivalStatus=$predictionSurvivalController->getEventDaySurvivalStatus($userID,$eventID);
            $predictionStandingsPoints = $pointController->getPredictionStandingsUserPoints($userID);

            return view('main')->with('messages', $messages)->with('points', $points)->with('predictionGames', $predictionResultsWithStats)->with('previousRoundPoints', array_slice($previousRoundPoints,0,5))->with('eventDaySurvivalStatus',$eventDaySurvivalStatus)->with('groupDetails',$feeController->getGroupDetails())->with('userDetails',$feeController->getUserDetails())->with('fund',$feeController->getFund())->with('fundCollected',$feeController->getFundCollected())->with('standings',$standings)->with('predictionStandingsPoints',$predictionStandingsPoints);
        }
        else {
            $games = Game::with('away_team')->with('home_team')->take(9)->get();

            return view('main')->with('games',$games);
        }
    }


}
