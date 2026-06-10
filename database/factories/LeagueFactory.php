<?php

namespace Database\Factories;

use App\Models\League;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\League>
 */
class LeagueFactory extends Factory
{
    protected $model = League::class;

    public function definition(): array
    {
        return [
            'name'               => $this->faker->words(3, true),
            'description'        => $this->faker->sentence(),
            'is_public'          => false,
            'base_fee'           => $this->faker->numberBetween(0, 100),
            'penalty_step'       => $this->faker->numberBetween(0, 20),
            'reward_description' => $this->faker->sentence(),
            'use_league_odds'    => false,
        ];
    }
}
