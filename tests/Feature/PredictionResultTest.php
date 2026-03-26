<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\PredictionResult;
use App\Models\Team;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictionResultTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_make_prediction_on_game(): void
    {
        $user = User::factory()->create();
        $group = \App\Models\Group::factory()->create();
        UserGroup::factory()->create(['user_id' => $user->id, 'group_id' => $group->id]);

        $event = Event::create([
            'event' => 'Euro 2024 Round of 16',
            'event_day' => 2,
            'event_survival' => 0,
            'active' => 1,
            'rate' => 1,
        ]);

        $homeTeam = Team::create(['team' => 'France', 'group_name' => 'A']);
        $awayTeam = Team::create(['team' => 'Poland', 'group_name' => 'A']);

        $game = Game::create([
            'game_date' => now()->addDay(),
            'event_id' => $event->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
        ]);

        $prediction = PredictionResult::create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'home_team_score' => 2,
            'away_team_score' => 0,
        ]);

        $this->assertDatabaseHas('prediction_results', [
            'user_id' => $user->id,
            'game_id' => $game->id,
            'home_team_score' => 2,
            'away_team_score' => 0,
        ]);
    }

    public function test_multiple_users_can_predict_same_game(): void
    {
        $group = \App\Models\Group::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        UserGroup::factory()->create(['user_id' => $user1->id, 'group_id' => $group->id]);
        UserGroup::factory()->create(['user_id' => $user2->id, 'group_id' => $group->id]);

        $event = Event::create([
            'event' => 'Euro 2024',
            'event_day' => 1,
            'event_survival' => 0,
            'active' => 1,
            'rate' => 1,
        ]);

        $homeTeam = Team::create(['team' => 'Italy', 'group_name' => 'B']);
        $awayTeam = Team::create(['team' => 'England', 'group_name' => 'B']);

        $game = Game::create([
            'game_date' => now()->addDays(2),
            'event_id' => $event->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
        ]);

        PredictionResult::create([
            'user_id' => $user1->id,
            'game_id' => $game->id,
            'home_team_score' => 1,
            'away_team_score' => 1,
        ]);

        PredictionResult::create([
            'user_id' => $user2->id,
            'game_id' => $game->id,
            'home_team_score' => 2,
            'away_team_score' => 0,
        ]);

        $this->assertCount(2, PredictionResult::where('game_id', $game->id)->get());
    }

    public function test_user_can_update_prediction_before_game(): void
    {
        $user = User::factory()->create();
        $group = \App\Models\Group::factory()->create();
        UserGroup::factory()->create(['user_id' => $user->id, 'group_id' => $group->id]);

        $event = Event::create([
            'event' => 'Euro 2024 Final',
            'event_day' => 10,
            'event_survival' => 0,
            'active' => 1,
            'rate' => 1,
        ]);

        $homeTeam = Team::create(['team' => 'France']);
        $awayTeam = Team::create(['team' => 'Spain']);

        $game = Game::create([
            'game_date' => now()->addDays(5),
            'event_id' => $event->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
        ]);

        $prediction = PredictionResult::create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'home_team_score' => 1,
            'away_team_score' => 0,
        ]);

        $prediction->update([
            'home_team_score' => 2,
            'away_team_score' => 1,
        ]);

        $this->assertSame(2, $prediction->fresh()->home_team_score);
        $this->assertSame(1, $prediction->fresh()->away_team_score);
    }
}
