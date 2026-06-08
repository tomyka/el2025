<?php

namespace App\Services;

use App\Models\GameOdds;
use stdClass;

class ScoringService
{
    /**
     * 50 pts if the predicted winner/draw direction matches the actual result.
     */
    public function getWinnerPoints(
        int $homeScore,
        int $awayScore,
        int $homeScorePrediction,
        int $awayScorePrediction
    ): int {
        $homeWin  = ($homeScore > $awayScore)  && ($homeScorePrediction > $awayScorePrediction);
        $awayWin  = ($homeScore < $awayScore)  && ($homeScorePrediction < $awayScorePrediction);
        $draw     = ($homeScore === $awayScore) && ($homeScorePrediction === $awayScorePrediction);

        return ($homeWin || $awayWin || $draw) ? 50 : 0;
    }

    /**
     * 50 minus the absolute difference between actual and predicted goal-difference.
     */
    public function getDifferencePoints(
        int $homeScore,
        int $awayScore,
        int $homeScorePrediction,
        int $awayScorePrediction
    ): int {
        $actualDiff    = $homeScore - $awayScore;
        $predictedDiff = $homeScorePrediction - $awayScorePrediction;

        return 50 - abs($actualDiff - $predictedDiff);
    }

    /**
     * 50 pts for exact score; 20 pts if the goal-difference delta is zero (same margin, different scores).
     */
    public function getBingoPoints(
        int $homeScore,
        int $awayScore,
        int $homeScorePrediction,
        int $awayScorePrediction
    ): int {
        if ($homeScore === $homeScorePrediction && $awayScore === $awayScorePrediction) {
            return 50;
        }

        if (($homeScore - $homeScorePrediction) === ($awayScore - $awayScorePrediction)) {
            return 20;
        }

        return 0;
    }

    /**
     * Pick the applicable odds value from the stored GameOdds row.
     * Generated predictions always receive odds = 1 (no bonus).
     */
    public function getGameOdds(
        int $homeScorePrediction,
        int $awayScorePrediction,
        GameOdds $gameOdds,
        mixed $generated
    ): float {
        if ((int) $generated === 1) {
            return 1.0;
        }

        return $homeScorePrediction > $awayScorePrediction
            ? (float) $gameOdds->home_odds
            : (float) $gameOdds->away_odds;
    }

    /**
     * Odds bonus: winner_points × (odds − 1), only when the winner was correctly called.
     */
    public function getOddsPoints(float $odds, int $winnerPoints): float
    {
        return $winnerPoints === 50 ? $winnerPoints * ($odds - 1) : 0.0;
    }

    /**
     * Apply the event rate multiplier and return a point breakdown object.
     */
    public function calculateGamePoints(
        int   $winnerPoints,
        int   $differencePoints,
        float $oddsPoints,
        int   $bingoPoints,
        float $odds,
        float $rate
    ): stdClass {
        $points                    = new stdClass();
        $points->winnerPoints      = $winnerPoints * $rate;
        $points->differencePoints  = $differencePoints * $rate;
        $points->bingoPoints       = $bingoPoints * $rate;
        $points->oddsPoints        = $oddsPoints * $rate;
        $points->odds              = $odds;
        $points->fullPoints        = $points->winnerPoints
            + $points->differencePoints
            + $points->bingoPoints
            + $points->oddsPoints;

        return $points;
    }
}
