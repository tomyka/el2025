<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_admin(): void
    {
        $response = $this->get(route('admin.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_non_admin_is_redirected_to_home(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.index'));

        $response->assertRedirect('/');
    }

    public function test_authenticated_admin_can_access_admin_routes(): void
    {
        $user = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $user->id, 'admin' => 1]);

        $response = $this->actingAs($user)
            ->withSession(['admin' => 1, 'eventID' => 0, 'userID' => $user->id])
            ->get(route('admin.index'));

        $response->assertOk();
    }
}
