<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\PredictionResult;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleGamePredictionTest extends TestCase
{
    use RefreshDatabase;

    private function makeGame(bool $started = false): Game
    {
        $event = Event::create([
            'event' => 'Test', 'event_day' => 1,
            'event_survival' => 0, 'active' => 1, 'rate' => 1,
        ]);
        $home = Team::create(['team' => 'Home' . uniqid()]);
        $away = Team::create(['team' => 'Away' . uniqid()]);
        return Game::create([
            'event_id'     => $event->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'game_date'    => $started
                ? now()->subHours(2)->toDateTimeString()
                : now()->addHours(3)->toDateTimeString(),
        ]);
    }

    public function test_authenticated_user_can_view_single_game_page(): void
    {
        $game = $this->makeGame();
        $user = User::factory()->create();
        PredictionResult::create(['user_id' => $user->id, 'game_id' => $game->id]);

        $this->actingAs($user)
            ->get(route('prediction.game.single', $game->id))
            ->assertOk()
            ->assertViewIs('prediction.game-single')
            ->assertViewHas('locked', false);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $game = $this->makeGame();

        $this->get(route('prediction.game.single', $game->id))
            ->assertRedirect('/login');
    }

    public function test_started_game_is_shown_as_locked(): void
    {
        $game = $this->makeGame(started: true);
        $user = User::factory()->create();
        PredictionResult::create(['user_id' => $user->id, 'game_id' => $game->id]);

        $this->actingAs($user)
            ->get(route('prediction.game.single', $game->id))
            ->assertOk()
            ->assertViewHas('locked', true);
    }

    public function test_nonexistent_game_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('prediction.game.single', 9999))
            ->assertNotFound();
    }
}
