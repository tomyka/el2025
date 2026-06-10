<?php

namespace Tests\Feature;

use App\Models\LeagueMember;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_update_profile(): void
    {
        $admin = User::factory()->create();
        $targetUser = User::factory()->create();
        $userSetting = UserSetting::factory()->create(['user_id' => $targetUser->id, 'admin' => 0]);

        $response = $this->actingAs($admin)
            ->withSession(['admin' => 1])
            ->post(route('admin.updateUser'), [
                'userID' => $targetUser->id,
                'username' => $targetUser->email,
                'admin' => 1,
            ]);

        $response->assertRedirect(route('admin.users'));
        $this->assertSame(1, $targetUser->refresh()->userSetting->admin);
    }

    public function test_admin_user_delete_user_and_related_data(): void
    {
        $admin = User::factory()->create();
        $targetUser = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $targetUser->id]);
        LeagueMember::factory()->create(['user_id' => $targetUser->id]);

        $response = $this->actingAs($admin)
            ->withSession(['admin' => 1])
            ->post(route('admin.deleteUser'), ['userID' => $targetUser->id]);

        $response->assertRedirect(route('admin.users'));
        $this->assertNull(User::find($targetUser->id));
    }
}
