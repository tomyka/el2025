<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointResult extends Model
{
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'game_id',
        'winner_points',
        'difference_points',
        'bingo_points',
        'odds',
        'odds_points',
        'full_points',
        'streak_bonus',
    ];
}
