<?php

namespace Tests\Feature;

use App\Console\Commands\SendPredictionReminders;
use App\Mail\PredictionReminder;
use App\Models\Event;
use App\Models\Game;
use App\Models\Team;
use App\Models\User;
use App\Models\UserSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PredictionReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    private function makeGame(string $gameDate): Game
    {
        $event = Event::create([
            'event' => 'Test', 'event_day' => 1,
            'event_survival' => 0, 'active' => 1, 'rate' => 1,
        ]);
        $home = Team::create(['team' => 'Home'.uniqid()]);
        $away = Team::create(['team' => 'Away'.uniqid()]);

        return Game::create([
            'event_id' => $event->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'game_date' => $gameDate,
        ]);
    }

    private function makeOptedInUser(): User
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        UserSetting::create([
            'user_id' => $user->id,
            'admin' => 0,
            'receive_reminders' => true,
        ]);

        return $user;
    }

    public function test_sends_reminder_to_opted_in_user_when_reminder_time_passed(): void
    {
        Mail::fake();
        // Now: 23:00 LT (20:00 UTC). Game at 23:30 LT (20:30 UTC).
        // Night game (hour=23 >= 22) → reminderTime = 21:00 LT (18:00 UTC).
        // 23:00 LT > 21:00 LT → send.
        Carbon::setTestNow('2026-06-19 20:00:00');
        $game = $this->makeGame('2026-06-19 20:30:00');
        $user = $this->makeOptedInUser();

        $this->artisan('reminders:send')->assertExitCode(0);

        Mail::assertQueued(PredictionReminder::class, fn ($m) => $m->hasTo($user->email));
        $this->assertTrue((bool) $game->fresh()->reminder_sent);
    }

    public function test_does_not_send_when_reminder_time_not_yet_reached(): void
    {
        Mail::fake();
        // Now: 20:00 LT (17:00 UTC). Game at 23:30 LT (20:30 UTC).
        // reminderTime = 21:00 LT (18:00 UTC). 20:00 LT < 21:00 LT → skip.
        Carbon::setTestNow('2026-06-19 17:00:00');
        $game = $this->makeGame('2026-06-19 20:30:00');
        $this->makeOptedInUser();

        $this->artisan('reminders:send')->assertExitCode(0);

        Mail::assertNothingQueued();
        $this->assertFalse((bool) $game->fresh()->reminder_sent);
    }

    public function test_does_not_send_to_opted_out_user(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-06-19 20:00:00');
        $game = $this->makeGame('2026-06-19 20:30:00');
        $user = User::factory()->create(['email' => 'out@example.com']);
        UserSetting::create(['user_id' => $user->id, 'admin' => 0, 'receive_reminders' => false]);

        $this->artisan('reminders:send')->assertExitCode(0);

        Mail::assertNothingQueued();
    }

    public function test_does_not_resend_if_reminder_already_sent(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-06-19 20:00:00');
        $game = $this->makeGame('2026-06-19 20:30:00');
        $game->update(['reminder_sent' => true]);
        $this->makeOptedInUser();

        $this->artisan('reminders:send')->assertExitCode(0);

        Mail::assertNothingQueued();
    }

    public function test_skips_games_with_scores_already_entered(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-06-19 20:00:00');
        $game = $this->makeGame('2026-06-19 20:30:00');
        $game->update(['home_team_score' => 1, 'away_team_score' => 0]);
        $this->makeOptedInUser();

        $this->artisan('reminders:send')->assertExitCode(0);

        Mail::assertNothingQueued();
    }

    public function test_reminder_time_for_22xx_game_is_2100_same_day(): void
    {
        $cmd = new SendPredictionReminders;
        $gameTime = Carbon::parse('2026-06-19 22:45:00', 'Europe/Vilnius');
        $expected = Carbon::parse('2026-06-19 21:00:00', 'Europe/Vilnius');

        $this->assertEquals($expected->timestamp, $cmd->computeReminderTime($gameTime)->timestamp);
    }

    public function test_reminder_time_for_0300_game_is_2100_previous_day(): void
    {
        $cmd = new SendPredictionReminders;
        $gameTime = Carbon::parse('2026-06-20 03:00:00', 'Europe/Vilnius');
        $expected = Carbon::parse('2026-06-19 21:00:00', 'Europe/Vilnius');

        $this->assertEquals($expected->timestamp, $cmd->computeReminderTime($gameTime)->timestamp);
    }

    public function test_reminder_time_for_1800_game_is_1700_same_day(): void
    {
        $cmd = new SendPredictionReminders;
        $gameTime = Carbon::parse('2026-06-19 18:00:00', 'Europe/Vilnius');
        $expected = Carbon::parse('2026-06-19 17:00:00', 'Europe/Vilnius');

        $this->assertEquals($expected->timestamp, $cmd->computeReminderTime($gameTime)->timestamp);
    }
}
