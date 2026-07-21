<?php

namespace Tests\Unit;

use App\Services\StandingScoringService;
use Tests\TestCase;

class StandingScoringServiceTest extends TestCase
{
    private StandingScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StandingScoringService;
    }

    // ── calculateGroupPositionPoints ─────────────────────────────────────────

    public function test_group_position_exact_match(): void
    {
        // |1 - 1| = 0, (48 - 1 - 0) * 10 = 470
        $this->assertSame(470, $this->service->calculateGroupPositionPoints(1, 1, 48));
    }

    public function test_group_position_off_by_one(): void
    {
        // |1 - 2| = 1, (48 - 1 - 1) * 10 = 460
        $this->assertSame(460, $this->service->calculateGroupPositionPoints(1, 2, 48));
    }

    public function test_group_position_max_difference(): void
    {
        // |1 - 4| = 3, (48 - 1 - 3) * 10 = 440
        $this->assertSame(440, $this->service->calculateGroupPositionPoints(1, 4, 48));
    }

    public function test_group_position_zero_returns_zero(): void
    {
        $this->assertSame(0, $this->service->calculateGroupPositionPoints(0, 1, 48));
    }

    public function test_group_position_null_prediction_returns_zero(): void
    {
        $this->assertSame(0, $this->service->calculateGroupPositionPoints(1, null, 48));
    }

    // ── calculateKnockoutPoints ──────────────────────────────────────────────

    public function test_knockout_both_one_returns_award(): void
    {
        $this->assertSame(60, $this->service->calculateKnockoutPoints(1, 1, 60));
    }

    public function test_knockout_team_advanced_not_predicted(): void
    {
        $this->assertSame(0, $this->service->calculateKnockoutPoints(1, 0, 60));
    }

    public function test_knockout_not_advanced_but_predicted(): void
    {
        $this->assertSame(0, $this->service->calculateKnockoutPoints(0, 1, 60));
    }

    public function test_knockout_null_prediction_returns_zero(): void
    {
        $this->assertSame(0, $this->service->calculateKnockoutPoints(1, null, 60));
    }

    public function test_knockout_award_amounts_are_respected(): void
    {
        $this->assertSame(40, $this->service->calculateKnockoutPoints(1, 1, 40));
        $this->assertSame(90, $this->service->calculateKnockoutPoints(1, 1, 90));
        $this->assertSame(120, $this->service->calculateKnockoutPoints(1, 1, 120));
    }

    // ── calculateFinalPoints ─────────────────────────────────────────────────

    public function test_final_points_champion_correctly_predicted(): void
    {
        $this->assertSame(36, $this->service->calculateFinalPoints(1, 1));
    }

    public function test_final_points_runner_up_correctly_predicted(): void
    {
        $this->assertSame(30, $this->service->calculateFinalPoints(2, 2));
    }

    public function test_final_points_third_correctly_predicted(): void
    {
        $this->assertSame(24, $this->service->calculateFinalPoints(3, 3));
    }

    public function test_final_points_fourth_correctly_predicted(): void
    {
        $this->assertSame(18, $this->service->calculateFinalPoints(4, 4));
    }

    public function test_final_points_champion_predicted_runner_up(): void
    {
        // teamFinal=1, predictedFinal=2 → 27
        $this->assertSame(27, $this->service->calculateFinalPoints(1, 2));
    }

    public function test_final_points_null_inputs_return_zero(): void
    {
        $this->assertSame(0, $this->service->calculateFinalPoints(null, 1));
        $this->assertSame(0, $this->service->calculateFinalPoints(1, null));
    }

    public function test_final_points_all_combinations_are_positive(): void
    {
        foreach (range(1, 4) as $actual) {
            foreach (range(1, 4) as $predicted) {
                $points = $this->service->calculateFinalPoints($actual, $predicted);
                $this->assertGreaterThan(0, $points, "Expected positive for actual=$actual predicted=$predicted");
            }
        }
    }
}
