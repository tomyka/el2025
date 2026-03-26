<?php

namespace App\Http\Controllers;

class PostRegisterController extends Controller
{
   public function postRegisterActions($userID){

        $fee = 0;
        $guest = 0;
        $active = 1;
        $default_user_group = 1;

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
