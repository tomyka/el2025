<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeagueInvite extends Model
{
    protected $fillable = ['league_id', 'invited_user_id', 'invited_by_id', 'status'];

    public function league()
    {
        return $this->belongsTo(League::class);
    }

    public function invitedUser()
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }

    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }
}
