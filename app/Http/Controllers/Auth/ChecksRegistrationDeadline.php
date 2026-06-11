<?php

namespace App\Http\Controllers\Auth;

use App\Models\Game;

trait ChecksRegistrationDeadline
{
    protected function registrationIsOpen(): bool
    {
        $next = Game::whereNull('home_team_score')
            ->whereNull('away_team_score')
            ->min('game_date');

        return is_null($next) || now()->lt($next);
    }
}
