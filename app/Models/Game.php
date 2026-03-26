<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $primaryKey = 'id';

    protected $fillable = [
        'game_date',
        'event_id',
        'home_team_id',
        'away_team_id',
        'home_team_score',
        'away_team_score',
    ];

    public function home_team()
    {
        return $this->hasOne('App\Models\Team','id', 'home_team_id');
    }

    public function away_team()
    {
        return $this->hasOne('App\Models\Team','id', 'away_team_id');
    }

    public function event()
    {
        return $this->hasOne('App\Models\Event','id', 'event_id');
    }
}
