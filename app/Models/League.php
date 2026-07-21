<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class League extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'is_public', 'owner_id',
        'base_fee', 'penalty_step', 'use_league_odds', 'reward_description',
        'tournament_id',
    ];

    protected $attributes = [
        'tournament_id' => 1,
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'use_league_odds' => 'boolean',
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

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

    public function pendingInvites()
    {
        return $this->hasMany(LeagueInvite::class)->where('status', 'pending');
    }

    public function gameOdds()
    {
        return $this->hasMany(LeagueGameOdds::class);
    }
}
