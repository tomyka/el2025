<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Team extends Model
{
    protected $primaryKey = 'id';

    protected $fillable = [
        'team',
        'group_name',
        'group_position',
    ];
}

