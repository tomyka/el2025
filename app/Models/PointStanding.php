<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class PointStanding extends Model
{
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'team_id',
        'group_position_points',
        'last32_points',
        'last16_points',
        'quarterfinal_points',
        'semifinal_points',
        'final_points',
    ];
}
