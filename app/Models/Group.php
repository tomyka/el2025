<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'group',
        'group_description',
        'fee',
        'reward_ratio',
        'reward_description',
    ];

    protected $primaryKey = 'id';

}
