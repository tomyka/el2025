<?php

namespace App\Http\Controllers;

class PostRegisterController extends Controller
{
   public function postRegisterActions($userID){

        $fee = 0;
        $guest = 0;
        $active = 1;
        $default_user_group = 1;

        // Ensure default group exists to avoid FK errors in tests and fresh databases.
        if (!\App\Models\Group::find($default_user_group)) {
            \App\Models\Group::factory()->create([
                'id' => $default_user_group,
                'group' => 'Default',
                'group_description' => 'Default group',
                'fee' => 0,
                'reward_ratio' => 0,
                'reward_description' => 'Default',
            ]);
        }

        $userSettingsController = new UserSettingController();
        $userSettingsController->insertUserSettings($userID);

        $userGroupController = new UserGroupController();
        $userGroupController->insertNewUserIntoGroup($userID, $default_user_group, $active, $fee, $guest);

        $predictionResultController = new PredictionResultController();
        $predictionResultController->insertPredictionResultsUser($userID);

        $predictionStandingController = new PredictionStandingController();
        $predictionStandingController->insertPredictionStandingsUser($userID);

        $predictionSurvivalController = new PredictionSurvivalController();
        $predictionSurvivalController->insertPredictionSurvivalUser($userID);


       return redirect(route('main', absolute: false));

    }
}
