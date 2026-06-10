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
}
