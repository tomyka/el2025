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
            $gameHistory = $pointController->getAllUsersGameHistory($groupID);
            foreach ($points as $i => &$point) {
                $history               = $gameHistory[$point['userID']] ?? [];
                $point['roundHistory'] = $history;
                $n                     = count($history);
                $lastRound             = $n >= 1 ? $history[$n - 1] : null;
                $secondLastRound       = $n >= 2 ? $history[$n - 2] : null;
                $point['rankChange']   = ($lastRound && $secondLastRound)
                    ? $secondLastRound['rank'] - $lastRound['rank']
                    : null;
            }
            unset($point);
            $messages = $messageController->getProfileMessages($groupID);
            $predictionResults = $predictionResults->getPredictionResultsUserGroupEventDay($eventID,$groupID, $userID);
            // @deprecated previousRoundPoints removed from view
            $previousRoundPoints = [];
            $predictionResultsWithStats = $teamStatisticsController->prepareTeamStatistics($predictionResults);

            // Limit upcoming-games widget to first 3 distinct calendar dates
            $upcomingDates = collect($predictionResultsWithStats)
                ->map(fn($item) => substr($item['gameDetails']->game_date, 0, 10))
                ->unique()->take(3)->flip();
            $predictionResultsWithStats = array_values(array_filter(
                $predictionResultsWithStats,
                fn($item) => $upcomingDates->has(substr($item['gameDetails']->game_date, 0, 10))
            ));
            $standings = $predictionStandingController->getPredictionStandingTop4( $groupID);
            $eventDaySurvivalStatus=$predictionSurvivalController->getEventDaySurvivalStatus($userID,$eventID);
            $predictionStandingsPoints = $pointController->getPredictionStandingsUserPoints($userID);

            return view('main')->with('messages', $messages)->with('points', $points)->with('predictionGames', $predictionResultsWithStats)->with('eventDaySurvivalStatus',$eventDaySurvivalStatus)->with('groupDetails',$feeController->getGroupDetails())->with('userDetails',$feeController->getUserDetails())->with('fund',$feeController->getFund())->with('fundCollected',$feeController->getFundCollected())->with('standings',$standings)->with('predictionStandingsPoints',$predictionStandingsPoints);
        }
        else {
            $games = Game::with('away_team')->with('home_team')->take(9)->get();

            return view('main')->with('games',$games);
        }
    }


}
