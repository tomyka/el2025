<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(int $adminLevel): User
    {
        $user = User::factory()->create();
        $setting = new UserSetting;
        $setting->user_id = $user->id;
        $setting->admin = $adminLevel;
        $setting->save();

        return $user;
    }

    public function test_unauthenticated_blocked_from_audit(): void
    {
        $this->get(route('admin.audit'))->assertRedirect(route('login'));
    }

    public function test_level1_blocked_from_audit(): void
    {
        $user = $this->makeUser(1);
        $this->actingAs($user)
            ->withSession(['userID' => $user->id])
            ->get(route('admin.audit'))
            ->assertRedirect(route('admin.index'));
    }

    public function test_level5_allowed_on_audit(): void
    {
        $user = $this->makeUser(5);
        $this->actingAs($user)
            ->withSession(['userID' => $user->id])
            ->get(route('admin.audit'))
            ->assertOk();
    }

    public function test_level9_allowed_on_audit(): void
    {
        $user = $this->makeUser(9);
        $this->actingAs($user)
            ->withSession(['userID' => $user->id])
            ->get(route('admin.audit'))
            ->assertOk();
    }

    public function test_user_filter_returns_ok(): void
    {
        $admin = $this->makeUser(5);
        $target = $this->makeUser(0);

        $this->actingAs($admin)
            ->withSession(['userID' => $admin->id])
            ->get(route('admin.audit', ['user' => $target->id]))
            ->assertOk();
    }
}
