<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('points_calculations')->delete();

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

    public function down(): void
    {
        DB::table('points_calculations')->delete();
    }
};
