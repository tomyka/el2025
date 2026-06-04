<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\Group;
use App\Models\PredictionResult;
use App\Models\Team;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_prediction_result_rejects_score_above_maximum(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('prediction.results'), [
                'gameID' => 1,
                'prediction_gameID' => 1,
                'homeTeamScore' => 200,
                'awayTeamScore' => 80,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['homeTeamScore']);
    }

    public function test_update_prediction_result_rejects_score_below_minimum(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('prediction.results'), [
                'gameID' => 1,
                'prediction_gameID' => 1,
                'homeTeamScore' => 10,
                'awayTeamScore' => 80,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['homeTeamScore']);
    }

    public function test_update_prediction_result_accepts_valid_scores(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        UserGroup::factory()->create(['user_id' => $user->id, 'group_id' => $group->id]);

        $homeTeam = Team::create(['team' => 'TeamA']);
        $awayTeam = Team::create(['team' => 'TeamB']);
        $event = Event::create(['event' => 'Test', 'event_day' => 1, 'event_survival' => 0, 'active' => 1, 'rate' => 1]);
        $game = Game::create([
            'game_date' => now()->addDay(),
            'event_id' => $event->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
        ]);
        $prediction = PredictionResult::create(['user_id' => $user->id, 'game_id' => $game->id]);

        $response = $this->actingAs($user)
            ->withSession(['userID' => $user->id, 'timeDifference' => 0])
            ->postJson(route('prediction.results'), [
                'gameID' => $game->id,
                'prediction_gameID' => $prediction->id,
                'homeTeamScore' => 75,
                'awayTeamScore' => 65,
                'gameWinnerID' => $homeTeam->id,
            ]);

        $response->assertJson(['success' => true]);
    }

    public function test_update_prediction_standing_rejects_invalid_final_position(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('prediction.standings'), [
                'prediction_standingID' => 1,
                'final' => 10,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['final']);
    }

    public function test_update_prediction_standing_rejects_missing_standing_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('prediction.standings'), [
                'groupPosition' => 1,
                'final' => 1,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['prediction_standingID']);
    }

    public function test_update_result_rejects_missing_game_id(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)
            ->withSession(['admin' => 1])
            ->postJson(route('admin.updateResult'), [
                'homeTeamScore' => 80,
                'awayTeamScore' => 70,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['gameID']);
    }

    public function test_update_result_rejects_negative_scores(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)
            ->withSession(['admin' => 1])
            ->postJson(route('admin.updateResult'), [
                'gameID' => 1,
                'homeTeamScore' => -1,
                'awayTeamScore' => 70,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['homeTeamScore']);
    }
}
