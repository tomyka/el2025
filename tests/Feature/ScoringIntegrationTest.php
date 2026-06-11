<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\GameOdds;
use App\Models\League;
use App\Models\LeagueMember;
use App\Models\PointResult;
use App\Models\PredictionResult;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoringIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PointCalculationSeeder::class);
    }

    private function scaffoldGame(int $homeScore, int $awayScore): array
    {
        $league = League::factory()->create();
        $user   = User::factory()->create();
        LeagueMember::factory()->create(['user_id' => $user->id, 'league_id' => $league->id]);

        $event = Event::create([
            'event'          => 'Test Event',
            'event_day'      => 1,
            'event_survival' => 0,
            'rate'           => 1,
        ]);

        $homeTeam = Team::create(['team' => 'Home FC', 'group_name' => 'A']);
        $awayTeam = Team::create(['team' => 'Away FC', 'group_name' => 'A']);

        $game = Game::create([
            'game_date'       => now()->subHour(),
            'event_id'        => $event->id,
            'home_team_id'    => $homeTeam->id,
            'away_team_id'    => $awayTeam->id,
            'home_team_score' => $homeScore,
            'away_team_score' => $awayScore,
        ]);

        GameOdds::create([
            'game_id'   => $game->id,
            'home_odds' => 1.8,
            'draw_odds' => 3.0,
            'away_odds' => 2.2,
        ]);

        return compact('user', 'game', 'event');
    }

    // Exact match: |home_diff|=0, away_diff=0 → table gives 10/2 = 5.0
    // Direction correct → odds_points = 50*(1.8-1)*rate(1) = 40.0
    // full_points = 5.0 + 40.0 = 45.0; winner_points = 0; bingo_points = 0
    public function test_exact_score_prediction_earns_table_max_score(): void
    {
        ['user' => $user, 'game' => $game] = $this->scaffoldGame(2, 1);

        PredictionResult::create([
            'user_id'         => $user->id,
            'game_id'         => $game->id,
            'home_team_score' => 2,
            'away_team_score' => 1,
            'generated'       => 0,
        ]);

        $controller = app(\App\Http\Controllers\PointResultController::class);
        $controller->updateGamePoints($game->id);

        $r = PointResult::where('user_id', $user->id)->where('game_id', $game->id)->firstOrFail();

        $this->assertSame(5.0,  (float) $r->winner_points);       // correct direction → +5
        $this->assertSame(2.5,  (float) $r->bingo_points);        // exact score → +2.5
        $this->assertSame(5.0,  (float) $r->difference_points);   // table 10/2 × rate(1)
        $this->assertEqualsWithDelta(4.0,  (float) $r->odds_points, 0.01); // 5*(1.8-1)*1
        $this->assertEqualsWithDelta(16.5, (float) $r->full_points, 0.01); // 5+2.5+5+4
    }

    // Wrong direction: no odds bonus; winner_points always 0 in new model
    public function test_wrong_direction_prediction_earns_no_odds_bonus(): void
    {
        ['user' => $user, 'game' => $game] = $this->scaffoldGame(2, 0);

        PredictionResult::create([
            'user_id'         => $user->id,
            'game_id'         => $game->id,
            'home_team_score' => 0,
            'away_team_score' => 2,
            'generated'       => 0,
        ]);

        $controller = app(\App\Http\Controllers\PointResultController::class);
        $controller->updateGamePoints($game->id);

        $r = PointResult::where('user_id', $user->id)->where('game_id', $game->id)->firstOrFail();

        $this->assertSame(0.0, (float) $r->winner_points);
        $this->assertSame(0.0, (float) $r->odds_points);
    }

    // Generated prediction: odds forced to 1.0 → no odds bonus regardless of direction
    public function test_generated_prediction_receives_no_odds_bonus(): void
    {
        ['user' => $user, 'game' => $game] = $this->scaffoldGame(2, 0);

        PredictionResult::create([
            'user_id'         => $user->id,
            'game_id'         => $game->id,
            'home_team_score' => 2,
            'away_team_score' => 0,
            'generated'       => 1,
        ]);

        $controller = app(\App\Http\Controllers\PointResultController::class);
        $controller->updateGamePoints($game->id);

        $r = PointResult::where('user_id', $user->id)->where('game_id', $game->id)->firstOrFail();

        $this->assertSame(1.0, (float) $r->odds);
        $this->assertSame(0.0, (float) $r->odds_points);
    }

    // Recalculation must replace, not append
    public function test_recalculating_game_replaces_old_points(): void
    {
        ['user' => $user, 'game' => $game] = $this->scaffoldGame(2, 1);

        PredictionResult::create([
            'user_id'         => $user->id,
            'game_id'         => $game->id,
            'home_team_score' => 2,
            'away_team_score' => 1,
            'generated'       => 0,
        ]);

        $controller = app(\App\Http\Controllers\PointResultController::class);
        $controller->updateGamePoints($game->id);
        $controller->updateGamePoints($game->id);

        $this->assertSame(1, PointResult::where('game_id', $game->id)->count());
    }
}
