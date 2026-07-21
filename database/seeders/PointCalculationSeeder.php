<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PointCalculationSeeder extends Seeder
{
    // Points table: row = |actual_home - pred_home| (0–7)
    // col = signed away difference (−7 to +7, sign-flip adjusted)
    // Values stored ×2 to preserve 0.5-point increments.
    //
    // Formula: col >= row → 10 + row − 2·col
    //          0 ≤ col < row → (10 − 2·row) + col
    //          col < 0       → (10 − 2·row) + 2·col

    public function run(): void
    {
        $awayDiffs = [7, 6, 5, 4, 3, 2, 1, 0, -1, -2, -3, -4, -5, -6, -7];

        $table = [
            0 => [-4,  -2,   0,   2,   4,   6,   8,  10,   8,   6,   4,   2,   0,  -2,  -4],
            1 => [-3,  -1,   1,   3,   5,   7,   9,   8,   6,   4,   2,   0,  -2,  -4,  -6],
            2 => [-2,   0,   2,   4,   6,   8,   7,   6,   4,   2,   0,  -2,  -4,  -6,  -8],
            3 => [-1,   1,   3,   5,   7,   6,   5,   4,   2,   0,  -2,  -4,  -6,  -8, -10],
            4 => [0,   2,   4,   6,   5,   4,   3,   2,   0,  -2,  -4,  -6,  -8, -10, -12],
            5 => [1,   3,   5,   4,   3,   2,   1,   0,  -2,  -4,  -6,  -8, -10, -12, -14],
            6 => [2,   4,   3,   2,   1,   0,  -1,  -2,  -4,  -6,  -8, -10, -12, -14, -16],
            7 => [3,   2,   1,   0,  -1,  -2,  -3,  -4,  -6,  -8, -10, -12, -14, -16, -18],
        ];

        $rows = [];
        $now = now();

        foreach ($table as $homeDiff => $points) {
            foreach ($awayDiffs as $i => $awayDiff) {
                $rows[] = [
                    'home_score_difference' => $homeDiff,
                    'away_score_difference' => $awayDiff,
                    'points' => $points[$i],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('points_calculations')->insert($rows);
    }
}
