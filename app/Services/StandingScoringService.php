<?php

namespace App\Services;

class StandingScoringService
{
    /**
     * Points based on how close the predicted group position is to the actual one.
     * Formula: (numberOfTeams − 1 − |actual − predicted|) × 10, minimum 0.
     */
    public function calculateGroupPositionPoints(
        int  $teamPosition,
        ?int $predictedPosition,
        int  $numberOfTeams
    ): int {
        if ($teamPosition === 0 || $predictedPosition === null) {
            return 0;
        }

        return max(0, ($numberOfTeams - 1 - abs($teamPosition - $predictedPosition)) * 10);
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
            1 => [1 => 720, 2 => 480, 3 => 240, 4 => 240],
            2 => [1 => 480, 2 => 600, 3 => 360, 4 => 360],
            3 => [1 => 240, 2 => 360, 3 => 420, 4 => 420],
            4 => [1 => 240, 2 => 360, 3 => 420, 4 => 420],
        ];

        return $matrix[$predictedFinal][$teamFinal] ?? 0;
    }
}
