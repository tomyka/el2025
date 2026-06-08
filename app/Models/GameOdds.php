<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameOdds extends Model
{
    protected $primaryKey = 'id';

    protected $fillable = ['game_id', 'home_odds', 'draw_odds', 'away_odds'];
}
