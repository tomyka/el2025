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

    public function recalculateAllGamePoints()
    {
        if (!session('admin')) {
            abort(403);
        }

        $games = Game::whereNotNull('home_team_score')
            ->whereNotNull('away_team_score')
            ->pluck('id');

        $pointsResultController = app(PointResultController::class);

        foreach ($games as $gameID) {
            DB::transaction(function () use ($gameID, $pointsResultController) {
                $pointsResultController->updateGamePoints($gameID);
            });
        }

        $pointsResultController->recalculateStreaks();

        return redirect()->route('admin.results')->with('info', __('Visi taškų rezultatai perskaičiuoti.'));
    }

    private function generateMissingResults(int $gameID): void
    {
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
        $upsertRows = [];
        foreach ($predictionResults as $predictionResult) {
            $upsertRows[] = [
                'id'              => $predictionResult->id,
                'user_id'         => $predictionResult->user_id,
                'game_id'         => $predictionResult->game_id,
                'home_team_score' => $this->generateMissingScore(),
                'away_team_score' => $this->generateMissingScore(),
                'generated'       => 1,
            ];
            $affectedUserIds[] = $predictionResult->user_id;
        }
        PredictionResult::upsert($upsertRows, ['id'], ['home_team_score', 'away_team_score', 'generated']);

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
