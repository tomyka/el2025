<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeagueGameOdds extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    // Composite PK (league_id, game_id) — Eloquent doesn't support composite PKs.
    // Use DB::table('league_game_odds') for all writes; this model is for reads only.
    protected $primaryKey = 'league_id';

    protected $fillable = ['league_id', 'game_id', 'home_odds', 'draw_odds', 'away_odds'];

    protected $casts = [
        'home_odds' => 'decimal:2',
        'draw_odds' => 'decimal:2',
        'away_odds' => 'decimal:2',
    ];

    public function league()
    {
        return $this->belongsTo(League::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
