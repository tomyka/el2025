<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointsCalculation extends Model
{
    protected $fillable = ['home_score_difference', 'away_score_difference', 'points'];
}
