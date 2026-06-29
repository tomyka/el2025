<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateResultRequest;
use App\Models\Game;
use App\Models\PredictionResult;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        $gameWinnerID  = $request->input('gameWinnerID');

        DB::transaction(function () use ($gameID, $homeTeamScore, $awayTeamScore, $gameWinnerID) {
            $game = Game::find($gameID);
            $game->home_team_score = $homeTeamScore;
            $game->away_team_score = $awayTeamScore;
            $isDraw = $homeTeamScore !== '' && $awayTeamScore !== '' && $homeTeamScore == $awayTeamScore;
            $game->game_winner_id = ($isDraw && $gameWinnerID) ? (int) $gameWinnerID : null;
            $game->save();

            if ((($homeTeamScore == "") ? -1 : $homeTeamScore) != -1 || (($awayTeamScore == "") ? -1 : $awayTeamScore) != -1) {
                $this->generateMissingResults($gameID);

                if (session('survivalGame') != 0) {
                    $predictionSurvivalController = new PredictionSurvivalController();
                    $predictionSurvivalController->updatePredictionSurvivalGame($gameID);
                }

                $gameOddsController = new GameOddsController();
                $gameOddsController->updateGameOdds($gameID);

                $pointsResultController = app(PointResultController::class);
                $pointsResultController->updateGamePoints($gameID);
                $pointsResultController->recalculateStreaks();
            }
        });

        return response()->json(['success' => true]);
    }

   private function generateMissingResults ($gameID){

        $homeTeamScore = $this->generateMissingScore();
        $awayTeamScore = $this->generateMissingScore();

        $inactiveUserIds = \App\Models\UserSetting::where('active', false)->pluck('user_id');

        $predictionResults = PredictionResult::where('game_id', $gameID)
            ->whereNull('home_team_score')
            ->whereNotIn('user_id', $inactiveUserIds)
            ->lockForUpdate()
            ->get();

        if ($predictionResults->isEmpty()) {
            return;
        }

        $affectedUserIds = [];
        foreach ($predictionResults as $predictionResult){
            $predictionResult->home_team_score = $homeTeamScore;
            $predictionResult->away_team_score = $awayTeamScore;
            $predictionResult->generated = 1;
            $predictionResult->save();
            $affectedUserIds[] = $predictionResult->user_id;
        }

        // Auto-deactivate users who have accumulated 5+ randomly generated predictions
        $generatedCounts = PredictionResult::whereIn('user_id', $affectedUserIds)
            ->where('generated', 1)
            ->groupBy('user_id')
            ->selectRaw('user_id, COUNT(*) as total')
            ->pluck('total', 'user_id');

        $toDeactivate = $generatedCounts->filter(fn($count) => $count >= 5)->keys();

        if ($toDeactivate->isNotEmpty()) {
            \App\Models\UserSetting::whereIn('user_id', $toDeactivate)
                ->update(['active' => false]);
        }
    }

    public function getEventGameUnfinishedCount ($eventID) {
        $gameCount = Game :: where('home_team_score',null)->join('events','games.event_id','=','events.id')->where('event_id',$eventID)->count();
        return $gameCount;
    }

    private function generateMissingScore(){
        return random_int(0, 3);
    }
}
