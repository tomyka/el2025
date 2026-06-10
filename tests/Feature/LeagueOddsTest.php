<?php
namespace Tests\Feature;

use App\Models\League;
use App\Models\LeagueMember;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeagueOddsTest extends TestCase
{
    use RefreshDatabase;

    private function makeLeagueWith20Members(bool $useLeagueOdds = true): array
    {
        $league = League::create([
            'name'            => 'Big League',
            'is_public'       => false,
            'use_league_odds' => $useLeagueOdds,
        ]);

        $users = [];
        for ($i = 0; $i < 20; $i++) {
            $user = User::factory()->create();
            LeagueMember::create(['league_id' => $league->id, 'user_id' => $user->id, 'active' => false, 'is_guest' => false, 'is_admin' => false]);
            $users[] = $user;
        }

        return [$league, $users];
    }

    private function makeGame(): \App\Models\Game
    {
        $event    = \App\Models\Event::create(['event' => 'Test', 'event_day' => 1, 'event_survival' => 0, 'active' => 1, 'rate' => 1]);
        $homeTeam = \App\Models\Team::create(['team' => 'Home']);
        $awayTeam = \App\Models\Team::create(['team' => 'Away']);
        return \App\Models\Game::create([
            'event_id'     => $event->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'game_date'    => now()->addDays(1)->toDateTimeString(),
        ]);
    }

    public function test_updateGameOdds_writes_league_game_odds_for_opt_in_league(): void
    {
        $game = $this->makeGame();
        [$league, $users] = $this->makeLeagueWith20Members(true);

        // 15 users predict home win (1-0), 5 predict draw (0-0)
        foreach (array_slice($users, 0, 15) as $user) {
            \App\Models\PredictionResult::create(['user_id' => $user->id, 'game_id' => $game->id, 'home_team_score' => 1, 'away_team_score' => 0, 'generated' => 0]);
        }
        foreach (array_slice($users, 15, 5) as $user) {
            \App\Models\PredictionResult::create(['user_id' => $user->id, 'game_id' => $game->id, 'home_team_score' => 0, 'away_team_score' => 0, 'generated' => 0]);
        }

        $controller = new \App\Http\Controllers\GameOddsController();
        $controller->updateGameOdds($game->id);

        $this->assertDatabaseHas('game_odds', ['game_id' => $game->id]);
        $this->assertDatabaseHas('league_game_odds', ['league_id' => $league->id, 'game_id' => $game->id]);
    }

    public function test_updateGameOdds_skips_league_with_fewer_than_20_members(): void
    {
        $game = $this->makeGame();

        $league = League::create(['name' => 'Small', 'is_public' => false, 'use_league_odds' => true]);
        for ($i = 0; $i < 5; $i++) {
            $user = User::factory()->create();
            LeagueMember::create(['league_id' => $league->id, 'user_id' => $user->id, 'active' => false, 'is_guest' => false, 'is_admin' => false]);
        }

        $controller = new \App\Http\Controllers\GameOddsController();
        $controller->updateGameOdds($game->id);

        $this->assertDatabaseMissing('league_game_odds', ['league_id' => $league->id, 'game_id' => $game->id]);
    }

    public function test_league_admin_can_toggle_use_league_odds(): void
    {
        $owner = User::factory()->create();
        $league = League::create(['name' => 'Tog', 'is_public' => false, 'owner_id' => $owner->id, 'use_league_odds' => false]);
        LeagueMember::create(['league_id' => $league->id, 'user_id' => $owner->id, 'is_admin' => true, 'active' => true, 'is_guest' => false]);

        $this->actingAs($owner)->withSession(['userID' => $owner->id])
            ->post(route('leagues.toggleOdds'), ['leagueID' => $league->id, 'use_league_odds' => 1])
            ->assertRedirect();

        $this->assertDatabaseHas('leagues', ['id' => $league->id, 'use_league_odds' => true]);
    }

    public function test_league_odds_recalculated_in_leaderboard_when_active(): void
    {
        $event    = \App\Models\Event::create(['event' => 'E3', 'event_day' => 1, 'event_survival' => 0, 'active' => 1, 'rate' => 1]);
        $homeTeam = \App\Models\Team::create(['team' => 'H3']);
        $awayTeam = \App\Models\Team::create(['team' => 'A3']);
        $game = \App\Models\Game::create([
            'event_id'        => $event->id,
            'home_team_id'    => $homeTeam->id,
            'away_team_id'    => $awayTeam->id,
            'game_date'       => now()->subDay()->toDateTimeString(),
            'home_team_score' => 1,
            'away_team_score' => 0,
        ]);

        [$league, $users] = $this->makeLeagueWith20Members(true);
        $user = $users[0];

        // Global odds: home_odds = 1.8
        DB::table('game_odds')->insert([
            'game_id'    => $game->id,
            'home_odds'  => 1.8,
            'draw_odds'  => 1.5,
            'away_odds'  => 1.9,
            'updated_at' => now(),
        ]);

        // League odds: home_odds = 1.5
        DB::table('league_game_odds')->insert([
            'league_id'  => $league->id,
            'game_id'    => $game->id,
            'home_odds'  => 1.5,
            'draw_odds'  => 1.6,
            'away_odds'  => 1.7,
            'updated_at' => now(),
        ]);

        // User predicted home win, got points
        DB::table('point_results')->insert([
            'user_id'           => $user->id,
            'game_id'           => $game->id,
            'winner_points'     => 50,
            'difference_points' => 30,
            'bingo_points'      => 0,
            'odds'              => 1.8,
            'odds_points'       => 40, // global: 50 * (1.8-1)
            'full_points'       => 120,
            'streak_bonus'      => 0,
        ]);

        $controller = app(\App\Http\Controllers\PointResultController::class);
        $profile    = $controller->getUserProfilePoints($user->id, $league->id);

        // With league odds (1.5): odds_points_league = 50 * (1.5-1) = 25
        $row = collect($profile)->firstWhere('game_id', $game->id);
        $this->assertNotNull($row);
        $this->assertEquals(25.0, (float) $row['odds_points_league']);
        // full_points_league = 50 + 30 + 0 + 25 = 105
        $this->assertEquals(105.0, (float) $row['full_points_league']);
    }
}
