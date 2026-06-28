<?php

namespace App\Http\Controllers\Auth;

use App\Models\Game;

trait ChecksRegistrationDeadline
{
    protected function registrationIsOpen(): bool
    {
        // Use the first game date ever, not the first unscored game.
        // The unscored-game approach re-opens registration between rounds
        // when all current-stage games are scored but next-stage games
        // haven't been entered yet.
        $first = Game::min('game_date');

        return is_null($first) || now()->lt($first);
    }
}
