<?php

namespace App\Services;

class StandingScoringService
{
    public function calculateGroupPositionPoints(
        int  $teamPosition,
        ?int $predictedPosition
    ): int {
        if ($teamPosition === 0 || $predictedPosition === null) {
            return 0;
        }

        return max(0, 3 - abs($teamPosition - $predictedPosition));
    }

    /**
     * Fixed point award when both team and prediction flag match (team advanced & correctly predicted).
     */
    public function calculateKnockoutPoints(int $teamResult, ?int $predictedResult, int $points): int
    {
        return ($teamResult === 1 && $predictedResult === 1) ? $points : 0;
    }

    /**
     * Finals position scoring matrix (row = predicted position, col = actual position).
     */
    public function calculateFinalPoints(?int $teamFinal, ?int $predictedFinal): int
    {
        if ($teamFinal === null || $predictedFinal === null) {
            return 0;
        }

        $matrix = [
            1 => [1 => 36, 2 => 27, 3 => 18, 4 => 9],
            2 => [1 => 27, 2 => 30, 3 => 21, 4 => 12],
            3 => [1 => 18, 2 => 21, 3 => 24, 4 => 15],
            4 => [1 => 9,  2 => 12, 3 => 15, 4 => 18],
        ];

        return $matrix[$predictedFinal][$teamFinal] ?? 0;
    }
}
