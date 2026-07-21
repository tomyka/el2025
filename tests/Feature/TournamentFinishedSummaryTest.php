<?php

namespace Tests\Feature;

use App\Http\Controllers\PointSurvivalController;
use App\Http\Controllers\PredictionResultController;
use App\Http\Controllers\PredictionSurvivalController;
use App\Models\Event;
use App\Models\Game;
use App\Models\League;
use App\Models\LeagueMember;
use App\Models\PredictionResult;
use App\Models\Team;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * session('eventID') is 0 once the tournament is fully scored (no
 * unscored games left - see SessionController::setSession()). Several
 * summary queries used session('eventID') directly as an upper bound
 * ("only show events up to the current one"), which meant a finished
 * tournament silently filtered out every event instead of showing them
 * all - the summary pages rendered empty.
 */
class TournamentFinishedSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_prediction_results_summary_shows_scores_after_tournament_finished(): void
    {
        $user = User::factory()->create();
        $league = League::factory()->create();
        LeagueMember::factory()->create(['user_id' => $user->id, 'league_id' => $league->id, 'active' => true, 'is_guest' => 0]);
        UserSetting::factory()->create(['user_id' => $user->id]);

        $event = Event::create(['event' => 'Final', 'event_day' => 1, 'event_survival' => 0, 'active' => 1, 'rate' => 1]);
        $home = Team::create(['team' => 'TeamA']);
        $away = Team::create(['team' => 'TeamB']);
        $game = Game::create([
            'game_date' => now()->subDay(),
            'event_id' => $event->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'home_team_score' => 2,
            'away_team_score' => 1,
        ]);
        PredictionResult::create(['user_id' => $user->id, 'game_id' => $game->id, 'home_team_score' => 2, 'away_team_score' => 1]);

        Session::put('guest', 0);

        // Tournament finished: no unscored games left, session('eventID') is 0.
        $results = (new PredictionResultController)->getPredictionGamesProfile($league->id, 0);

        $this->assertNotEmpty($results, 'Prediction results must still show once the tournament has finished (eventID=0).');
        $this->assertEquals($game->id, $results[0]->game_id);
    }

    public function test_survival_summary_shows_events_after_tournament_finished(): void
    {
        $event = Event::create(['event' => 'Final', 'event_day' => 1, 'event_survival' => 1, 'active' => 1, 'rate' => 1]);
        $user = User::factory()->create();

        Session::put('eventID', 0);

        $events = (new PredictionSurvivalController)->getPredictionSurvivalSummary()->getData()['events'];

        $this->assertTrue($events->contains('id', $event->id), 'Survival summary must still list events once the tournament has finished (eventID=0).');
    }

    public function test_point_survival_event_id_returns_events_after_tournament_finished(): void
    {
        $event = Event::create(['event' => 'Final', 'event_day' => 1, 'event_survival' => 1, 'active' => 1, 'rate' => 1]);
        User::factory()->create();

        $rows = (new PointSurvivalController)->getPointSurvivalEventID(0);

        $this->assertTrue(collect($rows)->contains('event_id', $event->id), 'Point survival lookup must still include events once the tournament has finished (eventID=0).');
    }
}
