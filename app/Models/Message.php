<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $primaryKey = 'id';

    protected $fillable = ['message', 'active', 'group_id'];
}
