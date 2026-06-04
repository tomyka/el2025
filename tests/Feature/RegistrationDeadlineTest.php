<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationDeadlineTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_tab_visible_when_no_games(): void
    {
        $response = $this->get(route('main'));

        $response->assertOk();
        $response->assertSee('Registruotis');
    }

    public function test_registration_tab_visible_when_first_game_is_in_future(): void
    {
        $homeTeam = Team::create(['team' => 'TeamA']);
        $awayTeam = Team::create(['team' => 'TeamB']);
        $event = Event::create([
            'event' => 'WC 2026',
            'event_day' => 1,
            'event_survival' => 0,
            'active' => 1,
            'rate' => 1,
        ]);
        Game::create([
            'game_date' => now()->addDay(),
            'event_id' => $event->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
        ]);

        $response = $this->get(route('main'));

        $response->assertOk();
        $response->assertSee('Registruotis');
    }

    public function test_registration_tab_hidden_when_first_game_has_started(): void
    {
        $homeTeam = Team::create(['team' => 'TeamA']);
        $awayTeam = Team::create(['team' => 'TeamB']);
        $event = Event::create([
            'event' => 'WC 2026',
            'event_day' => 1,
            'event_survival' => 0,
            'active' => 1,
            'rate' => 1,
        ]);
        Game::create([
            'game_date' => now()->subDay(),
            'event_id' => $event->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
        ]);

        $response = $this->get(route('main'));

        $response->assertOk();
        $response->assertDontSee('Registruotis');
    }
}
