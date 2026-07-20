<?php

namespace Tests\Feature;

use App\Http\Controllers\PredictionStandingController;
use App\Http\Requests\UpdatePredictionStandingRequest;
use App\Models\League;
use App\Models\LeagueMember;
use App\Models\PredictionStanding;
use App\Models\Team;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class PredictionStandingLockTest extends TestCase
{
    use RefreshDatabase;

    private function seedData(): array
    {
        $user   = User::factory()->create();
        $league = League::factory()->create();
        LeagueMember::factory()->create(['user_id' => $user->id, 'league_id' => $league->id, 'active' => true, 'is_guest' => 0]);
        UserSetting::factory()->create(['user_id' => $user->id]);

        $team              = Team::create(['team' => 'TeamA']);
        $predictionStanding = PredictionStanding::create(['user_id' => $user->id, 'team_id' => $team->id, 'group_position' => 1]);

        Session::put('userID', $user->id);

        return compact('user', 'predictionStanding');
    }

    private function makeRequest(int $predictionStandingID, int $groupPosition): UpdatePredictionStandingRequest
    {
        return UpdatePredictionStandingRequest::create('/prediction/standings', 'POST', [
            'prediction_standingID' => $predictionStandingID,
            'groupPosition'         => $groupPosition,
            'last32'                => 1,
            'last16'                => null,
            'quarterfinal'          => null,
            'semifinal'             => null,
            'final'                 => null,
        ]);
    }

    public function test_update_is_rejected_once_predictions_are_disabled(): void
    {
        ['predictionStanding' => $predictionStanding] = $this->seedData();
        Session::put('disabled', 'disabled');

        (new PredictionStandingController())->updatePredictionStandingsUser(
            $this->makeRequest($predictionStanding->id, 3)
        );

        $this->assertEquals(1, $predictionStanding->fresh()->group_position);
    }

    public function test_update_is_saved_when_predictions_are_open(): void
    {
        ['predictionStanding' => $predictionStanding] = $this->seedData();
        Session::put('disabled', '');

        (new PredictionStandingController())->updatePredictionStandingsUser(
            $this->makeRequest($predictionStanding->id, 3)
        );

        $this->assertEquals(3, $predictionStanding->fresh()->group_position);
    }
}
