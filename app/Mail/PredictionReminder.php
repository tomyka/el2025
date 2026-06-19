<?php

namespace App\Mail;

use App\Models\Game;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class PredictionReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Game $game,
        public readonly User $recipient,
    ) {}

    public function envelope(): Envelope
    {
        $home = $this->game->home_team->team;
        $away = $this->game->away_team->team;
        $time = Carbon::parse($this->game->game_date, 'UTC')
            ->setTimezone('Europe/Vilnius')
            ->format('H:i');

        return new Envelope(
            to: $this->recipient->email,
            subject: "Prognozė: {$home} vs {$away} – {$time} LT",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.prediction-reminder',
            with: [
                'unsubscribeUrl' => URL::signedRoute(
                    'profile.notifications.unsubscribe',
                    ['user' => $this->recipient->id]
                ),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
