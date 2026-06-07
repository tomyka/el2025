<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class PredictionStanding extends Model
{
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'team_id',
        'group_position',
        'last32',
        'last16',
        'quarterfinal',
        'semifinal',
        'final',
    ];
}
