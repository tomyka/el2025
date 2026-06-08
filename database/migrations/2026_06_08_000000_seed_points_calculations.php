<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('points_calculations')->exists()) {
            return;
        }

        $awayDiffs = [5, 4, 3, 2, 1, 0, -1, -2, -3, -4, -5];

        $table = [
            0 => [ 0,  2,  4,  6,  8, 10,  8,  6,  4,  2,  0],
            1 => [ 1,  3,  5,  7,  9,  8,  6,  4,  2,  0, -2],
            2 => [ 2,  4,  6,  8,  7,  6,  4,  2,  0, -2, -4],
            3 => [ 3,  5,  7,  6,  5,  4,  2,  0, -2, -4, -6],
            4 => [ 4,  6,  5,  4,  3,  2,  0, -2, -4, -6, -8],
            5 => [ 5,  4,  3,  2,  1,  0, -2, -4, -6, -8,-10],
        ];

        $rows = [];
        $now  = now();

        foreach ($table as $homeDiff => $points) {
            foreach ($awayDiffs as $i => $awayDiff) {
                $rows[] = [
                    'home_score_difference' => $homeDiff,
                    'away_score_difference' => $awayDiff,
                    'points'               => $points[$i],
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ];
            }
        }

        DB::table('points_calculations')->insert($rows);
    }

    public function down(): void
    {
        DB::table('points_calculations')->truncate();
    }
};
