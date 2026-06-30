<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $primaryKey = 'id';

    protected $fillable = [
        'event',
        'event_day',
        'event_survival',
        'is_knockout',
        'active',
        'rate',
        'tournament_id',
    ];

    protected $attributes = [
        'tournament_id' => 1,
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }
}
