<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class League extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'is_public', 'owner_id',
        'base_fee', 'penalty_step', 'use_league_odds', 'reward_description',
    ];

    protected $casts = [
        'is_public'       => 'boolean',
        'use_league_odds' => 'boolean',
    ];

    public function members()
    {
        return $this->hasMany(LeagueMember::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function invites()
    {
        return $this->hasMany(LeagueInvite::class);
    }

    public function gameOdds()
    {
        return $this->hasMany(LeagueGameOdds::class);
    }
}
