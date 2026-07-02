<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_switch_to_english(): void
    {
        $user = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $user->id, 'locale' => 'lt']);

        $this->actingAs($user)
            ->post('/locale', ['locale' => 'en'])
            ->assertRedirect();

        $this->assertDatabaseHas('user_settings', ['user_id' => $user->id, 'locale' => 'en']);
        $this->assertEquals('en', session('locale'));
    }

    public function test_authenticated_user_can_switch_to_lithuanian(): void
    {
        $user = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $user->id, 'locale' => 'en']);

        $this->actingAs($user)
            ->post('/locale', ['locale' => 'lt'])
            ->assertRedirect();

        $this->assertDatabaseHas('user_settings', ['user_id' => $user->id, 'locale' => 'lt']);
        $this->assertEquals('lt', session('locale'));
    }

    public function test_guest_can_switch_locale_via_session_only(): void
    {
        $this->post('/locale', ['locale' => 'en'])
            ->assertRedirect();

        $this->assertEquals('en', session('locale'));
        $this->assertDatabaseCount('user_settings', 0);
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $user = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post('/locale', ['locale' => 'fr'])
            ->assertSessionHasErrors('locale');
    }
}
