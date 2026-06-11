<?php

namespace Tests\Feature;

use App\Http\Controllers\PointResultController;
use App\Http\Controllers\PointStandingController;
use App\Http\Controllers\PointSurvivalController;
use App\Models\Event;
use App\Models\Game;
use App\Models\League;
use App\Models\LeagueMember;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BulkPointsTest extends TestCase
{
    use RefreshDatabase;

    private League $league;

    protected function setUp(): void
    {
        parent::setUp();
        $this->league = League::factory()->create(['use_league_odds' => false]);
    }

    private function makeUser(): User
    {
        $user = User::factory()->create();
        LeagueMember::factory()->create([
            'user_id'   => $user->id,
            'league_id' => $this->league->id,
            'is_guest'  => false,
        ]);
        return $user;
    }

    private function makeGame(): Game
    {
        $event = Event::create([
            'event' => 'Test', 'event_day' => 1,
            'event_survival' => 0, 'active' => 1, 'rate' => 1,
        ]);
        $home = Team::create(['team' => 'H' . uniqid()]);
        $away = Team::create(['team' => 'A' . uniqid()]);
        return Game::create([
            'event_id'        => $event->id,
            'home_team_id'    => $home->id,
            'away_team_id'    => $away->id,
            'game_date'       => now(),
            'home_team_score' => 1,
            'away_team_score' => 0,
        ]);
    }

    private function insertResult(int $userId, int $gameId, array $data = []): void
    {
        DB::table('point_results')->insert(array_merge([
            'user_id'           => $userId,
            'game_id'           => $gameId,
            'winner_points'     => 0,
            'difference_points' => 0,
            'bingo_points'      => 0,
            'odds'              => 1,
            'odds_points'       => 0,
            'full_points'       => 0,
            'streak_bonus'      => 0,
            'created_at'        => now(),
            'updated_at'        => now(),
        ], $data));
    }

    private function insertStanding(int $userId, array $data = []): void
    {
        DB::table('point_standings')->insert(array_merge([
            'user_id'               => $userId,
            'team_id'               => Team::create(['team' => 'T' . uniqid()])->id,
            'group_position_points' => 0,
            'last32_points'         => 0,
            'last16_points'         => 0,
            'quarterfinal_points'   => 0,
            'semifinal_points'      => 0,
            'final_points'          => 0,
        ], $data));
    }

    private function insertSurvival(int $userId, int $eventId, float $pts): void
    {
        DB::table('point_survivals')->insert([
            'user_id'         => $userId,
            'event_id'        => $eventId,
            'survival_points' => $pts,
            'team_id'         => Team::create(['team' => 'S' . uniqid()])->id,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    // ── getBulkUserGamePoints ─────────────────────────────────────────

    public function test_bulk_game_points_matches_per_user_calls(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        $game1 = $this->makeGame();
        $game2 = $this->makeGame();

        $this->insertResult($userA->id, $game1->id, [
            'full_points' => 100.0, 'streak_bonus' => 10.0, 'bingo_points' => 50,
        ]);
        $this->insertResult($userA->id, $game2->id, [
            'full_points' => 50.0, 'streak_bonus' => 0, 'bingo_points' => 0,
        ]);
        $this->insertResult($userB->id, $game1->id, [
            'full_points' => 75.0, 'streak_bonus' => 5.0, 'bingo_points' => 50,
        ]);

        $controller = app(PointResultController::class);
        $bulk       = $controller->getBulkUserGamePoints(
            [$userA->id, $userB->id], $this->league->id
        );

        $this->assertArrayHasKey($userA->id, $bulk);
        $this->assertArrayHasKey($userB->id, $bulk);

        // userA: 100 + 50 = 150 game pts, 10 + 0 = 10 streak, 1 bingo (game1 only), 2 games
        $this->assertEquals(150.0, $bulk[$userA->id]['game_points']);
        $this->assertEquals(10.0,  $bulk[$userA->id]['streak_points']);
        $this->assertEquals(1,     $bulk[$userA->id]['bingo_count']);
        $this->assertEquals(2,     $bulk[$userA->id]['game_count']);

        // userB: 75 game pts, 5 streak, 1 bingo, 1 game
        $this->assertEquals(75.0, $bulk[$userB->id]['game_points']);
        $this->assertEquals(5.0,  $bulk[$userB->id]['streak_points']);
        $this->assertEquals(1,    $bulk[$userB->id]['bingo_count']);
        $this->assertEquals(1,    $bulk[$userB->id]['game_count']);
    }

    public function test_bulk_game_points_returns_nothing_for_user_with_no_results(): void
    {
        $user       = $this->makeUser();
        $controller = app(PointResultController::class);
        $bulk       = $controller->getBulkUserGamePoints([$user->id], $this->league->id);

        $this->assertArrayNotHasKey($user->id, $bulk);
    }

    public function test_bulk_game_points_empty_array_returns_empty(): void
    {
        $controller = app(PointResultController::class);
        $this->assertSame([], $controller->getBulkUserGamePoints([], $this->league->id));
    }

    // ── getBulkUserStandingPoints ─────────────────────────────────────

    public function test_bulk_standing_points_matches_per_user_calls(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();

        $this->insertStanding($userA->id, ['group_position_points' => 30, 'last16_points' => 60]);
        $this->insertStanding($userA->id, ['quarterfinal_points' => 90]);
        $this->insertStanding($userB->id, ['semifinal_points' => 120, 'final_points' => 200]);

        $controller = app(PointStandingController::class);
        $bulk       = $controller->getBulkUserStandingPoints([$userA->id, $userB->id]);

        $this->assertArrayHasKey($userA->id, $bulk);
        $this->assertArrayHasKey($userB->id, $bulk);

        // userA: 30 + 60 + 90 = 180
        $this->assertEquals(180, $bulk[$userA->id]->total_points);
        $this->assertEquals(30,  $bulk[$userA->id]->group_position_points);
        $this->assertEquals(60,  $bulk[$userA->id]->last16_points);
        $this->assertEquals(90,  $bulk[$userA->id]->quarterfinal_points);

        // userB: 120 + 200 = 320
        $this->assertEquals(320, $bulk[$userB->id]->total_points);
    }

    public function test_bulk_standing_points_fills_zeros_for_user_with_no_rows(): void
    {
        $user       = $this->makeUser();
        $controller = app(PointStandingController::class);
        $bulk       = $controller->getBulkUserStandingPoints([$user->id]);

        $this->assertArrayHasKey($user->id, $bulk);
        $this->assertEquals('0', $bulk[$user->id]->total_points);
    }

    public function test_bulk_standing_points_empty_array_returns_empty(): void
    {
        $controller = app(PointStandingController::class);
        $this->assertSame([], $controller->getBulkUserStandingPoints([]));
    }

    // ── getBulkUserSurvivalPoints ─────────────────────────────────────

    public function test_bulk_survival_points_matches_per_user_calls(): void
    {
        $event1 = Event::create([
            'event' => 'R1', 'event_day' => 1, 'event_survival' => 1, 'active' => 1, 'rate' => 1,
        ]);
        $event2 = Event::create([
            'event' => 'R2', 'event_day' => 2, 'event_survival' => 1, 'active' => 1, 'rate' => 1,
        ]);
        $userA  = $this->makeUser();
        $userB  = $this->makeUser();
        $userC  = $this->makeUser();

        // userA: two rows across different events = 30 + 20 = 50
        $this->insertSurvival($userA->id, $event1->id, 30.0);
        $this->insertSurvival($userA->id, $event2->id, 20.0);
        $this->insertSurvival($userB->id, $event1->id, 75.0);
        // userC has no survival rows

        $controller = app(PointSurvivalController::class);
        $bulk       = $controller->getBulkUserSurvivalPoints([$userA->id, $userB->id, $userC->id]);

        $this->assertArrayHasKey($userA->id, $bulk);
        $this->assertArrayHasKey($userB->id, $bulk);
        $this->assertArrayHasKey($userC->id, $bulk);

        $this->assertEquals(50.0, $bulk[$userA->id]);
        $this->assertEquals(75.0, $bulk[$userB->id]);
        $this->assertEquals(0.0,  $bulk[$userC->id]);
    }

    public function test_bulk_survival_points_empty_array_returns_empty(): void
    {
        $controller = app(PointSurvivalController::class);
        $this->assertSame([], $controller->getBulkUserSurvivalPoints([]));
    }

    // ── getAllUserPoints integration ───────────────────────────────────

    public function test_get_all_user_points_returns_correct_order_and_totals(): void
    {
        session(['guest' => 0]);

        $event = Event::create([
            'event' => 'R1', 'event_day' => 1, 'event_survival' => 0, 'active' => 1, 'rate' => 1,
        ]);
        $home = Team::create(['team' => 'H' . uniqid()]);
        $away = Team::create(['team' => 'A' . uniqid()]);
        $game = Game::create([
            'event_id'        => $event->id,
            'home_team_id'    => $home->id,
            'away_team_id'    => $away->id,
            'game_date'       => now(),
            'home_team_score' => 2,
            'away_team_score' => 1,
        ]);

        $userA = $this->makeUser(); // will rank 1st
        $userB = $this->makeUser(); // will rank 2nd
        $userC = $this->makeUser(); // will rank 3rd

        // userA: 200 game pts
        $this->insertResult($userA->id, $game->id, ['full_points' => 200.0]);
        // userB: 100 game pts + 50 standing pts = 150 total
        $this->insertResult($userB->id, $game->id, ['full_points' => 100.0]);
        $this->insertStanding($userB->id, ['group_position_points' => 50]);
        // userC: 50 game pts + 30 survival pts = 80 total
        $this->insertResult($userC->id, $game->id, ['full_points' => 50.0]);
        $this->insertSurvival($userC->id, $event->id, 30.0);

        $controller = new \App\Http\Controllers\PointController();
        $result     = $controller->getAllUserPoints($this->league->id);

        $this->assertCount(3, $result);
        $this->assertEquals($userA->id, $result[0]['userID']);  // 200 pts
        $this->assertEquals($userB->id, $result[1]['userID']);  // 150 pts
        $this->assertEquals($userC->id, $result[2]['userID']);  // 80 pts

        $this->assertEquals(200.0, $result[0]['userGamePoints']);
        $this->assertEquals(100.0, $result[1]['userGamePoints']);
        $this->assertEquals(50.0,  $result[1]['standingPoints']->total_points);
        $this->assertEquals(30.0,  $result[2]['survivalPoints']);
    }
}
