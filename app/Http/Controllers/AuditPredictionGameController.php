<?php

namespace App\Http\Controllers;

use App\Models\AuditPredictionGame;

class AuditPredictionGameController extends Controller
{
    public function insertAuditPredictionGame(
        int $userID,
        int $gameID,
        string $homeTeamScore,
        string $awayTeamScore,
        string|null $gameWinnerID,
        ?int $oldHomeTeamScore = null,
        ?int $oldAwayTeamScore = null,
        ?int $oldGameWinnerId = null
    ): void {
        $audit = new AuditPredictionGame();
        $audit->user_id             = $userID;
        $audit->game_id             = $gameID;
        $audit->home_team_score     = $homeTeamScore;
        $audit->away_team_score     = $awayTeamScore;
        $audit->game_winner_id      = $gameWinnerID;
        $audit->old_home_team_score = $oldHomeTeamScore;
        $audit->old_away_team_score = $oldAwayTeamScore;
        $audit->old_game_winner_id  = $oldGameWinnerId;
        $audit->save();
    }
}
