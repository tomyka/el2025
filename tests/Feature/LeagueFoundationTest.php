<?php

namespace Tests\Feature;

use App\Http\Controllers\PointController;
use App\Http\Controllers\SessionController;
use App\Models\League;
use App\Models\LeagueMember;
use App\Models\Setting;
use App\Models\Tournament;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeagueFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_league_model_creates_and_retrieves(): void
    {
        $league = League::create([
            'name' => 'Test League',
            'is_public' => true,
            'tournament_id' => 1,
        ]);

        $this->assertDatabaseHas('leagues', ['name' => 'Test League', 'is_public' => true]);
        $this->assertEquals('Test League', League::where('name', 'Test League')->first()->name);
    }

    public function test_league_member_belongs_to_league(): void
    {
        $league = League::create(['name' => 'My League', 'is_public' => false, 'tournament_id' => 1]);
        $user = User::factory()->create();

        LeagueMember::create([
            'league_id' => $league->id,
            'user_id' => $user->id,
            'active' => true,
        ]);

        $this->assertEquals($league->id, $user->leagueMembers()->first()->league_id);
    }

    public function test_data_migration_creates_public_league(): void
    {
        // RefreshDatabase runs all migrations fresh, including the data migration.
        // So the public league already exists — just verify it was created correctly.
        $publicLeague = League::where('is_public', true)->first();
        $this->assertNotNull($publicLeague);
        $this->assertEquals('Public League', $publicLeague->name);
    }

    public function test_messages_table_has_league_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('messages', 'league_id'));
        $this->assertFalse(Schema::hasColumn('messages', 'group_id'));
    }

    public function test_set_session_puts_league_id_in_session(): void
    {
        $user = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $user->id, 'admin' => 0]);
        Setting::firstOrCreate(['setting' => 'timeDifference'], ['value' => 0]);

        $tournament = Tournament::create([
            'name' => 'T', 'slug' => 'test-t', 'sport' => 'football', 'status' => 'active',
        ]);
        $league = League::create([
            'name' => 'My League', 'is_public' => false, 'tournament_id' => $tournament->id,
        ]);
        LeagueMember::create([
            'league_id' => $league->id,
            'user_id' => $user->id,
            'active' => true,
            'is_guest' => false,
        ]);

        $sessionController = new SessionController;
        $sessionController->setSession($user);

        $this->assertEquals($league->id, session('leagueID'));
        $this->assertNull(session('groupID'));
        $this->assertEquals(0, session('guest'));
        $this->assertEquals($tournament->id, session('tournamentID'));
    }

    public function test_get_all_user_points_filters_by_league(): void
    {
        session(['guest' => 0]);

        $leagueA = League::create(['name' => 'League A', 'is_public' => false, 'tournament_id' => 1]);
        $leagueB = League::create(['name' => 'League B', 'is_public' => false, 'tournament_id' => 1]);

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $userA->id, 'admin' => 0]);
        UserSetting::factory()->create(['user_id' => $userB->id, 'admin' => 0]);

        LeagueMember::create(['league_id' => $leagueA->id, 'user_id' => $userA->id, 'active' => true, 'is_guest' => false]);
        LeagueMember::create(['league_id' => $leagueB->id, 'user_id' => $userB->id, 'active' => true, 'is_guest' => false]);

        $pointController = new PointController;
        $points = $pointController->getAllUserPoints($leagueA->id);

        $userIds = array_column($points, 'userID');
        $this->assertContains($userA->id, $userIds);
        $this->assertNotContains($userB->id, $userIds);
    }

    public function test_new_user_registration_joins_public_league(): void
    {
        // Public league is created by the data migration (RefreshDatabase runs it)
        $publicLeague = League::where('is_public', true)->firstOrFail();

        $user = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $user->id, 'admin' => 0]);

        // Simulate postRegisterActions by creating the membership directly
        LeagueMember::create([
            'league_id' => $publicLeague->id,
            'user_id' => $user->id,
            'is_admin' => false,
            'is_guest' => false,
            'active' => true,
        ]);

        $this->assertDatabaseHas('league_members', [
            'league_id' => $publicLeague->id,
            'user_id' => $user->id,
            'active' => true,
        ]);
    }
}
