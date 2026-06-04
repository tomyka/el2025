<?php

namespace Tests\Feature;

use App\Http\Controllers\PredictionResultController;
use App\Models\Event;
use App\Models\Game;
use App\Models\Group;
use App\Models\PredictionResult;
use App\Models\Team;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class SqlInjectionRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function seedMinimal(): array
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        UserGroup::factory()->create(['user_id' => $user->id, 'group_id' => $group->id, 'active' => true, 'guest' => 0]);
        UserSetting::factory()->create(['user_id' => $user->id]);

        $homeTeam = Team::create(['team' => 'TeamA']);
        $awayTeam = Team::create(['team' => 'TeamB']);
        $event = Event::create(['event' => 'Test', 'event_day' => 1, 'event_survival' => 0, 'active' => 1, 'rate' => 1]);
        $game = Game::create([
            'game_date' => now()->addDay(),
            'event_id' => $event->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
        ]);
        PredictionResult::create(['user_id' => $user->id, 'game_id' => $game->id]);

        Session::put('timeDifference', 0);
        Session::put('guest', 0);

        return compact('user', 'group', 'event', 'game', 'homeTeam', 'awayTeam');
    }

    public function test_get_prediction_results_user_group_event_day_returns_results(): void
    {
        $data = $this->seedMinimal();

        $controller = new PredictionResultController();
        $results = $controller->getPredictionResultsUserGroupEventDay(
            $data['event']->id,
            $data['group']->id,
            $data['user']->id
        );

        $this->assertCount(1, $results);
        $this->assertEquals($data['game']->id, $results[0]->id);
    }

    public function test_get_prediction_games_user_history_returns_results(): void
    {
        $data = $this->seedMinimal();

        $game = $data['game'];
        $game->home_team_score = 75;
        $game->away_team_score = 65;
        $game->save();

        $futureEvent = Event::create(['event' => 'Future', 'event_day' => 2, 'event_survival' => 0, 'active' => 1, 'rate' => 1]);

        $controller = new PredictionResultController();
        $results = $controller->getPredictionGamesUserHistory(
            $data['user']->id,
            $futureEvent->id
        );

        $this->assertCount(1, $results);
        $this->assertEquals($data['game']->id, $results[0]->game_id);
    }

    public function test_get_prediction_games_profile_returns_results(): void
    {
        $data = $this->seedMinimal();

        $game = $data['game'];
        $game->game_date = now()->subDay();
        $game->home_team_score = 80;
        $game->away_team_score = 70;
        $game->save();

        $controller = new PredictionResultController();
        $results = $controller->getPredictionGamesProfile($data['group']->id, $data['event']->id);

        $this->assertIsArray($results);
    }

    public function test_get_prediction_games_user_result_amount_returns_results(): void
    {
        $data = $this->seedMinimal();

        $controller = new PredictionResultController();
        $results = $controller->getPredictionGamesUserResultAmount($data['user']->id, 10);

        $this->assertIsArray($results);
    }
}
