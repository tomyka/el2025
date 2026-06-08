<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\GameOdds;
use App\Models\Group;
use App\Models\PointResult;
use App\Models\PredictionResult;
use App\Models\Team;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoringIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function scaffoldGame(int $homeScore, int $awayScore): array
    {
        $group    = Group::factory()->create();
        $user     = User::factory()->create();
        UserGroup::factory()->create(['user_id' => $user->id, 'group_id' => $group->id]);

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

    public function test_exact_score_prediction_earns_bingo_and_winner_points(): void
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

        $pointResult = PointResult::where('user_id', $user->id)
            ->where('game_id', $game->id)
            ->firstOrFail();

        $this->assertSame(50.0, (float) $pointResult->winner_points);
        $this->assertSame(50.0, (float) $pointResult->bingo_points);
        $this->assertSame(50.0, (float) $pointResult->difference_points);
    }

    public function test_wrong_direction_prediction_earns_zero_winner_points(): void
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

        $pointResult = PointResult::where('user_id', $user->id)
            ->where('game_id', $game->id)
            ->firstOrFail();

        $this->assertSame(0.0, (float) $pointResult->winner_points);
        $this->assertSame(0.0, (float) $pointResult->odds_points);
    }

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

        $pointResult = PointResult::where('user_id', $user->id)
            ->where('game_id', $game->id)
            ->firstOrFail();

        $this->assertSame(1.0, (float) $pointResult->odds);
        $this->assertSame(0.0, (float) $pointResult->odds_points);
    }

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
