<?php

namespace Tests\Unit;

use App\Models\GameOdds;
use App\Services\ScoringService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ScoringServiceTest extends TestCase
{
    private ScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ScoringService;
    }

    // ── getTablePoints ───────────────────────────────────────────────────────

    public function test_table_points_exact_match_returns_max(): void
    {
        // |0|, 0 → table[0][0 idx=5] = 10 → 10/2 = 5.0
        $this->assertSame(5.0, $this->service->getTablePoints(2, 1, 2, 1, $this->buildLookup()));
    }

    public function test_table_points_null_prediction_returns_zero(): void
    {
        $this->assertSame(0.0, $this->service->getTablePoints(2, 1, null, null, $this->buildLookup()));
    }

    public function test_table_points_off_by_one_home(): void
    {
        // |2-3|=1, away diff=1-1=0 → table[1][5] = 8 → 8/2 = 4.0
        $this->assertSame(4.0, $this->service->getTablePoints(2, 1, 3, 1, $this->buildLookup()));
    }

    public function test_table_points_off_by_one_away(): void
    {
        // |0|, 1-0=1 → table[0][4] = 8 → 8/2 = 4.0
        $this->assertSame(4.0, $this->service->getTablePoints(2, 1, 2, 0, $this->buildLookup()));
    }

    public function test_table_points_negative_score(): void
    {
        // actual 5:0, pred 0:5 — homeSigned=+5 (no flip), awayRaw=0-5=-5 → key "5_-5" → -10/2=-5.0
        $this->assertSame(-5.0, $this->service->getTablePoints(5, 0, 0, 5, $this->buildLookup()));
    }

    public function test_table_points_home_overpredicted_flips_away_sign(): void
    {
        // actual 0:1, pred 1:0 — homeSigned=-1 (flip), awayRaw=+1 → awayDiff=-1 → key "1_-1" → 6/2=3.0
        $this->assertSame(3.0, $this->service->getTablePoints(0, 1, 1, 0, $this->buildLookup()));
    }

    public function test_table_points_uniform_underprediction_equals_overprediction(): void
    {
        // actual 0:0, pred 1:1 — both down by 1 → homeSigned=-1 (flip), awayRaw=-1 → awayDiff=+1 → key "1_1"
        // actual 2:1, pred 1:0 — both up by 1  → homeSigned=+1 (no flip), awayRaw=+1 → awayDiff=+1 → key "1_1"
        $up = $this->service->getTablePoints(2, 1, 1, 0, $this->buildLookup());
        $down = $this->service->getTablePoints(0, 0, 1, 1, $this->buildLookup());
        $this->assertSame($up, $down); // symmetric miss → same score
        $this->assertSame(4.5, $up);   // key "1_1" → 9/2
    }

    public function test_table_points_home_diff_clamped_to_seven(): void
    {
        // actual 10:1, pred 0:1 — homeSigned=+10→7, awayRaw=0 → key "7_0" → -4/2=-2.0
        $this->assertSame(-2.0, $this->service->getTablePoints(10, 1, 0, 1, $this->buildLookup()));
    }

    public function test_table_points_away_diff_clamped_to_seven(): void
    {
        // actual 2:12, pred 2:0 — homeSigned=0 (no flip), awayRaw=12→7 → key "0_7" → -4/2=-2.0
        $this->assertSame(-2.0, $this->service->getTablePoints(2, 12, 2, 0, $this->buildLookup()));
    }

    private function buildLookup(): Collection
    {
        $awayDiffs = [7, 6, 5, 4, 3, 2, 1, 0, -1, -2, -3, -4, -5, -6, -7];
        $tableData = [
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
        foreach ($tableData as $homeDiff => $points) {
            foreach ($awayDiffs as $i => $awayDiff) {
                $key = "{$homeDiff}_{$awayDiff}";
                $rows[$key] = (object) [
                    'home_score_difference' => $homeDiff,
                    'away_score_difference' => $awayDiff,
                    'points' => $points[$i],
                ];
            }
        }

        return collect($rows);
    }

    // ── getWinnerPoints ──────────────────────────────────────────────────────

    public function test_winner_points_home_win_correctly_predicted(): void
    {
        $this->assertSame(50, $this->service->getWinnerPoints(2, 0, 3, 1));
    }

    public function test_winner_points_away_win_correctly_predicted(): void
    {
        $this->assertSame(50, $this->service->getWinnerPoints(0, 2, 1, 3));
    }

    public function test_winner_points_draw_correctly_predicted(): void
    {
        $this->assertSame(50, $this->service->getWinnerPoints(1, 1, 0, 0));
    }

    public function test_winner_points_wrong_direction(): void
    {
        $this->assertSame(0, $this->service->getWinnerPoints(2, 0, 0, 1));
    }

    public function test_winner_points_actual_draw_predicted_home_win(): void
    {
        $this->assertSame(0, $this->service->getWinnerPoints(1, 1, 2, 0));
    }

    public function test_winner_points_actual_home_win_predicted_draw(): void
    {
        $this->assertSame(0, $this->service->getWinnerPoints(2, 1, 0, 0));
    }

    // ── getDifferencePoints ──────────────────────────────────────────────────

    public function test_difference_points_exact_difference(): void
    {
        // actual diff = 2-0 = 2, predicted diff = 3-1 = 2 → |2-2| = 0 → 50 pts
        $this->assertSame(50, $this->service->getDifferencePoints(2, 0, 3, 1));
    }

    public function test_difference_points_off_by_one(): void
    {
        // actual diff = 1, predicted diff = 2 → |1-2| = 1 → 49 pts
        $this->assertSame(49, $this->service->getDifferencePoints(2, 1, 3, 1));
    }

    public function test_difference_points_large_discrepancy(): void
    {
        // actual diff = 0, predicted diff = 5 → |0-5| = 5 → 45 pts
        $this->assertSame(45, $this->service->getDifferencePoints(1, 1, 5, 0));
    }

    // ── getBingoPoints ───────────────────────────────────────────────────────

    public function test_bingo_exact_score(): void
    {
        $this->assertSame(2.5, $this->service->getBingoPoints(2, 1, 2, 1));
    }

    public function test_bingo_same_goal_difference_delta_no_longer_scores(): void
    {
        // same margin but different scores → no bingo bonus
        $this->assertSame(0.0, $this->service->getBingoPoints(2, 0, 3, 1));
    }

    public function test_bingo_no_match(): void
    {
        $this->assertSame(0.0, $this->service->getBingoPoints(2, 1, 0, 2));
    }

    public function test_bingo_zero_zero_exact(): void
    {
        $this->assertSame(2.5, $this->service->getBingoPoints(0, 0, 0, 0));
    }

    // ── getOddsPoints ────────────────────────────────────────────────────────

    public function test_odds_points_winner_correct_with_odds(): void
    {
        $this->assertEqualsWithDelta(10.0, $this->service->getOddsPoints(2.0, 5.0), 0.001);
    }

    public function test_odds_points_no_bonus_when_winner_wrong(): void
    {
        $this->assertSame(0.0, $this->service->getOddsPoints(1.75, 0));
    }

    public function test_odds_points_odds_of_zero_gives_zero_bonus(): void
    {
        // odds = log₂(1/1) = 0 when everyone picked this outcome
        $this->assertSame(0.0, $this->service->getOddsPoints(0.0, 5.0));
    }

    // ── getGameOdds ──────────────────────────────────────────────────────────

    public function test_game_odds_generated_always_returns_one(): void
    {
        $gameOdds = new GameOdds;
        $gameOdds->home_odds = 1.9;
        $gameOdds->away_odds = 2.1;

        $this->assertSame(0.0, $this->service->getGameOdds(3, 1, $gameOdds, 1));
    }

    public function test_game_odds_home_prediction_returns_home_odds(): void
    {
        $gameOdds = new GameOdds;
        $gameOdds->home_odds = 1.9;
        $gameOdds->away_odds = 2.1;

        $this->assertSame(1.9, $this->service->getGameOdds(2, 0, $gameOdds, 0));
    }

    public function test_game_odds_away_prediction_returns_away_odds(): void
    {
        $gameOdds = new GameOdds;
        $gameOdds->home_odds = 1.9;
        $gameOdds->away_odds = 2.1;

        $this->assertSame(2.1, $this->service->getGameOdds(0, 2, $gameOdds, 0));
    }

    public function test_game_odds_draw_prediction_returns_draw_odds(): void
    {
        $gameOdds = new GameOdds;
        $gameOdds->home_odds = 1.9;
        $gameOdds->draw_odds = 3.2;
        $gameOdds->away_odds = 2.1;

        $this->assertSame(3.2, $this->service->getGameOdds(0, 0, $gameOdds, 0));
        $this->assertSame(3.2, $this->service->getGameOdds(1, 1, $gameOdds, 0));
    }

    // ── calculateGamePoints ──────────────────────────────────────────────────

    public function test_calculate_game_points_applies_rate(): void
    {
        $points = $this->service->calculateGamePoints(50, 50, 25.0, 50, 1.5, 2.0);

        $this->assertEqualsWithDelta(100.0, $points->winnerPoints, 0.001);
        $this->assertEqualsWithDelta(100.0, $points->differencePoints, 0.001);
        $this->assertEqualsWithDelta(100.0, $points->bingoPoints, 0.001);
        $this->assertEqualsWithDelta(50.0, $points->oddsPoints, 0.001);
        $this->assertEqualsWithDelta(350.0, $points->fullPoints, 0.001);
        $this->assertSame(1.5, $points->odds);
    }

    public function test_calculate_game_points_full_correct_prediction(): void
    {
        // rate=1, winner=50, diff=50, odds bonus=25 (odds=1.5), bingo=50
        $points = $this->service->calculateGamePoints(50, 50, 25.0, 50, 1.5, 1.0);

        $this->assertEqualsWithDelta(175.0, $points->fullPoints, 0.001);
    }
}
