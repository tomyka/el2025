<?php

namespace App\Services;

class StreakService
{
    public static function bonus(int $streakLength, float $rate): float
    {
        return max(0, $streakLength - 1) * $rate;
    }

    public static function length(float $streakBonus, float $rate): int
    {
        if ($rate <= 0.0) {
            return 1;
        }

        return (int) round($streakBonus / $rate) + 1;
    }
}
