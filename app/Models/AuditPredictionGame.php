<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditPredictionGame extends Model
{
    protected $fillable = [
        'user_id', 'game_id',
        'home_team_score', 'away_team_score', 'game_winner_id',
        'old_home_team_score', 'old_away_team_score', 'old_game_winner_id',
    ];
}
