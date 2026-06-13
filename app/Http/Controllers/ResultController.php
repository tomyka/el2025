<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateResultRequest;
use App\Models\Game;
use App\Models\PredictionResult;

use Carbon\Carbon;

class ResultController extends Controller
{


    public function getResultsAll() {
        $games = Game::with('home_team')->with('away_team')->with('event')->orderBy('game_date')->get();
        $now = Carbon::now('UTC');
        return view('admin.results')->with('games',$games)->with('now',$now);
    }
    public function getResultsCurrentRound() {
        $games = Game::with('home_team')->with('away_team')->with('event')->where('event_id', '=', session('eventID'))->orderBy('game_date')->get();
        $now = Carbon::now('UTC');
        return view('admin.results')->with('games',$games)->with('now',$now);
    }


    public function updateResult(UpdateResultRequest $request)
    {
        $gameID        = $request->input('gameID');
        $homeTeamScore = $request->input('homeTeamScore');
        $awayTeamScore = $request->input('awayTeamScore');

        $game = Game::find($gameID);
        $game->home_team_score    = $homeTeamScore;
        $game->away_team_score    = $awayTeamScore;
        $game->save();

        if ((($homeTeamScore=="")?-1:$homeTeamScore) != -1 || (($awayTeamScore=="")?-1:$awayTeamScore) != -1) {
            //random generation of missing prediction results
            $this->generateMissingResults($gameID);

            if (session('survivalGame') != 0) {
                //Update Survival sequences for each user
                $predictionSurvivalController = new PredictionSurvivalController();
                $predictionSurvivalController->updatePredictionSurvivalGame($gameID);

               }

            $gameOddsController = new GameOddsController();
            $gameOddsController->updateGameOdds($gameID);

            $pointsResultController = app(PointResultController::class);
            $pointsResultController->updateGamePoints($gameID);
            $pointsResultController->recalculateStreaks();

        }
        return response()->json([
            'success' => true
        ]);
    }

   private function generateMissingResults ($gameID){

        $homeTeamScore = $this->generateMissingScore();
        $awayTeamScore = $this->generateMissingScore();;
        $predictionResults = PredictionResult::where('game_id',$gameID)->where('home_team_score',null)->get();

        foreach ($predictionResults as $predictionResult){
            $predictionResult->home_team_score = $homeTeamScore;
            $predictionResult->away_team_score = $awayTeamScore;
            $predictionResult->generated = 1;
            $predictionResult->save();
        }
    }

    public function getEventGameUnfinishedCount ($eventID) {
        $gameCount = Game :: where('home_team_score',null)->join('events','games.event_id','=','events.id')->where('event_id',$eventID)->count();
        return $gameCount;
    }

    private function generateMissingScore(){
        return random_int(0, 5);
    }
}
