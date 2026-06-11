<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'name',
        'surname',
        'email',
        'password',
        'google_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function userSetting()
    {
        return $this->hasOne('App\Models\UserSetting','user_id','id');
    }

    public function leagueMembers()
    {
        return $this->hasMany(LeagueMember::class);
    }

    public function ownedLeagues()
    {
        return $this->hasMany(League::class, 'owner_id');
    }

    public function receivedInvites()
    {
        return $this->hasMany(LeagueInvite::class, 'invited_user_id');
    }

    public function sentInvites()
    {
        return $this->hasMany(LeagueInvite::class, 'invited_by_id');
    }
}
