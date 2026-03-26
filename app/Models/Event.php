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
        'active',
        'rate',
    ];
}
