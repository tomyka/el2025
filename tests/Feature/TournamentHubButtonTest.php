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

    /**
     * The hub page always renders whatever other tournaments exist (e.g. a
     * pre-seeded fixture tournament from a data migration), so widget-content
     * assertions must be scoped to just this tournament's own card.
     */
    private function cardHtmlFor(string $content, string $tournamentName): string
    {
        $start = strpos($content, $tournamentName);
        $this->assertIsInt($start, "Tournament \"{$tournamentName}\" not found in response.");

        $nextCard = strpos($content, 'sb-card mb-4', $start);
        $end = $nextCard !== false ? $nextCard : strlen($content);

        return substr($content, $start, $end - $start);
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

    public function test_guest_sees_perziureti_button_when_active_tournament_is_finished(): void
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

        $this->get(route('tournaments.hub'))
            ->assertOk()
            ->assertSee('Peržiūrėti →', false)
            ->assertSee(route('tournament.show', $data['tournament']->slug), false);
    }

    public function test_guest_sees_no_button_when_active_tournament_still_ongoing(): void
    {
        $data = $this->makeActiveTournamentWithMember();
        Game::create([
            'game_date' => now()->addDay(),
            'event_id' => $data['event']->id,
            'home_team_id' => $data['home']->id,
            'away_team_id' => $data['away']->id,
        ]);

        $this->get(route('tournaments.hub'))
            ->assertOk()
            ->assertDontSee('Žaisti →', false)
            ->assertDontSee('Peržiūrėti →', false);
    }

    public function test_guest_detail_widgets_hidden_when_active_tournament_is_finished(): void
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

        $response = $this->get(route('tournaments.hub'))->assertOk();
        $card = $this->cardHtmlFor($response->getContent(), 'Hub Cup');

        $this->assertStringNotContainsString('Statistika', $card);
    }

    public function test_guest_detail_widgets_still_shown_when_tournament_ongoing(): void
    {
        $data = $this->makeActiveTournamentWithMember();
        Game::create([
            'game_date' => now()->addDay(),
            'event_id' => $data['event']->id,
            'home_team_id' => $data['home']->id,
            'away_team_id' => $data['away']->id,
        ]);

        $response = $this->get(route('tournaments.hub'))->assertOk();
        $card = $this->cardHtmlFor($response->getContent(), 'Hub Cup');

        $this->assertStringContainsString('Statistika', $card);
    }
}
