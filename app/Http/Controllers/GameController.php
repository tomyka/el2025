<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Game;
use App\Models\Group;
use App\Models\Team;
use App\Models\Event;
use App\Models\PredictionResult;
use App\Models\GameOdds;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class GameController extends Controller
{
    public function getGame () {
        $games = Game::where('gameDate','>',date("Y-m-d"))->with('awayTeam')->with('homeTeam')->take(12)->get();
        $profiles = Profile::pluck('profile','profileID');
        Session::put ('profiles',$profiles);
        return view('welcome',['games'=>$games]);
    }


    public static function getMaxGameDateTime(){
        $maxGameDateTime = Game::select('game_date')->where('event_id','>=',session('eventID'))->orderby('game_date','DESC')->first();
        if ($maxGameDateTime == "") {
            return date("Y-m-d H:i:s");
        }
        else {
            return $maxGameDateTime->game_date;
        }

    }

    public function getGameAll() {
        date_default_timezone_set('Europe/Vilnius');
        $games = Game::with('home_team')->with('away_team')->with('event')->orderByDesc('id')->get();
        $teams = Team::pluck('team','id');
        $events = Event::pluck('event','id');
        $maxEventID = Game::max('event_id');
        $gameMaxDateTime = GameController::getMaxGameDateTime();
        $now = Carbon::now();
        return view('admin.games')->with('games',$games)->with('teams',$teams)->with('events',$events)->with('lastEnteredEventID',$maxEventID)->with('gameMaxDateTime',$gameMaxDateTime)->with('now',$now);

    }



    public function getGamesResultsAll() {
        date_default_timezone_set('Europe/Vilnius');
        $games = Game::with('homeTeam')->with('awayTeam')->with('event')->orderByDesc('gameID')->get();
        $teams = Team::pluck('team','teamID');
        $now = Carbon::now();
        return view('admin.gameResults')->with('games',$games)->with('teams',$teams)->with('now',$now);

    }

    public function insertGame(Request $request){
        $game = new game();
        $predictionResult = new PredictionResult();
        $gameOdds = new GameOdds();

        $game->game_date = $request->input('gameDate').' '.$request->input('gameHour').':'.$request->input('gameMinute');;
        $game->home_team_id = $request->input('homeTeamID');
        $game->away_team_id = $request->input('awayTeamID');
        $game->event_id = $request->input('eventID');
        $game->save();

        $gameID = $game->id;

        $users = User::all();
        foreach($users as $user) {
            $predictionResults[]=[
                'user_id'=>$user->id
                ,'game_id'=>$gameID
            ];
        }

        $predictionResult :: insert($predictionResults);
        $gameOdds :: insert(['game_id'=>$gameID]);

        return redirect()->route('admin.games')->with('info','Game '.$gameID.' has been inserted');
    }

    public function updateGame(Request $request)
    {
        $gameID         = $request->input('gameID');
        $gameDate       = $request->input('gameDate');
        $homeTeamID     = $request->input('homeTeamID');
        $awayTeamID     = $request->input('awayTeamID');

        $eventID        = $request->input('eventID');

        $game = game::find($gameID);
        $game->game_date     = $gameDate;
        $game->home_team_id   = $homeTeamID;
        $game->away_team_id   = $awayTeamID;
        $game->event_id      = $eventID;
        $game->save();

        return redirect()->route('admin.games')->with('info','Game '.$gameID.' has been updated');
    }

    public function deleteGame(Request $request)
    {
        game::destroy($request->input('gameID'));
        return redirect()->route('admin.games')->with('info','game '. $request->input('gameID') .' deleted');
    }
}
