<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\League;
use App\Models\LeagueMember;
use App\Models\PredictionResult;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditPredictionTest extends TestCase
{
    use RefreshDatabase;

    private function setupGame(): array
    {
        $user   = User::factory()->create();
        $league = League::factory()->create();
        LeagueMember::factory()->create(['user_id' => $user->id, 'league_id' => $league->id]);

        $event = Event::create([
            'event'          => 'Test Event',
            'event_day'      => 1,
            'event_survival' => 0,
            'rate'           => 1,
        ]);
        $home = Team::create(['team' => 'Home FC', 'group_name' => 'A']);
        $away = Team::create(['team' => 'Away FC', 'group_name' => 'A']);
        $game = Game::create([
            'game_date'    => now()->addDay(),
            'event_id'     => $event->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
        ]);

        return compact('user', 'game', 'home');
    }

    public function test_prediction_change_records_old_and_new_values(): void
    {
        ['user' => $user, 'game' => $game, 'home' => $home] = $this->setupGame();

        $prediction = PredictionResult::create([
            'user_id'         => $user->id,
            'game_id'         => $game->id,
            'home_team_score' => 1,
            'away_team_score' => 0,
            'game_winner_id'  => $home->id,
        ]);

        $this->actingAs($user)
            ->withSession(['userID' => $user->id])
            ->postJson(route('prediction.results'), [
                'gameID'            => $game->id,
                'prediction_gameID' => $prediction->id,
                'homeTeamScore'     => 3,
                'awayTeamScore'     => 1,
                'gameWinnerID'      => $home->id,
            ]);

        $this->assertDatabaseHas('audit_prediction_games', [
            'user_id'             => $user->id,
            'game_id'             => $game->id,
            'old_home_team_score' => 1,
            'old_away_team_score' => 0,
            'home_team_score'     => 3,
            'away_team_score'     => 1,
        ]);
    }

    public function test_first_prediction_has_null_old_values(): void
    {
        ['user' => $user, 'game' => $game, 'home' => $home] = $this->setupGame();

        $prediction = PredictionResult::create([
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);

        $this->actingAs($user)
            ->withSession(['userID' => $user->id])
            ->postJson(route('prediction.results'), [
                'gameID'            => $game->id,
                'prediction_gameID' => $prediction->id,
                'homeTeamScore'     => 2,
                'awayTeamScore'     => 1,
                'gameWinnerID'      => $home->id,
            ]);

        $this->assertDatabaseHas('audit_prediction_games', [
            'user_id'             => $user->id,
            'game_id'             => $game->id,
            'old_home_team_score' => null,
            'old_away_team_score' => null,
            'home_team_score'     => 2,
            'away_team_score'     => 1,
        ]);
    }
}
