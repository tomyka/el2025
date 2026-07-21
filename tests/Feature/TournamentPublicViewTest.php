<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\League;
use App\Models\LeagueMember;
use App\Models\PointResult;
use App\Models\PredictionResult;
use App\Models\PredictionStanding;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentPublicViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_points_table_and_final_predictions_on_public_tournament_page(): void
    {
        $tournament = Tournament::create(['name' => 'Public Cup', 'slug' => 'public-cup', 'sport' => 'football', 'status' => 'finished']);
        $league = League::create(['name' => 'Public League', 'is_public' => true, 'tournament_id' => $tournament->id]);

        $user = User::factory()->create(['username' => 'publicviewer']);
        UserSetting::factory()->create(['user_id' => $user->id, 'active' => true]);
        LeagueMember::create(['league_id' => $league->id, 'user_id' => $user->id, 'active' => true, 'is_guest' => false]);

        $event = Event::create(['event' => 'R1', 'event_day' => 1, 'event_survival' => 0, 'active' => 1, 'rate' => 1, 'tournament_id' => $tournament->id]);
        $home = Team::create(['team' => 'HomeTeam', 'tournament_id' => $tournament->id]);
        $away = Team::create(['team' => 'AwayTeam', 'tournament_id' => $tournament->id]);
        $game = Game::create([
            'game_date' => now()->subDay(), 'event_id' => $event->id,
            'home_team_id' => $home->id, 'away_team_id' => $away->id,
            'home_team_score' => 2, 'away_team_score' => 1,
        ]);
        PredictionResult::create(['user_id' => $user->id, 'game_id' => $game->id, 'home_team_score' => 2, 'away_team_score' => 1]);
        PointResult::create([
            'user_id' => $user->id, 'game_id' => $game->id,
            'winner_points' => 5, 'difference_points' => 2, 'bingo_points' => 2.5,
            'odds' => 1.2, 'odds_points' => 1, 'full_points' => 12.5, 'streak_bonus' => 0,
        ]);

        $finalsTeam = Team::create(['team' => 'FinalsTeam', 'tournament_id' => $tournament->id]);
        PredictionStanding::create(['user_id' => $user->id, 'team_id' => $finalsTeam->id, 'final' => 1]);

        $response = $this->get(route('tournament.show', $tournament->slug));

        $response->assertOk();
        $response->assertSee('publicviewer', false);
        $response->assertSee('Taškų lentelė', false);
        $response->assertSee('Finalų dalyvių prognozės', false);
        $response->assertSee('FinalsTeam', false);
    }

    public function test_guest_sees_no_points_table_when_tournament_has_no_public_league(): void
    {
        $tournament = Tournament::create(['name' => 'Private Only Cup', 'slug' => 'private-only-cup', 'sport' => 'football', 'status' => 'finished']);
        League::create(['name' => 'Private League', 'is_public' => false, 'tournament_id' => $tournament->id]);

        $response = $this->get(route('tournament.show', $tournament->slug));

        $response->assertOk();
        $response->assertDontSee('Taškų lentelė', false);
        $response->assertDontSee('Finalų dalyvių prognozės', false);
    }

    public function test_guest_public_page_header_has_no_prediction_editing_links(): void
    {
        $tournament = Tournament::create(['name' => 'Header Check Cup', 'slug' => 'header-check-cup', 'sport' => 'football', 'status' => 'finished']);
        $league = League::create(['name' => 'Public League', 'is_public' => true, 'tournament_id' => $tournament->id]);
        $user = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $user->id, 'active' => true]);
        LeagueMember::create(['league_id' => $league->id, 'user_id' => $user->id, 'active' => true, 'is_guest' => false]);

        $response = $this->get(route('tournament.show', $tournament->slug));

        $response->assertOk();
        $response->assertDontSee(route('prediction.results'), false);
        $response->assertDontSee(route('prediction.standings'), false);
    }

    public function test_join_invitation_card_hidden_when_tournament_is_finished(): void
    {
        $tournament = Tournament::create(['name' => 'Finished Cup', 'slug' => 'finished-cup', 'sport' => 'football', 'status' => 'finished']);

        $response = $this->get(route('tournament.show', $tournament->slug));

        $response->assertOk();
        $response->assertDontSee('Prisijungti ir dalyvauti', false);
        $response->assertDontSee($tournament->name, false);
        $response->assertSee('← Turnyrai', false);
        $response->assertSee(route('tournaments.hub'), false);
    }

    public function test_join_invitation_card_shown_when_tournament_still_active(): void
    {
        $tournament = Tournament::create(['name' => 'Active Cup', 'slug' => 'active-cup', 'sport' => 'football', 'status' => 'active']);

        $response = $this->get(route('tournament.show', $tournament->slug));

        $response->assertOk();
        $response->assertSee('Prisijungti ir dalyvauti', false);
        $response->assertSee($tournament->name, false);
    }
}
