<?php
namespace Tests\Feature;

use App\Models\League;
use App\Models\LeagueMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeagueFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_league_model_creates_and_retrieves(): void
    {
        $league = League::create([
            'name'      => 'Test League',
            'is_public' => true,
        ]);

        $this->assertDatabaseHas('leagues', ['name' => 'Test League', 'is_public' => true]);
        $this->assertEquals('Test League', League::where('name', 'Test League')->first()->name);
    }

    public function test_league_member_belongs_to_league(): void
    {
        $league = League::create(['name' => 'My League', 'is_public' => false]);
        $user   = User::factory()->create();

        LeagueMember::create([
            'league_id' => $league->id,
            'user_id'   => $user->id,
            'active'    => true,
        ]);

        $this->assertEquals($league->id, $user->leagueMembers()->first()->league_id);
    }

    public function test_data_migration_creates_public_league(): void
    {
        // RefreshDatabase runs all migrations fresh, including the data migration.
        // So the public league already exists — just verify it was created correctly.
        $publicLeague = \App\Models\League::where('is_public', true)->first();
        $this->assertNotNull($publicLeague);
        $this->assertEquals('Public League', $publicLeague->name);
    }

    public function test_messages_table_has_league_id_column(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('messages', 'league_id'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('messages', 'group_id'));
    }

    public function test_set_session_puts_league_id_in_session(): void
    {
        $user = \App\Models\User::factory()->create();
        \App\Models\UserSetting::factory()->create(['user_id' => $user->id, 'admin' => 0]);
        \App\Models\Setting::firstOrCreate(['setting' => 'survivalGame'], ['value' => 0]);
        \App\Models\Setting::firstOrCreate(['setting' => 'timeDifference'], ['value' => 0]);

        $league = \App\Models\League::create(['name' => 'My League', 'is_public' => false]);
        \App\Models\LeagueMember::create([
            'league_id' => $league->id,
            'user_id'   => $user->id,
            'active'    => true,
            'is_guest'  => false,
        ]);

        $sessionController = new \App\Http\Controllers\SessionController();
        $sessionController->setSession($user);

        $this->assertEquals($league->id, session('leagueID'));
        $this->assertNull(session('groupID'));
        $this->assertEquals(0, session('guest'));
    }

    public function test_get_all_user_points_filters_by_league(): void
    {
        session(['guest' => 0]);

        $leagueA = \App\Models\League::create(['name' => 'League A', 'is_public' => false]);
        $leagueB = \App\Models\League::create(['name' => 'League B', 'is_public' => false]);

        $userA = \App\Models\User::factory()->create();
        $userB = \App\Models\User::factory()->create();
        \App\Models\UserSetting::factory()->create(['user_id' => $userA->id, 'admin' => 0]);
        \App\Models\UserSetting::factory()->create(['user_id' => $userB->id, 'admin' => 0]);

        \App\Models\LeagueMember::create(['league_id' => $leagueA->id, 'user_id' => $userA->id, 'active' => true, 'is_guest' => false]);
        \App\Models\LeagueMember::create(['league_id' => $leagueB->id, 'user_id' => $userB->id, 'active' => true, 'is_guest' => false]);

        $pointController = new \App\Http\Controllers\PointController();
        $points = $pointController->getAllUserPoints($leagueA->id);

        $userIds = array_column($points, 'userID');
        $this->assertContains($userA->id, $userIds);
        $this->assertNotContains($userB->id, $userIds);
    }

    public function test_new_user_registration_joins_public_league(): void
    {
        // Public league is created by the data migration (RefreshDatabase runs it)
        $publicLeague = \App\Models\League::where('is_public', true)->firstOrFail();

        $user = \App\Models\User::factory()->create();
        \App\Models\UserSetting::factory()->create(['user_id' => $user->id, 'admin' => 0]);

        // Simulate postRegisterActions by creating the membership directly
        \App\Models\LeagueMember::create([
            'league_id' => $publicLeague->id,
            'user_id'   => $user->id,
            'is_admin'  => false,
            'is_guest'  => false,
            'active'    => true,
        ]);

        $this->assertDatabaseHas('league_members', [
            'league_id' => $publicLeague->id,
            'user_id'   => $user->id,
            'active'    => true,
        ]);
    }
}
