<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeagueMember extends Model
{
    protected $fillable = ['league_id', 'user_id', 'is_admin', 'is_guest', 'active'];

    public function league()
    {
        return $this->belongsTo(League::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
