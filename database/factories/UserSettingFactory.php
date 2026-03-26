<?php

namespace Database\Factories;

use App\Models\UserSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserSetting>
 */
class UserSettingFactory extends Factory
{
    protected $model = UserSetting::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'admin' => $this->faker->boolean(20),
            'result_amount' => $this->faker->numberBetween(0, 10),
            'time_zone' => $this->faker->numberBetween(0, 24),
        ];
    }
}
