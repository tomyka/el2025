<?php
namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\League;
use App\Models\LeagueMember;
use App\Models\Setting;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentSessionTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserInTournament(bool $survivalGame = false): array
    {
        $tournament = Tournament::create([
            'name' => 'Test Cup', 'slug' => 'test-cup',
            'sport' => 'football', 'status' => 'active',
            'survival_game' => $survivalGame,
        ]);
        $league = League::create(['name' => 'L', 'is_public' => false, 'tournament_id' => $tournament->id]);
        $user   = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $user->id, 'admin' => 0]);
        Setting::firstOrCreate(['setting' => 'timeDifference'], ['value' => 0]);
        LeagueMember::create([
            'league_id' => $league->id, 'user_id' => $user->id,
            'active' => true, 'is_guest' => false,
        ]);
        return [$user, $tournament, $league];
    }

    public function test_set_session_puts_tournament_id_in_session(): void
    {
        [$user, $tournament] = $this->makeUserInTournament();
        (new \App\Http\Controllers\SessionController())->setSession($user);
        $this->assertEquals($tournament->id, session('tournamentID'));
    }

    public function test_survival_game_comes_from_tournament_not_settings(): void
    {
        [$user] = $this->makeUserInTournament(survivalGame: true);
        // Deliberately set settings to opposite value
        Setting::firstOrCreate(['setting' => 'survivalGame'], ['value' => 0]);
        (new \App\Http\Controllers\SessionController())->setSession($user);
        $this->assertEquals(1, session('survivalGame'));
    }

    public function test_event_lookup_scoped_to_tournament(): void
    {
        [$user, $tournament] = $this->makeUserInTournament();

        $otherTournament = Tournament::create([
            'name' => 'Other Cup', 'slug' => 'other-cup',
            'sport' => 'football', 'status' => 'active', 'survival_game' => false,
        ]);
        $otherEvent = Event::create([
            'event' => 'Other Round', 'event_day' => 1, 'event_survival' => 0,
            'active' => 1, 'rate' => 1, 'tournament_id' => $otherTournament->id,
        ]);
        $otherTeamA = Team::create(['team' => 'X', 'tournament_id' => $otherTournament->id]);
        $otherTeamB = Team::create(['team' => 'Y', 'tournament_id' => $otherTournament->id]);
        Game::create([
            'game_date' => now()->addDay(),
            'event_id' => $otherEvent->id,
            'home_team_id' => $otherTeamA->id,
            'away_team_id' => $otherTeamB->id,
        ]);

        (new \App\Http\Controllers\SessionController())->setSession($user);
        // eventID must be 0 because no games exist in the user's tournament
        $this->assertEquals(0, session('eventID'));
    }

    public function test_disabled_stays_set_once_tournament_finished(): void
    {
        [$user, $tournament] = $this->makeUserInTournament();

        $event = Event::create([
            'event' => 'Final', 'event_day' => 1, 'event_survival' => 0,
            'active' => 1, 'rate' => 1, 'tournament_id' => $tournament->id,
        ]);
        $teamA = Team::create(['team' => 'A', 'tournament_id' => $tournament->id]);
        $teamB = Team::create(['team' => 'B', 'tournament_id' => $tournament->id]);
        Game::create([
            'game_date' => now()->subDay(),
            'event_id' => $event->id,
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamB->id,
            'home_team_score' => 1,
            'away_team_score' => 0,
        ]);

        (new \App\Http\Controllers\SessionController())->setSession($user);

        // Tournament is fully scored (no unscored games left), but the first
        // game already started in the past, so predictions must stay locked.
        $this->assertEquals(0, session('eventID'));
        $this->assertEquals('disabled', session('disabled'));
    }
}
