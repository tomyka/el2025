<?php

namespace Database\Factories;

use App\Models\League;
use App\Models\LeagueMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeagueMember>
 */
class LeagueMemberFactory extends Factory
{
    protected $model = LeagueMember::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'league_id' => League::factory(),
            'active' => true,
            'is_guest' => false,
            'is_admin' => false,
        ];
    }
}
