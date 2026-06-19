# Email Reminders + Single-Game Prediction Design

## Overview

Two coupled features:
1. **Single-game prediction** — modal on main page (opened by clicking a game in the upcoming widget) + standalone page at `/prediction/game/{id}` used as email deep link.
2. **Email reminders** — opt-in scheduled emails sent to users before each upcoming game. Links directly to the single-game prediction for that game.

---

## Data Model

Single migration adds two columns:

```php
// 2026_06_19_000000_add_reminder_fields.php
Schema::table('games', function (Blueprint $table) {
    $table->boolean('reminder_sent')->default(false);
});

Schema::table('user_settings', function (Blueprint $table) {
    $table->boolean('receive_reminders')->default(false);
});
```

**`games.reminder_sent`** — flipped to `true` after the command sends reminders for that game. Prevents double-sends across scheduler runs. Must be reset to `false` if admin reschedules the game (handled in wherever game `game_date` is updated — `ResultController` or a future game admin controller).

**`user_settings.receive_reminders`** — opt-in flag, `false` by default. User controls it from profile settings.

---

## Single-Game Prediction

### Modal on main page

Each game card in the upcoming widget (`resources/views/main.blade.php`) gets a "Prognozuoti" button. Clicking it opens a Bootstrap modal.

The modal is shared (one in the DOM). Alpine.js manages `selectedGameId` state and renders the correct game inputs. All data comes from `$predictionGames` already passed to the view — no AJAX load required.

On save: AJAX POST to `POST /prediction/results` (`PredictionResultController::updatePredictionResultUser`) — same endpoint used by the full prediction form. On success: update the displayed prediction score inputs in the widget to reflect the saved values, then close the modal.

If the game is locked (already started), the button is disabled and shows "Užrakinta".

### Standalone page for email deep links

**Route:** `GET /prediction/game/{gameID}` → `PredictionResultController::showSingleGame($gameID)`, named `prediction.game.single`

Loads that game's data (teams, current user's prediction if any), renders `resources/views/prediction/game-single.blade.php` — a simple full-page form with home/away score inputs and a save button.

After save: redirect to main page with a success flash message.

If game is locked: show read-only view of current prediction (or "Žaidimas prasidėjo" message if no prediction exists).

Requires authentication — redirect to login if unauthenticated (standard Laravel middleware).

---

## Email Reminders

### Timing logic

All times in **Europe/Vilnius** timezone.

| Game kickoff (LT) | Reminder sent at |
|---|---|
| 08:00 – 21:59 | 1 hour before kickoff |
| 22:00 – 23:59 | 21:00 same day |
| 00:00 – 07:59 | 21:00 previous evening |

```php
$gameTime = Carbon::parse($game->game_date)->setTimezone('Europe/Vilnius');
$hour = (int) $gameTime->format('H');

if ($hour >= 22) {
    $reminderTime = $gameTime->copy()->setTime(21, 0);
} elseif ($hour < 8) {
    $reminderTime = $gameTime->copy()->subDay()->setTime(21, 0);
} else {
    $reminderTime = $gameTime->copy()->subHour();
}
```

### Components

**`app/Console/Commands/SendPredictionReminders.php`**

Command name: `reminders:send`. Scheduled every 15 minutes.

Logic:
1. Find all games where `reminder_sent = false` AND `home_team_score IS NULL` (not yet played).
2. For each game, compute `$reminderTime` using the logic above.
3. Skip if `now('Europe/Vilnius') < $reminderTime`.
4. Find all users where `user_settings.receive_reminders = true` AND `users.email IS NOT NULL`.
5. Dispatch queued `PredictionReminder` mailable for each user.
6. Set `$game->reminder_sent = true` and save.

**`app/Mail/PredictionReminder.php`**

Queued mailable. Constructor receives `Game $game` and `User $user`.

**`resources/views/emails/prediction-reminder.blade.php`**

- Subject: `Prognozė: {HomeTeam} vs {AwayTeam} – {time} LT`
- Body: teams, kickoff time (formatted in LT timezone), "Prognozuoti" CTA button → `route('prediction.game.single', $game->id)`
- Footer: "Atsisakyti priminimų" unsubscribe link → `route('profile.notifications.unsubscribe', $token)` (signed URL, no login required)

**Scheduler registration** in `routes/console.php`:
```php
Schedule::command('reminders:send')->everyFifteenMinutes();
```

### Unsubscribe

Reminder emails include a one-click unsubscribe link using a Laravel signed URL pointing to a `GET` route. This sets `receive_reminders = false` on the user's settings without requiring login. Uses `URL::signedRoute()` with the user's ID as parameter — no separate token table needed.

---

## Opt-in UI

New card in `resources/views/userProfile.blade.php` (below the two-column profile/password row, above Danger Zone):

```
┌─────────────────────────────────────────────┐
│ 🔔 Pranešimai                               │
│                                             │
│ Gauti priminimu el. paštu apie artėjančias  │
│ rungtynes prieš pat žaidimo pradžią         │
│                                             │
│ [✓] Įjungti priminimai                      │
│                          [Išsaugoti]        │
└─────────────────────────────────────────────┘
```

**New route:**
```php
Route::post('profile/notifications', [UserProfileController::class, 'updateNotifications'])
    ->name('profile.notifications');
Route::get('profile/notifications/unsubscribe/{user}', [UserProfileController::class, 'unsubscribe'])
    ->name('profile.notifications.unsubscribe')
    ->middleware('signed');
Route::get('prediction/game/{gameID}', [PredictionResultController::class, 'showSingleGame'])
    ->name('prediction.game.single');
```

**`UserProfileController::updateNotifications()`**: validates `receive_reminders` boolean, updates `UserSetting`, redirects back with `info` flash.

**`UserProfileController::unsubscribe()`**: sets `receive_reminders = false` for the given user ID (validated by signed URL), redirects to login page with a flash message.

---

## Files Created / Modified

| Action | File |
|---|---|
| Create | `database/migrations/2026_06_19_000000_add_reminder_fields.php` |
| Create | `app/Console/Commands/SendPredictionReminders.php` |
| Create | `app/Mail/PredictionReminder.php` |
| Create | `resources/views/emails/prediction-reminder.blade.php` |
| Create | `resources/views/prediction/game-single.blade.php` |
| Modify | `app/Http/Controllers/PredictionResultController.php` — add `showSingleGame()` |
| Modify | `app/Http/Controllers/UserProfileController.php` — add `updateNotifications()`, `unsubscribe()` |
| Modify | `resources/views/userProfile.blade.php` — add notifications card |
| Modify | `resources/views/main.blade.php` — add game click triggers + shared modal |
| Modify | `routes/web.php` — new routes |
| Modify | `routes/console.php` — scheduler registration |

---

## Production Notes

- `QUEUE_CONNECTION=database` already configured — queued mailables work out of the box.
- `MAIL_MAILER=log` locally. Production needs real SMTP (Mailgun / SES / Postmark) configured in `.env`.
- Run `php artisan migrate` on production after deploying.
- Ensure `php artisan queue:work` (or Supervisor) is running in production for queued emails.
- Ensure the Laravel scheduler cron is active: `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`.
