<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RegistrationDeadlineTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        RateLimiter::clear('register');
        RateLimiter::clear('register-page');
        RateLimiter::clear('password-reset');
        parent::tearDown();
    }

    // ── UI tests ────────────────────────────────────────────────────

    public function test_registration_tab_visible_when_no_games(): void
    {
        $this->get(route('tournaments.hub'))->assertOk()->assertSee('Registruotis');
    }

    public function test_registration_tab_visible_when_first_game_is_in_future(): void
    {
        $this->makeGame(now()->addDay());

        $this->get(route('tournaments.hub'))->assertOk()->assertSee('Registruotis');
    }

    public function test_registration_tab_hidden_when_first_game_has_started(): void
    {
        $this->makeGame(now()->subDay());

        $this->get(route('tournaments.hub'))->assertOk()->assertDontSee('Registruotis');
    }

    // ── Backend: GET /register ───────────────────────────────────────

    public function test_register_page_accessible_before_deadline(): void
    {
        $this->makeGame(now()->addDay());

        $this->get(route('register'))->assertOk();
    }

    public function test_register_page_redirects_after_deadline(): void
    {
        $this->makeGame(now()->subDay());

        $this->get(route('register'))->assertRedirect(route('main'));
    }

    // ── Backend: POST /register ──────────────────────────────────────

    public function test_registration_blocked_after_deadline(): void
    {
        $this->makeGame(now()->subDay());

        $this->post(route('register'), [
            'username'              => 'lateuser',
            'name'                  => 'Late',
            'email'                 => 'late@test.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('main'));

        $this->assertDatabaseMissing('users', ['email' => 'late@test.com']);
    }

    public function test_registration_succeeds_before_deadline(): void
    {
        $this->makeGame(now()->addDay());

        $this->post(route('register'), [
            'username'              => 'newuser',
            'name'                  => 'New',
            'email'                 => 'new@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('main'));

        $this->assertDatabaseHas('users', ['email' => 'new@test.com']);
    }

    // ── Rate limiting ────────────────────────────────────────────────

    public function test_registration_rate_limited_after_three_attempts(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('register'), [
                'username'              => 'bot' . $i,
                'name'                  => 'Bot',
                'email'                 => 'bot' . $i . '@test.com',
                'password'              => 'password123',
                'password_confirmation' => 'password123',
            ]);
        }

        $response = $this->post(route('register'), [
            'username'              => 'bot4',
            'name'                  => 'Bot',
            'email'                 => 'bot4@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(429);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function makeGame(\Illuminate\Support\Carbon $date): Game
    {
        $event = Event::create(['event' => 'Test', 'event_day' => 1, 'event_survival' => 0, 'active' => 1, 'rate' => 1]);
        $home  = Team::create(['team' => 'TeamA']);
        $away  = Team::create(['team' => 'TeamB']);

        return Game::create([
            'game_date'    => $date,
            'event_id'     => $event->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
        ]);
    }
}
