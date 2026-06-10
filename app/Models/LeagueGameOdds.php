<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeagueGameOdds extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = ['league_id', 'game_id', 'home_odds', 'draw_odds', 'away_odds'];

    public function league()
    {
        return $this->belongsTo(League::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
