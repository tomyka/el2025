<?php

namespace Tests\Feature;

use App\Http\Controllers\PointController;
use App\Models\Event;
use App\Models\Game;
use App\Models\League;
use App\Models\LeagueMember;
use App\Models\Team;
use App\Models\User;
use App\Models\UserSetting;
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

    private function makeUser(int $leagueId): User
    {
        $user = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $user->id, 'active' => true]);
        LeagueMember::factory()->create(['user_id' => $user->id, 'league_id' => $leagueId, 'is_guest' => 0]);

        return $user;
    }

    private function makeEvent(int $day): Event
    {
        return Event::create(['event' => "R{$day}", 'event_day' => $day, 'event_survival' => 0, 'rate' => 1]);
    }

    private function makeGame(int $eventId): Game
    {
        $home = Team::create(['team' => 'H'.uniqid()]);
        $away = Team::create(['team' => 'A'.uniqid()]);

        return Game::create([
            'game_date' => now(),
            'event_id' => $eventId,
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
        $team = Team::create(['team' => 'S'.uniqid()]);
        DB::table('point_survivals')->insert([
            'user_id' => $userId, 'event_id' => $eventId, 'team_id' => $team->id,
            'survival_points' => $pts, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function insertStanding(int $userId, int $groupPts): void
    {
        $team = Team::create(['team' => 'St'.uniqid()]);
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
        $league = League::factory()->create();
        $user = $this->makeUser($league->id);

        $result = app(PointController::class)
            ->getAllUsersGameHistory($league->id);

        $this->assertEmpty($result[$user->id] ?? []);
    }

    public function test_returns_one_entry_per_scored_game(): void
    {
        $league = League::factory()->create();
        $user = $this->makeUser($league->id);
        $e1 = $this->makeEvent(1);
        $e2 = $this->makeEvent(2);
        $this->insertResult($user->id, $this->makeGame($e1->id)->id, 80.0);
        $this->insertResult($user->id, $this->makeGame($e2->id)->id, 100.0);

        $history = app(PointController::class)
            ->getAllUsersGameHistory($league->id)[$user->id];

        $this->assertCount(2, $history);
        $this->assertEquals(1, $history[0]['game_idx']);
        $this->assertEquals(2, $history[1]['game_idx']);
    }

    public function test_cumulative_points_accumulate_across_games(): void
    {
        $league = League::factory()->create();
        $user = $this->makeUser($league->id);
        $e1 = $this->makeEvent(1);
        $e2 = $this->makeEvent(2);
        $this->insertResult($user->id, $this->makeGame($e1->id)->id, 80.0);
        $this->insertResult($user->id, $this->makeGame($e2->id)->id, 100.0);

        $history = app(PointController::class)
            ->getAllUsersGameHistory($league->id)[$user->id];

        $this->assertEquals(80.0, $history[0]['cumulative_points']);
        $this->assertEquals(180.0, $history[1]['cumulative_points']);
    }

    public function test_game_points_is_per_game_value(): void
    {
        $league = League::factory()->create();
        $user = $this->makeUser($league->id);
        $e1 = $this->makeEvent(1);
        $e2 = $this->makeEvent(2);
        $this->insertResult($user->id, $this->makeGame($e1->id)->id, 80.0);
        $this->insertResult($user->id, $this->makeGame($e2->id)->id, 100.0);

        $history = app(PointController::class)
            ->getAllUsersGameHistory($league->id)[$user->id];

        $this->assertEquals(80.0, $history[0]['game_points']);
        $this->assertEquals(100.0, $history[1]['game_points']);
    }

    public function test_rank_computed_correctly_across_users(): void
    {
        $league = League::factory()->create();
        $u1 = $this->makeUser($league->id);
        $u2 = $this->makeUser($league->id);
        $e1 = $this->makeEvent(1);
        $game = $this->makeGame($e1->id);
        $this->insertResult($u1->id, $game->id, 80.0);
        $this->insertResult($u2->id, $game->id, 120.0);

        $result = app(PointController::class)
            ->getAllUsersGameHistory($league->id);

        $this->assertEquals(2, $result[$u1->id][0]['rank']);
        $this->assertEquals(1, $result[$u2->id][0]['rank']);
    }

    public function test_standing_points_included_in_cumulative(): void
    {
        $league = League::factory()->create();
        $user = $this->makeUser($league->id);
        $e1 = $this->makeEvent(1);
        $this->insertResult($user->id, $this->makeGame($e1->id)->id, 80.0);
        $this->insertStanding($user->id, 100);

        $history = app(PointController::class)
            ->getAllUsersGameHistory($league->id)[$user->id];

        $this->assertEquals(180.0, $history[0]['cumulative_points']);
    }

    public function test_standing_points_excluded_from_game_points(): void
    {
        $league = League::factory()->create();
        $user = $this->makeUser($league->id);
        $e1 = $this->makeEvent(1);
        $e2 = $this->makeEvent(2);
        $this->insertResult($user->id, $this->makeGame($e1->id)->id, 80.0);
        $this->insertResult($user->id, $this->makeGame($e2->id)->id, 100.0);
        $this->insertStanding($user->id, 100);

        $history = app(PointController::class)
            ->getAllUsersGameHistory($league->id)[$user->id];

        $this->assertEquals(80.0, $history[0]['game_points']);
        $this->assertEquals(100.0, $history[1]['game_points']);
    }

    public function test_survival_points_included_in_cumulative(): void
    {
        $league = League::factory()->create();
        $user = $this->makeUser($league->id);
        $e1 = $this->makeEvent(1);
        $this->insertResult($user->id, $this->makeGame($e1->id)->id, 80.0);
        $this->insertSurvival($user->id, $e1->id, 50);

        $history = app(PointController::class)
            ->getAllUsersGameHistory($league->id)[$user->id];

        $this->assertEquals(130.0, $history[0]['cumulative_points']);
        $this->assertEquals(80.0, $history[0]['game_points']);
    }

    public function test_get_rank_history_returns_empty_with_no_scored_games(): void
    {
        $league = League::factory()->create();
        $user = $this->makeUser($league->id);

        $result = app(PointController::class)
            ->getRankHistory($league->id, $user->id);

        $this->assertSame([], $result);
    }

    public function test_get_rank_history_single_player_always_rank_one(): void
    {
        $league = League::factory()->create();
        $user = $this->makeUser($league->id);
        $event = $this->makeEvent(1);
        $game = $this->makeGame($event->id);
        $game->update(['home_team_score' => 1, 'away_team_score' => 0]);
        $this->insertResult($user->id, $game->id, 50.0);

        $result = app(PointController::class)
            ->getRankHistory($league->id, $user->id);

        $this->assertSame([1], $result);
    }

    public function test_get_rank_history_rank_reflects_cumulative_points(): void
    {
        $league = League::factory()->create();
        $u1 = $this->makeUser($league->id);
        $u2 = $this->makeUser($league->id);
        $event = $this->makeEvent(1);
        $g1 = $this->makeGame($event->id);
        $g2 = $this->makeGame($event->id);
        $g1->update(['home_team_score' => 1, 'away_team_score' => 0, 'game_date' => now()->subHours(2)]);
        $g2->update(['home_team_score' => 2, 'away_team_score' => 0, 'game_date' => now()->subHour()]);

        // After g1: u2 leads (100 vs 50) → u1 is #2
        $this->insertResult($u1->id, $g1->id, 50.0);
        $this->insertResult($u2->id, $g1->id, 100.0);
        // After g2: u1 overtakes (250 total vs 100) → u1 is #1
        $this->insertResult($u1->id, $g2->id, 200.0);
        $this->insertResult($u2->id, $g2->id, 0.0);

        $result = app(PointController::class)
            ->getRankHistory($league->id, $u1->id);

        $this->assertSame([2, 1], $result);
    }

    public function test_get_rank_history_tied_users_get_same_rank(): void
    {
        $league = League::factory()->create();
        $u1 = $this->makeUser($league->id);
        $u2 = $this->makeUser($league->id);
        $u3 = $this->makeUser($league->id);
        $event = $this->makeEvent(1);
        $game = $this->makeGame($event->id);
        $game->update(['home_team_score' => 1, 'away_team_score' => 0]);

        // u1=100, u2=100, u3=50 → u1 and u2 both rank 1, u3 rank 2 (dense)
        $this->insertResult($u1->id, $game->id, 100.0);
        $this->insertResult($u2->id, $game->id, 100.0);
        $this->insertResult($u3->id, $game->id, 50.0);

        $result = app(PointController::class)
            ->getRankHistory($league->id, $u1->id);

        $this->assertSame([1], $result);

        $result3 = app(PointController::class)
            ->getRankHistory($league->id, $u3->id);

        $this->assertSame([2], $result3);
    }

    public function test_get_rank_history_user_not_in_league_returns_empty(): void
    {
        $league = League::factory()->create();
        $outsider = User::factory()->create(); // NOT added to league

        $result = app(PointController::class)
            ->getRankHistory($league->id, $outsider->id);

        $this->assertSame([], $result);
    }
}
