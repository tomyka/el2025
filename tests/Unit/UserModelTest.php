<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_user_setting_relation(): void
    {
        $user = User::factory()->create();
        $userSetting = UserSetting::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->userSetting()->exists());
        $this->assertSame($userSetting->id, $user->userSetting->id);
    }

    public function test_user_password_is_hashed_by_cast(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->assertNotSame('password', $user->password);
        $this->assertTrue(password_verify('password', $user->password));
    }
}
