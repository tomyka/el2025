<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    private function fakeSocialiteUser(string $googleId, string $email, string $name): void
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn($googleId);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getName')->andReturn($name);

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);
    }

    public function test_redirect_route_returns_redirect(): void
    {
        Socialite::shouldReceive('driver->redirect')->andReturn(
            redirect('https://accounts.google.com/o/oauth2/auth')
        );

        $response = $this->get('/auth/google');

        $response->assertRedirect();
    }

    public function test_existing_google_user_is_logged_in(): void
    {
        $user = User::factory()->create(['google_id' => 'gid-123', 'password' => null]);
        $this->fakeSocialiteUser('gid-123', $user->email, 'Test User');

        $response = $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('main'));
    }

    public function test_email_match_auto_links_google_id_and_logs_in(): void
    {
        $user = User::factory()->create(['google_id' => null]);
        $this->fakeSocialiteUser('gid-456', $user->email, 'Test User');

        $response = $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($user);
        $this->assertEquals('gid-456', $user->fresh()->google_id);
        $response->assertRedirect(route('main'));
    }

    public function test_new_google_user_is_created_and_logged_in(): void
    {
        $this->fakeSocialiteUser('gid-789', 'jonas@example.com', 'Jonas Petraitis');

        $response = $this->get('/auth/google/callback');

        $user = User::where('email', 'jonas@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('gid-789', $user->google_id);
        $this->assertEquals('jonas', $user->username);
        $this->assertEquals('Jonas', $user->name);
        $this->assertEquals('Petraitis', $user->surname);
        $this->assertNull($user->password);
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('main'));
    }
}
