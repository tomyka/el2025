<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(bool $reminders = false): User
    {
        $user = User::factory()->create();
        UserSetting::create([
            'user_id'           => $user->id,
            'admin'             => 0,
            'receive_reminders' => $reminders,
        ]);
        return $user;
    }

    public function test_user_can_enable_reminders(): void
    {
        $user = $this->makeUser(false);

        $this->actingAs($user)
            ->post(route('profile.notifications'), ['receive_reminders' => '1'])
            ->assertRedirect(route('userProfile'));

        $this->assertTrue(
            (bool) UserSetting::where('user_id', $user->id)->value('receive_reminders')
        );
    }

    public function test_user_can_disable_reminders(): void
    {
        $user = $this->makeUser(true);

        $this->actingAs($user)
            ->post(route('profile.notifications'), []);

        $this->assertFalse(
            (bool) UserSetting::where('user_id', $user->id)->value('receive_reminders')
        );
    }

    public function test_unsubscribe_signed_url_disables_reminders(): void
    {
        $user = $this->makeUser(true);

        $url = URL::signedRoute('profile.notifications.unsubscribe', ['user' => $user->id]);

        $this->get($url)->assertRedirect();

        $this->assertFalse(
            (bool) UserSetting::where('user_id', $user->id)->value('receive_reminders')
        );
    }

    public function test_guest_cannot_post_notification_preferences(): void
    {
        $this->post(route('profile.notifications'), ['receive_reminders' => '1'])
            ->assertRedirect('/login');
    }
}
