<?php

namespace Tests\Feature;

use App\Models\League;
use App\Models\LeagueMember;
use App\Models\PointStanding;
use App\Models\PredictionStanding;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class StandingSummaryBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_incorrect_knockout_prediction_shows_x_and_points_popover(): void
    {
        $user = User::factory()->create();
        $league = League::factory()->create();
        LeagueMember::factory()->create(['user_id' => $user->id, 'league_id' => $league->id, 'active' => true, 'is_guest' => 0]);

        // Team did NOT advance to last32, but the user predicted it would (wrong pick).
        $team = Team::create(['team' => 'TeamA']);
        $team->forceFill(['last32' => 0])->save();

        PredictionStanding::create(['user_id' => $user->id, 'team_id' => $team->id, 'last32' => 1]);
        PointStanding::create(['user_id' => $user->id, 'team_id' => $team->id, 'last32_points' => 0]);

        Session::put('leagueID', $league->id);

        $response = $this->actingAs($user)->get(route('summary.prediction.standings'));

        $response->assertOk();
        $response->assertSee('✗', false);
        $response->assertDontSee('✓', false);
        $response->assertSee('sst-pop-badge', false);
        $response->assertSee('data-bs-content', false);
    }

    public function test_correct_knockout_prediction_shows_checkmark(): void
    {
        $user = User::factory()->create();
        $league = League::factory()->create();
        LeagueMember::factory()->create(['user_id' => $user->id, 'league_id' => $league->id, 'active' => true, 'is_guest' => 0]);

        $team = Team::create(['team' => 'TeamB']);
        $team->forceFill(['last32' => 1])->save();

        PredictionStanding::create(['user_id' => $user->id, 'team_id' => $team->id, 'last32' => 1]);
        PointStanding::create(['user_id' => $user->id, 'team_id' => $team->id, 'last32_points' => 3]);

        Session::put('leagueID', $league->id);

        $response = $this->actingAs($user)->get(route('summary.prediction.standings'));

        $response->assertOk();
        $response->assertSee('✓', false);
    }

    public function test_final_prediction_is_red_when_team_never_reached_semifinal(): void
    {
        $user = User::factory()->create();
        $league = League::factory()->create();
        LeagueMember::factory()->create(['user_id' => $user->id, 'league_id' => $league->id, 'active' => true, 'is_guest' => 0]);

        // Team was eliminated before the semifinal, but the user predicted it would win it all.
        $team = Team::create(['team' => 'TeamC']);
        $team->forceFill(['semifinal' => 0])->save();

        PredictionStanding::create(['user_id' => $user->id, 'team_id' => $team->id, 'final' => 1]);
        PointStanding::create(['user_id' => $user->id, 'team_id' => $team->id, 'final_points' => 0]);

        Session::put('leagueID', $league->id);

        $response = $this->actingAs($user)->get(route('summary.prediction.standings'));

        $response->assertOk();
        $response->assertSee('sst-fin sst-miss', false);
    }

    public function test_final_prediction_is_amber_when_semifinal_correct_but_final_slot_wrong(): void
    {
        $user = User::factory()->create();
        $league = League::factory()->create();
        LeagueMember::factory()->create(['user_id' => $user->id, 'league_id' => $league->id, 'active' => true, 'is_guest' => 0]);

        // Team correctly predicted to reach the semifinal, and it did — but ended up
        // 2nd (runner-up), not champion as predicted.
        $team = Team::create(['team' => 'TeamD']);
        $team->forceFill(['semifinal' => 1, 'final' => 2])->save();

        PredictionStanding::create(['user_id' => $user->id, 'team_id' => $team->id, 'semifinal' => 1, 'final' => 1]);
        PointStanding::create(['user_id' => $user->id, 'team_id' => $team->id, 'final_points' => 0]);

        Session::put('leagueID', $league->id);

        $response = $this->actingAs($user)->get(route('summary.prediction.standings'));

        $response->assertOk();
        $response->assertSee('sst-fin sst-partial', false);
    }

    public function test_final_prediction_is_red_when_semifinal_not_predicted_and_final_slot_wrong(): void
    {
        $user = User::factory()->create();
        $league = League::factory()->create();
        LeagueMember::factory()->create(['user_id' => $user->id, 'league_id' => $league->id, 'active' => true, 'is_guest' => 0]);

        // Team reached the semifinal and placed 2nd, but the user never predicted the
        // semifinal advance at all — only guessed (wrongly) that it would be champion.
        $team = Team::create(['team' => 'TeamE']);
        $team->forceFill(['semifinal' => 1, 'final' => 2])->save();

        PredictionStanding::create(['user_id' => $user->id, 'team_id' => $team->id, 'semifinal' => 0, 'final' => 1]);
        PointStanding::create(['user_id' => $user->id, 'team_id' => $team->id, 'final_points' => 0]);

        Session::put('leagueID', $league->id);

        $response = $this->actingAs($user)->get(route('summary.prediction.standings'));

        $response->assertOk();
        $response->assertSee('sst-fin sst-miss', false);
    }
}
