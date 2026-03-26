<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class PredictionResult extends Model
{
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'game_id',
        'home_team_score',
        'away_team_score',
    ];
}
