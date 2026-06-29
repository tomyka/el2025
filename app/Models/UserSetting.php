<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id', 'admin', 'fee', 'color_id', 'receive_reminders', 'hidden'
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id','id');
    }

}
