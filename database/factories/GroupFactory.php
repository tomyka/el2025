<?php

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'group' => $this->faker->word(),
            'group_description' => $this->faker->sentence(),
            'fee' => $this->faker->numberBetween(0, 100),
            'reward_ratio' => $this->faker->randomFloat(2, 0, 10),
            'reward_description' => $this->faker->sentence(),
        ];
    }
}
