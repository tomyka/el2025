<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    protected $fillable = [
        'name', 'slug', 'sport', 'status',
        'start_date', 'end_date', 'description',
        'cover_image', 'is_public', 'survival_game',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'survival_game' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function leagues()
    {
        return $this->hasMany(League::class);
    }
}
