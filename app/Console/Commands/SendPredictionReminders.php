<?php

namespace App\Console\Commands;

use App\Mail\PredictionReminder;
use App\Models\Game;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendPredictionReminders extends Command
{
    protected $signature   = 'reminders:send';
    protected $description = 'Send email reminders for upcoming game predictions';

    public function handle(): int
    {
        $now = Carbon::now('Europe/Vilnius');

        $games = Game::with('home_team', 'away_team')
            ->whereNull('home_team_score')
            ->where('reminder_sent', false)
            ->get();

        foreach ($games as $game) {
            $gameTime     = Carbon::parse($game->game_date, 'UTC')->setTimezone('Europe/Vilnius');
            $reminderTime = $this->computeReminderTime($gameTime);

            if ($now->lt($reminderTime)) {
                continue;
            }

            // Game already started — mark sent and skip emailing
            if ($now->gte($gameTime)) {
                $game->reminder_sent = true;
                $game->save();
                continue;
            }

            $users = User::whereHas('userSetting', fn($q) => $q->where('receive_reminders', true))
                ->whereNotNull('email')
                ->get();

            foreach ($users as $user) {
                Mail::to($user->email)->queue(new PredictionReminder($game, $user));
            }

            $game->reminder_sent = true;
            $game->save();

            $this->info("Reminders queued for: {$game->home_team->team} vs {$game->away_team->team}");
        }

        return Command::SUCCESS;
    }

    public function computeReminderTime(Carbon $gameTime): Carbon
    {
        $hour = (int) $gameTime->format('H');

        if ($hour >= 22) {
            return $gameTime->copy()->setTime(21, 0, 0);
        }

        if ($hour < 8) {
            return $gameTime->copy()->subDay()->setTime(21, 0, 0);
        }

        return $gameTime->copy()->subHour();
    }
}
