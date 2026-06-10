<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeagueMember extends Model
{
    use HasFactory;

    protected $fillable = ['league_id', 'user_id', 'is_admin', 'is_guest', 'active'];

    protected $casts = [
        'is_admin' => 'boolean',
        'is_guest' => 'boolean',
        'active'   => 'boolean',
    ];

    public function league()
    {
        return $this->belongsTo(League::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
