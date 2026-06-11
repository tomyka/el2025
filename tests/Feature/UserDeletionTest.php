<?php

namespace Tests\Feature;

use App\Models\League;
use App\Models\LeagueMember;
use App\Models\PointResult;
use App\Models\PointStanding;
use App\Models\PredictionResult;
use App\Models\PredictionStanding;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $admin->id, 'admin' => 9]);

        return $admin;
    }

    public function test_non_admin_cannot_delete_user(): void
    {
        $actor  = User::factory()->create();
        $target = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $actor->id, 'admin' => 0]);

        $this->actingAs($actor)
            ->withSession(['admin' => 0, 'userID' => $actor->id])
            ->post(route('admin.deleteUser'), ['userID' => $target->id])
            ->assertRedirect(route('admin.index'));
    }

    public function test_admin_can_delete_user_and_cascade(): void
    {
        $admin  = $this->makeAdmin();
        $target = User::factory()->create();
        $league = League::factory()->create();

        LeagueMember::factory()->create(['user_id' => $target->id, 'league_id' => $league->id]);
        UserSetting::factory()->create(['user_id' => $target->id]);

        $this->actingAs($admin)
            ->withSession(['admin' => 9, 'leagueID' => $league->id, 'userID' => $admin->id])
            ->post(route('admin.deleteUser'), ['userID' => $target->id])
            ->assertRedirect(route('admin.users'));

        $this->assertNull(User::find($target->id));
        $this->assertDatabaseMissing('league_members', ['user_id' => $target->id]);
        $this->assertDatabaseMissing('user_settings', ['user_id' => $target->id]);
    }

    public function test_delete_also_removes_prediction_and_point_records(): void
    {
        $admin  = $this->makeAdmin();
        $target = User::factory()->create();
        $league = League::factory()->create();

        LeagueMember::factory()->create(['user_id' => $target->id, 'league_id' => $league->id]);
        UserSetting::factory()->create(['user_id' => $target->id]);

        $this->actingAs($admin)
            ->withSession(['admin' => 9, 'leagueID' => $league->id, 'userID' => $admin->id])
            ->post(route('admin.deleteUser'), ['userID' => $target->id]);

        $this->assertDatabaseMissing('prediction_results',  ['user_id' => $target->id]);
        $this->assertDatabaseMissing('prediction_standings', ['user_id' => $target->id]);
        $this->assertDatabaseMissing('point_results',       ['user_id' => $target->id]);
        $this->assertDatabaseMissing('point_standings',     ['user_id' => $target->id]);
    }
}
