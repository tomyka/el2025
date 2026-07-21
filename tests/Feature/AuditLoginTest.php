<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_login_creates_audit_record(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $this->assertDatabaseHas('audit_logins', [
            'user_id' => (string) $user->id,
            'login_method' => 'email',
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_failed_login_does_not_create_audit_record(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('correct'),
        ]);

        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong',
        ]);

        $this->assertDatabaseCount('audit_logins', 0);
    }
}
