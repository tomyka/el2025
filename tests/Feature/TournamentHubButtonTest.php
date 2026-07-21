<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\League;
use App\Models\LeagueMember;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentHubButtonTest extends TestCase
{
    use RefreshDatabase;

    private function makeActiveTournamentWithMember(): array
    {
        $tournament = Tournament::create([
            'name' => 'Hub Cup', 'slug' => 'hub-cup',
            'sport' => 'football', 'status' => 'active',
        ]);
        $league = League::create(['name' => 'Hub League', 'is_public' => false, 'tournament_id' => $tournament->id]);
        $user = User::factory()->create();
        LeagueMember::create(['league_id' => $league->id, 'user_id' => $user->id, 'active' => true, 'is_guest' => false]);

        $event = Event::create(['event' => 'R1', 'event_day' => 1, 'event_survival' => 0, 'active' => 1, 'rate' => 1, 'tournament_id' => $tournament->id]);
        $home = Team::create(['team' => 'HomeTeam', 'tournament_id' => $tournament->id]);
        $away = Team::create(['team' => 'AwayTeam', 'tournament_id' => $tournament->id]);

        return compact('tournament', 'league', 'user', 'event', 'home', 'away');
    }

    public function test_shows_zaisti_when_active_tournament_still_has_unscored_games(): void
    {
        $data = $this->makeActiveTournamentWithMember();
        Game::create([
            'game_date' => now()->addDay(),
            'event_id' => $data['event']->id,
            'home_team_id' => $data['home']->id,
            'away_team_id' => $data['away']->id,
        ]);

        $this->actingAs($data['user'])
            ->get(route('tournaments.hub'))
            ->assertOk()
            ->assertSee('Žaisti →', false)
            ->assertDontSee('Peržiūrėti →', false);
    }

    public function test_shows_perziureti_when_active_tournament_has_all_games_scored(): void
    {
        $data = $this->makeActiveTournamentWithMember();
        Game::create([
            'game_date' => now()->subDay(),
            'event_id' => $data['event']->id,
            'home_team_id' => $data['home']->id,
            'away_team_id' => $data['away']->id,
            'home_team_score' => 2,
            'away_team_score' => 1,
        ]);

        $this->actingAs($data['user'])
            ->get(route('tournaments.hub'))
            ->assertOk()
            ->assertSee('Peržiūrėti →', false)
            ->assertDontSee('Žaisti →', false);
    }

    public function test_shows_zaisti_when_active_tournament_has_no_games_yet(): void
    {
        $data = $this->makeActiveTournamentWithMember();

        $this->actingAs($data['user'])
            ->get(route('tournaments.hub'))
            ->assertOk()
            ->assertSee('Žaisti →', false);
    }
}
