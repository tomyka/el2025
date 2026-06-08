<?php

namespace Tests\Unit;

use App\Models\GameOdds;
use App\Services\ScoringService;
use Tests\TestCase;

class ScoringServiceTest extends TestCase
{
    private ScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ScoringService();
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
        $this->assertSame(50, $this->service->getBingoPoints(2, 1, 2, 1));
    }

    public function test_bingo_same_goal_difference_delta(): void
    {
        // home-prediction delta = 2-3 = -1, away-prediction delta = 0-1 = -1 → 20 pts
        $this->assertSame(20, $this->service->getBingoPoints(2, 0, 3, 1));
    }

    public function test_bingo_no_match(): void
    {
        $this->assertSame(0, $this->service->getBingoPoints(2, 1, 0, 2));
    }

    public function test_bingo_exact_takes_priority_over_delta(): void
    {
        // 0-0 vs 0-0 is exact match (50), not just delta match
        $this->assertSame(50, $this->service->getBingoPoints(0, 0, 0, 0));
    }

    // ── getOddsPoints ────────────────────────────────────────────────────────

    public function test_odds_points_winner_correct_with_odds(): void
    {
        $this->assertEqualsWithDelta(37.5, $this->service->getOddsPoints(1.75, 50), 0.001);
    }

    public function test_odds_points_no_bonus_when_winner_wrong(): void
    {
        $this->assertSame(0.0, $this->service->getOddsPoints(1.75, 0));
    }

    public function test_odds_points_odds_of_one_gives_zero_bonus(): void
    {
        $this->assertSame(0.0, $this->service->getOddsPoints(1.0, 50));
    }

    // ── getGameOdds ──────────────────────────────────────────────────────────

    public function test_game_odds_generated_always_returns_one(): void
    {
        $gameOdds             = new GameOdds();
        $gameOdds->home_odds  = 1.9;
        $gameOdds->away_odds  = 2.1;

        $this->assertSame(1.0, $this->service->getGameOdds(3, 1, $gameOdds, 1));
    }

    public function test_game_odds_home_prediction_returns_home_odds(): void
    {
        $gameOdds             = new GameOdds();
        $gameOdds->home_odds  = 1.9;
        $gameOdds->away_odds  = 2.1;

        $this->assertSame(1.9, $this->service->getGameOdds(2, 0, $gameOdds, 0));
    }

    public function test_game_odds_away_prediction_returns_away_odds(): void
    {
        $gameOdds             = new GameOdds();
        $gameOdds->home_odds  = 1.9;
        $gameOdds->away_odds  = 2.1;

        $this->assertSame(2.1, $this->service->getGameOdds(0, 2, $gameOdds, 0));
    }

    // ── calculateGamePoints ──────────────────────────────────────────────────

    public function test_calculate_game_points_applies_rate(): void
    {
        $points = $this->service->calculateGamePoints(50, 50, 25.0, 50, 1.5, 2.0);

        $this->assertEqualsWithDelta(100.0, $points->winnerPoints, 0.001);
        $this->assertEqualsWithDelta(100.0, $points->differencePoints, 0.001);
        $this->assertEqualsWithDelta(100.0, $points->bingoPoints, 0.001);
        $this->assertEqualsWithDelta(50.0,  $points->oddsPoints, 0.001);
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
