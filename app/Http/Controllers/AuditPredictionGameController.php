<?php

namespace App\Http\Controllers;

use App\Models\AuditPredictionGame;



class AuditPredictionGameController extends Controller
{
    public function insertAuditPredictionGame($userID, $gameID, $homeTeamScore, $awayTeamScore, $gameWinnerID): void
    {
        $auditPredictionGame = new AuditPredictionGame();
        $auditPredictionGame->user_id = $userID;
        $auditPredictionGame->game_id = $gameID;
        $auditPredictionGame->home_team_score = $homeTeamScore;
        $auditPredictionGame->away_team_score = $awayTeamScore;
        $auditPredictionGame->game_winner_id = $gameWinnerID;
        $auditPredictionGame->save();
    }

}
