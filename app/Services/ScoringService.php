<?php

namespace App\Services;

use App\Models\GameOdds;
use Illuminate\Support\Collection;
use stdClass;

class ScoringService
{
    /**
     * Look up the base score from the points_calculations table.
     *
     * $lookup must be keyed by "{home_score_difference}_{away_score_difference}".
     * Table values are stored ×2 to support 0.5-point increments; this method
     * divides by 2 before returning.
     */
    public function getTablePoints(
        int $homeActual,
        int $awayActual,
        mixed $homePred,
        mixed $awayPred,
        Collection $lookup
    ): float {
        if ($homePred === null || $awayPred === null) {
            return 0.0;
        }

        $homeSignedDiff = $homeActual - (int) $homePred;
        $homeDiff       = min(abs($homeSignedDiff), 7);
        $awayRawDiff    = $awayActual - (int) $awayPred;
        // Flip away direction when home was overpredicted so uniform misses
        // (both scores off by the same amount) always map to positive col values.
        $awayDiff = max(-7, min(7, $homeSignedDiff < 0 ? -$awayRawDiff : $awayRawDiff));
        $key      = "{$homeDiff}_{$awayDiff}";

        $row = $lookup->get($key);

        return $row !== null ? (float) $row->points / 2.0 : 0.0;
    }

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
     * @deprecated Replaced by getTablePoints(); kept for reference only.
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
     * 2.5 pts for an exact score prediction; 0 otherwise.
     */
    public function getBingoPoints(
        int $homeScore,
        int $awayScore,
        int $homeScorePrediction,
        int $awayScorePrediction
    ): float {
        return ($homeScore === $homeScorePrediction && $awayScore === $awayScorePrediction)
            ? 2.5
            : 0.0;
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
            return 0.0;
        }

        if ($homeScorePrediction > $awayScorePrediction) return (float) $gameOdds->home_odds;
        if ($homeScorePrediction < $awayScorePrediction) return (float) $gameOdds->away_odds;
        return (float) $gameOdds->draw_odds;
    }

    /**
     * Odds bonus: winner_points × log₂(1/fraction), only when the winner was correctly called.
     * odds is stored as log₂(total/votes), so this is simply winner_points × odds.
     */
    public function getOddsPoints(float $odds, float $winnerPoints): float
    {
        return $winnerPoints > 0 ? $winnerPoints * $odds : 0.0;
    }

    /**
     * Apply the event rate multiplier and return a point breakdown object.
     */
    public function calculateGamePoints(
        float $winnerPoints,
        float $differencePoints,
        float $oddsPoints,
        float $bingoPoints,
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
