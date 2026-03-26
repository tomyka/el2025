<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProfileService::class);
    }

    public function test_get_profile_returns_user(): void
    {
        $user = User::factory()->create();

        $profile = $this->service->getProfile($user);

        $this->assertSame($user->id, $profile->id);
        $this->assertSame($user->email, $profile->email);
    }

    public function test_update_profile_updates_user_details(): void
    {
        $user = User::factory()->create([
            'username' => 'olduser',
            'name' => 'Old Name',
        ]);

        $updated = $this->service->updateProfile($user, [
            'username' => 'newuser',
            'name' => 'New Name',
            'surname' => 'Updated',
            'email' => 'new@example.com',
        ]);

        $this->assertSame('newuser', $updated->username);
        $this->assertSame('New Name', $updated->name);
        $this->assertSame('new@example.com', $updated->email);
    }

    public function test_delete_account_requires_correct_password(): void
    {
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        $this->service->deleteAccount($user, 'wrong-password');
    }

    public function test_delete_account_with_correct_password(): void
    {
        $user = User::factory()->create();

        $this->service->deleteAccount($user, 'password');

        $this->assertNull(User::find($user->id));
    }
}
