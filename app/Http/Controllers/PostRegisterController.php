<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\LeagueMember;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;

class PostRegisterController extends Controller
{
    public function postRegisterActions(int $userID): RedirectResponse
    {
        $userSettingsController = new UserSettingController;
        $userSettingsController->insertUserSettings($userID);

        $tournament = $this->resolveIntendedTournament();
        Session::forget('intended_tournament');

        // Auto-join the tournament's public league, creating it if this
        // tournament somehow doesn't have one yet (defensive - normally
        // created alongside the tournament by TournamentController::adminStore).
        $publicLeague = League::firstOrCreate(
            ['tournament_id' => $tournament->id, 'is_public' => true],
            ['name' => $tournament->name]
        );
        LeagueMember::create([
            'league_id' => $publicLeague->id,
            'user_id' => $userID,
            'is_admin' => false,
            'is_guest' => false,
            'active' => true,
        ]);

        $predictionResultController = new PredictionResultController;
        $predictionResultController->insertPredictionResultsUser($userID);

        $predictionStandingController = new PredictionStandingController;
        $predictionStandingController->insertPredictionStandingsUser($userID);

        $predictionSurvivalController = new PredictionSurvivalController;
        $predictionSurvivalController->insertPredictionSurvivalUser($userID);

        return redirect(route('main', absolute: false));
    }

    /**
     * The tournament a fresh registration should join: whichever one the user
     * arrived from (session('intended_tournament'), set when they hit
     * login/register with a ?tournament=slug), falling back to the currently
     * active tournament for direct/unscoped registrations.
     */
    private function resolveIntendedTournament(): Tournament
    {
        $slug = Session::get('intended_tournament');
        if ($slug) {
            $tournament = Tournament::where('slug', $slug)->first();
            if ($tournament) {
                return $tournament;
            }
        }

        return Tournament::where('status', 'active')->orderByDesc('created_at')->orderByDesc('id')->first()
            ?? Tournament::orderByDesc('created_at')->orderByDesc('id')->firstOrFail();
    }
}
