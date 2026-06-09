<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\Group;
use App\Models\Team;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PointTrendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        session(['guest' => 0]);
    }

    private function makeUser(int $groupId): User
    {
        $user = User::factory()->create();
        UserGroup::factory()->create(['user_id' => $user->id, 'group_id' => $groupId, 'guest' => 0]);
        return $user;
    }

    private function makeEvent(int $day): Event
    {
        return Event::create(['event' => "R{$day}", 'event_day' => $day, 'event_survival' => 0, 'rate' => 1]);
    }

    private function makeGame(int $eventId): Game
    {
        $home = Team::create(['team' => 'H' . uniqid()]);
        $away = Team::create(['team' => 'A' . uniqid()]);
        return Game::create([
            'game_date'    => now(),
            'event_id'     => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
        ]);
    }

    private function insertResult(int $userId, int $gameId, float $pts): void
    {
        DB::table('point_results')->insert([
            'user_id' => $userId, 'game_id' => $gameId,
            'winner_points' => 0, 'difference_points' => 0, 'bingo_points' => 0,
            'odds' => 1, 'odds_points' => 0, 'full_points' => $pts,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function insertSurvival(int $userId, int $eventId, int $pts): void
    {
        $team = Team::create(['team' => 'S' . uniqid()]);
        DB::table('point_survivals')->insert([
            'user_id' => $userId, 'event_id' => $eventId, 'team_id' => $team->id,
            'survival_points' => $pts, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function insertStanding(int $userId, int $groupPts): void
    {
        $team = Team::create(['team' => 'St' . uniqid()]);
        DB::table('point_standings')->insert([
            'user_id' => $userId, 'team_id' => $team->id,
            'group_position_points' => $groupPts, 'last32_points' => 0,
            'last16_points' => 0, 'quarterfinal_points' => 0,
            'semifinal_points' => 0, 'final_points' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_returns_empty_when_no_scored_games(): void
    {
        $group = Group::factory()->create();
        $user  = $this->makeUser($group->id);

        $result = app(\App\Http\Controllers\PointController::class)
            ->getAllUsersRoundHistory($group->id);

        $this->assertEmpty($result[$user->id] ?? []);
    }

    public function test_returns_one_entry_per_scored_event(): void
    {
        $group = Group::factory()->create();
        $user  = $this->makeUser($group->id);
        $e1    = $this->makeEvent(1);
        $e2    = $this->makeEvent(2);
        $this->insertResult($user->id, $this->makeGame($e1->id)->id, 80.0);
        $this->insertResult($user->id, $this->makeGame($e2->id)->id, 100.0);

        $history = app(\App\Http\Controllers\PointController::class)
            ->getAllUsersRoundHistory($group->id)[$user->id];

        $this->assertCount(2, $history);
        $this->assertEquals(1, $history[0]['event_day']);
        $this->assertEquals(2, $history[1]['event_day']);
    }

    public function test_cumulative_points_accumulate_across_rounds(): void
    {
        $group = Group::factory()->create();
        $user  = $this->makeUser($group->id);
        $e1    = $this->makeEvent(1);
        $e2    = $this->makeEvent(2);
        $this->insertResult($user->id, $this->makeGame($e1->id)->id, 80.0);
        $this->insertResult($user->id, $this->makeGame($e2->id)->id, 100.0);

        $history = app(\App\Http\Controllers\PointController::class)
            ->getAllUsersRoundHistory($group->id)[$user->id];

        $this->assertEquals(80.0,  $history[0]['cumulative_points']);
        $this->assertEquals(180.0, $history[1]['cumulative_points']);
    }

    public function test_round_points_is_delta_per_round(): void
    {
        $group = Group::factory()->create();
        $user  = $this->makeUser($group->id);
        $e1    = $this->makeEvent(1);
        $e2    = $this->makeEvent(2);
        $this->insertResult($user->id, $this->makeGame($e1->id)->id, 80.0);
        $this->insertResult($user->id, $this->makeGame($e2->id)->id, 100.0);

        $history = app(\App\Http\Controllers\PointController::class)
            ->getAllUsersRoundHistory($group->id)[$user->id];

        $this->assertEquals(80.0,  $history[0]['round_points']);
        $this->assertEquals(100.0, $history[1]['round_points']);
    }

    public function test_rank_computed_correctly_across_users(): void
    {
        $group = Group::factory()->create();
        $u1    = $this->makeUser($group->id);
        $u2    = $this->makeUser($group->id);
        $e1    = $this->makeEvent(1);
        $this->insertResult($u1->id, $this->makeGame($e1->id)->id, 80.0);
        $this->insertResult($u2->id, $this->makeGame($e1->id)->id, 120.0);

        $result = app(\App\Http\Controllers\PointController::class)
            ->getAllUsersRoundHistory($group->id);

        $this->assertEquals(2, $result[$u1->id][0]['rank']);
        $this->assertEquals(1, $result[$u2->id][0]['rank']);
    }

    public function test_standing_points_included_in_cumulative(): void
    {
        $group = Group::factory()->create();
        $user  = $this->makeUser($group->id);
        $e1    = $this->makeEvent(1);
        $this->insertResult($user->id, $this->makeGame($e1->id)->id, 80.0);
        $this->insertStanding($user->id, 100);

        $history = app(\App\Http\Controllers\PointController::class)
            ->getAllUsersRoundHistory($group->id)[$user->id];

        $this->assertEquals(180.0, $history[0]['cumulative_points']);
    }

    public function test_standing_points_excluded_from_round_points(): void
    {
        $group = Group::factory()->create();
        $user  = $this->makeUser($group->id);
        $e1    = $this->makeEvent(1);
        $e2    = $this->makeEvent(2);
        $this->insertResult($user->id, $this->makeGame($e1->id)->id, 80.0);
        $this->insertResult($user->id, $this->makeGame($e2->id)->id, 100.0);
        $this->insertStanding($user->id, 100);

        $history = app(\App\Http\Controllers\PointController::class)
            ->getAllUsersRoundHistory($group->id)[$user->id];

        $this->assertEquals(80.0,  $history[0]['round_points']);
        $this->assertEquals(100.0, $history[1]['round_points']);
    }

    public function test_survival_points_included_in_round_and_cumulative(): void
    {
        $group = Group::factory()->create();
        $user  = $this->makeUser($group->id);
        $e1    = $this->makeEvent(1);
        $this->insertResult($user->id, $this->makeGame($e1->id)->id, 80.0);
        $this->insertSurvival($user->id, $e1->id, 50);

        $history = app(\App\Http\Controllers\PointController::class)
            ->getAllUsersRoundHistory($group->id)[$user->id];

        $this->assertEquals(130.0, $history[0]['cumulative_points']);
        $this->assertEquals(130.0, $history[0]['round_points']);
    }

    public function test_getRankHistory_returns_empty_with_no_scored_games(): void
    {
        $group = Group::factory()->create();
        $user  = $this->makeUser($group->id);

        $result = app(\App\Http\Controllers\PointController::class)
            ->getRankHistory($group->id, $user->id);

        $this->assertSame([], $result);
    }

    public function test_getRankHistory_single_player_always_rank_one(): void
    {
        $group = Group::factory()->create();
        $user  = $this->makeUser($group->id);
        $event = $this->makeEvent(1);
        $game  = $this->makeGame($event->id);
        $game->update(['home_team_score' => 1, 'away_team_score' => 0]);
        $this->insertResult($user->id, $game->id, 50.0);

        $result = app(\App\Http\Controllers\PointController::class)
            ->getRankHistory($group->id, $user->id);

        $this->assertSame([1], $result);
    }

    public function test_getRankHistory_rank_reflects_cumulative_points(): void
    {
        $group  = Group::factory()->create();
        $u1     = $this->makeUser($group->id);
        $u2     = $this->makeUser($group->id);
        $event  = $this->makeEvent(1);
        $g1     = $this->makeGame($event->id);
        $g2     = $this->makeGame($event->id);
        $g1->update(['home_team_score' => 1, 'away_team_score' => 0, 'game_date' => now()->subHours(2)]);
        $g2->update(['home_team_score' => 2, 'away_team_score' => 0, 'game_date' => now()->subHour()]);

        // After g1: u2 leads (100 vs 50) → u1 is #2
        $this->insertResult($u1->id, $g1->id, 50.0);
        $this->insertResult($u2->id, $g1->id, 100.0);
        // After g2: u1 overtakes (250 total vs 100) → u1 is #1
        $this->insertResult($u1->id, $g2->id, 200.0);
        $this->insertResult($u2->id, $g2->id, 0.0);

        $result = app(\App\Http\Controllers\PointController::class)
            ->getRankHistory($group->id, $u1->id);

        $this->assertSame([2, 1], $result);
    }

    public function test_getRankHistory_user_not_in_group_returns_empty(): void
    {
        $group   = Group::factory()->create();
        $outsider = User::factory()->create(); // NOT added to group

        $result = app(\App\Http\Controllers\PointController::class)
            ->getRankHistory($group->id, $outsider->id);

        $this->assertSame([], $result);
    }
}
