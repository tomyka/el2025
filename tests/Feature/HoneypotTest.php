<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class HoneypotTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        RateLimiter::clear('register');
        parent::tearDown();
    }

    private function makeOpenGame(): void
    {
        $event = Event::create(['event' => 'Test', 'event_day' => 1, 'event_survival' => 0, 'active' => 1, 'rate' => 1]);
        $home = Team::create(['team' => 'Home']);
        $away = Team::create(['team' => 'Away']);
        Game::create([
            'event_id'        => $event->id,
            'home_team_id'    => $home->id,
            'away_team_id'    => $away->id,
            'game_date'       => now()->addDay(),
            'home_team_score' => null,
            'away_team_score' => null,
        ]);
    }

    public function test_honeypot_filled_silently_rejects_registration(): void
    {
        $this->makeOpenGame();

        $this->post(route('register'), [
            'username'              => 'botuser',
            'name'                  => 'Bot',
            'email'                 => 'bot@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'website'               => 'http://spam.example.com',
        ])->assertRedirect(route('main'));

        $this->assertDatabaseMissing('users', ['email' => 'bot@test.com']);
    }

    public function test_honeypot_empty_allows_registration(): void
    {
        $this->makeOpenGame();

        $this->post(route('register'), [
            'username'              => 'realuser',
            'name'                  => 'Real',
            'email'                 => 'real@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'website'               => '',
        ])->assertRedirect(route('main'));

        $this->assertDatabaseHas('users', ['email' => 'real@test.com']);
    }
}
