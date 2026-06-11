<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(int $adminLevel): User
    {
        $user = User::factory()->create();
        $setting = new UserSetting();
        $setting->user_id = $user->id;
        $setting->admin = $adminLevel;
        $setting->save();
        return $user;
    }

    // ── Unauthenticated ───────────────────────────────────────────────

    public function test_unauthenticated_blocked_from_admin(): void
    {
        $this->get(route('admin.index'))->assertRedirect(route('login'));
    }

    public function test_unauthenticated_blocked_from_superadmin(): void
    {
        $this->get(route('admin.users'))->assertRedirect(route('login'));
    }

    // ── Level 0: non-admin ───────────────────────────────────────────

    public function test_level0_blocked_from_admin(): void
    {
        $user = $this->makeUser(0);

        $this->actingAs($user)
            ->withSession(['userID' => $user->id])
            ->get(route('admin.index'))
            ->assertRedirect('/');
    }

    // ── Level 1: basic admin ─────────────────────────────────────────

    public function test_level1_allowed_on_admin_dashboard(): void
    {
        $user = $this->makeUser(1);

        $this->actingAs($user)
            ->withSession(['userID' => $user->id])
            ->get(route('admin.index'))
            ->assertOk();
    }

    public function test_level1_blocked_from_superadmin_routes(): void
    {
        $user = $this->makeUser(1);

        $this->actingAs($user)
            ->withSession(['userID' => $user->id])
            ->get(route('admin.users'))
            ->assertRedirect(route('admin.index'));
    }

    // ── Level 5: superadmin ──────────────────────────────────────────

    public function test_level5_allowed_on_superadmin_routes(): void
    {
        $user = $this->makeUser(5);

        $this->actingAs($user)
            ->withSession(['userID' => $user->id])
            ->get(route('admin.users'))
            ->assertOk();
    }

    public function test_level5_blocked_from_delete_user(): void
    {
        $admin = $this->makeUser(5);
        $target = $this->makeUser(0);

        $this->actingAs($admin)
            ->withSession(['userID' => $admin->id])
            ->post(route('admin.deleteUser'), ['userID' => $target->id])
            ->assertRedirect(route('admin.index'));

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    // ── Level 9: full access ─────────────────────────────────────────

    public function test_level9_can_delete_user(): void
    {
        $admin  = $this->makeUser(9);
        $target = $this->makeUser(0);

        $this->actingAs($admin)
            ->withSession(['userID' => $admin->id])
            ->post(route('admin.deleteUser'), ['userID' => $target->id])
            ->assertRedirect(route('admin.users'));

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    // ── Level 5 blocked from leagues delete ──────────────────────────────

    public function test_level5_blocked_from_league_delete(): void
    {
        $admin  = $this->makeUser(5);
        $league = \App\Models\League::factory()->create(['is_public' => false]);

        $this->actingAs($admin)
            ->withSession(['userID' => $admin->id])
            ->post(route('admin.leagues.delete'), ['leagueID' => $league->id])
            ->assertRedirect(route('admin.index'));

        $this->assertDatabaseHas('leagues', ['id' => $league->id]);
    }

    public function test_level9_can_delete_league(): void
    {
        $admin  = $this->makeUser(9);
        $league = \App\Models\League::factory()->create(['is_public' => false]);

        $this->actingAs($admin)
            ->withSession(['userID' => $admin->id])
            ->post(route('admin.leagues.delete'), ['leagueID' => $league->id])
            ->assertRedirect();

        $this->assertDatabaseMissing('leagues', ['id' => $league->id]);
    }

    // ── Level-9 event delete ─────────────────────────────────────────────

    public function test_level5_blocked_from_event_delete(): void
    {
        $admin = $this->makeUser(5);
        $event = \App\Models\Event::create([
            'event'          => 'Test Event',
            'event_day'      => 1,
            'event_survival' => 0,
            'active'         => 1,
            'rate'           => 1,
        ]);

        $this->actingAs($admin)
            ->withSession(['userID' => $admin->id])
            ->post(route('admin.events'), ['delete' => 1, 'eventID' => $event->id])
            ->assertRedirect(route('admin.events'));

        $this->assertDatabaseHas('events', ['id' => $event->id]);
    }

    public function test_level9_can_delete_event(): void
    {
        $admin = $this->makeUser(9);
        $event = \App\Models\Event::create([
            'event'          => 'Test Event',
            'event_day'      => 1,
            'event_survival' => 0,
            'active'         => 1,
            'rate'           => 1,
        ]);

        $this->actingAs($admin)
            ->withSession(['userID' => $admin->id])
            ->post(route('admin.events'), ['delete' => 1, 'eventID' => $event->id])
            ->assertRedirect(route('admin.events'));

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }
}
