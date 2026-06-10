<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\LeagueMember;

class PostRegisterController extends Controller
{
    public function postRegisterActions(int $userID): \Illuminate\Http\RedirectResponse
    {
        $userSettingsController = new UserSettingController();
        $userSettingsController->insertUserSettings($userID);

        // Auto-join the public league (created in data migration)
        $publicLeague = League::where('is_public', true)->firstOrFail();
        LeagueMember::create([
            'league_id' => $publicLeague->id,
            'user_id'   => $userID,
            'is_admin'  => false,
            'is_guest'  => false,
            'active'    => true,
        ]);

        $predictionResultController = new PredictionResultController();
        $predictionResultController->insertPredictionResultsUser($userID);

        $predictionStandingController = new PredictionStandingController();
        $predictionStandingController->insertPredictionStandingsUser($userID);

        $predictionSurvivalController = new PredictionSurvivalController();
        $predictionSurvivalController->insertPredictionSurvivalUser($userID);

        return redirect(route('main', absolute: false));
    }
}
