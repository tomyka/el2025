<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserGroup;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_update_profile(): void
    {
        $user = User::factory()->create();
        $userSetting = UserSetting::factory()->create(["user_id" => $user->id, "admin" => 0]);

        $response = $this->post(route('admin.updateUser'), [
            'userID' => $user->id,
            'username' => $user->username ?? $user->email,
            'admin' => 1,
        ]);

        $response->assertRedirect(route('admin.users'));

        $this->assertSame(1, $user->refresh()->userSetting->admin);
    }

    public function test_admin_user_delete_user_and_related_data(): void
    {
        $user = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $user->id]);
        UserGroup::factory()->create(['user_id' => $user->id]);

        $response = $this->post(route('admin.deleteUser'), ['userID' => $user->id]);

        $response->assertRedirect(route('admin.users'));

        $this->assertNull(User::find($user->id));
    }
}
