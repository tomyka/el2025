<?php

namespace Tests\Feature;

use App\Http\Controllers\FeeController;
use App\Http\Controllers\PointController;
use App\Http\Controllers\PointResultController;
use App\Http\Controllers\PredictionResultController;
use App\Http\Controllers\PredictionStandingController;
use App\Http\Controllers\PredictionSurvivalController;
use App\Http\Controllers\TeamStatisticsController;
use App\Models\Event;
use App\Models\Game;
use App\Models\League;
use App\Models\LeagueMember;
use App\Models\PointResult;
use App\Models\PointStanding;
use App\Models\PredictionResult;
use App\Models\PredictionStanding;
use App\Models\Team;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class SqlInjectionRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function seedMinimal(): array
    {
        $user   = User::factory()->create();
        $league = League::factory()->create();
        LeagueMember::factory()->create(['user_id' => $user->id, 'league_id' => $league->id, 'active' => true, 'is_guest' => 0]);
        UserSetting::factory()->create(['user_id' => $user->id]);

        $homeTeam = Team::create(['team' => 'TeamA']);
        $awayTeam = Team::create(['team' => 'TeamB']);
        $event = Event::create(['event' => 'Test', 'event_day' => 1, 'event_survival' => 0, 'active' => 1, 'rate' => 1]);
        $game = Game::create([
            'game_date'    => now()->addDay(),
            'event_id'     => $event->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
        ]);
        PredictionResult::create(['user_id' => $user->id, 'game_id' => $game->id]);

        Session::put('timeDifference', 0);
        Session::put('guest', 0);

        return compact('user', 'league', 'event', 'game', 'homeTeam', 'awayTeam');
    }

    public function test_get_prediction_results_user_group_event_day_returns_results(): void
    {
        $data = $this->seedMinimal();

        $controller = new PredictionResultController();
        $results = $controller->getPredictionResultsUserGroupEventDay(
            $data['event']->id,
            $data['league']->id,
            $data['user']->id
        );

        $this->assertCount(1, $results);
        $this->assertEquals($data['game']->id, $results[0]->id);
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
        $results = $controller->getPredictionGamesProfile($data['league']->id, $data['event']->id);

        $this->assertIsArray($results);
    }

    public function test_get_prediction_games_user_result_amount_returns_results(): void
    {
        $data = $this->seedMinimal();

        $controller = new PredictionResultController();
        $results = $controller->getPredictionGamesUserResultAmount($data['user']->id, 10);

        $this->assertIsArray($results);
    }

    public function test_get_prediction_standing_profile_returns_results(): void
    {
        $data = $this->seedMinimal();
        PredictionStanding::create(['user_id' => $data['user']->id, 'team_id' => $data['homeTeam']->id]);

        $controller = new PredictionStandingController();
        $results = $controller->getPredictionStandingProfile($data['league']->id);

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
    }

    public function test_get_prediction_standing_top4_returns_results(): void
    {
        $data = $this->seedMinimal();
        PredictionStanding::create(['user_id' => $data['user']->id, 'team_id' => $data['homeTeam']->id, 'final' => 1]);

        $controller = new PredictionStandingController();
        $results = $controller->getPredictionStandingTop4($data['league']->id);

        $this->assertIsArray($results);
    }

    public function test_get_prediction_standings_user_points_returns_results(): void
    {
        $data = $this->seedMinimal();
        PointStanding::create([
            'user_id'               => $data['user']->id,
            'team_id'               => $data['homeTeam']->id,
            'group_position_points' => 10,
            'last16_points'         => 0,
            'quarterfinal_points'   => 0,
            'semifinal_points'      => 0,
            'final_points'          => 0,
        ]);

        $controller = new PointController();
        $results = $controller->getPredictionStandingsUserPoints($data['user']->id);

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
    }

    public function test_get_user_details_returns_counts(): void
    {
        $data = $this->seedMinimal();
        Session::put('leagueID', $data['league']->id);

        $controller = new FeeController();
        $result = $controller->getUserDetails();

        $this->assertObjectHasProperty('users', $result);
        $this->assertObjectHasProperty('usersActive', $result);
    }

    public function test_get_fund_collected_returns_numeric(): void
    {
        $data = $this->seedMinimal();
        Session::put('leagueID', $data['league']->id);

        $controller = new FeeController();
        $result = $controller->getFundCollected();

        $this->assertIsNumeric($result);
    }

    public function test_get_prediction_survival_user_event_id_returns_results(): void
    {
        $data = $this->seedMinimal();
        Session::put('timeDifference', 0);

        $controller = new PredictionSurvivalController();
        $results = $controller->getPredictionSurvivalUserEventID($data['user']->id, $data['event']->id);

        $this->assertIsArray($results);
    }

    public function test_get_team_statistics_returns_stats(): void
    {
        $data = $this->seedMinimal();

        $controller = new TeamStatisticsController();
        $result = $controller->getTeamStatistics($data['homeTeam']->id);

        $this->assertObjectHasProperty('gameCount', $result);
        $this->assertObjectHasProperty('won', $result);
    }

    public function test_delete_point_result_game_points_deletes_records(): void
    {
        $data = $this->seedMinimal();
        PointResult::create([
            'user_id'           => $data['user']->id,
            'game_id'           => $data['game']->id,
            'winner_points'     => 50,
            'difference_points' => 40,
            'bingo_points'      => 0,
            'odds'              => 1.5,
            'odds_points'       => 25,
            'full_points'       => 115,
        ]);

        $controller = app(PointResultController::class);
        $controller->deletePointResultGamePoints($data['game']->id);

        $this->assertDatabaseMissing('point_results', ['game_id' => $data['game']->id]);
    }
}
