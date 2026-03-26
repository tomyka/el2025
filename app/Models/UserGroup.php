<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id','id');
    }

    public function group()
    {
        return $this->belongsTo('App\Models\Group','group_id','id');
    }

}
