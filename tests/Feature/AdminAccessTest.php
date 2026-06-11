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
}
