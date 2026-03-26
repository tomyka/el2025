<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class UserSetting extends Model
{
    protected $primaryKey = 'id';

    protected $fillable = [
        'admin', 'fee', 'color_id'
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id','id');
    }

}
