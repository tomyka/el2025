# Design: Registration Deadline from First Game

**Date:** 2026-06-04
**Scope:** Replace hardcoded registration deadline with dynamic check against the first game in the database.

---

## Problem

`resources/views/modals/main.blade.php` gates the registration tab with a hardcoded date:
```php
@if (@now()<'2025-09-01')
```
This date has already passed, so registration is permanently hidden. The deadline should instead reflect the actual first match of the tournament (World Football Cup 2026).

## Solution

Derive the deadline from `games.game_date` — specifically the earliest game in the database. Registration is open while no first game has started yet.

## Changes

**One file modified:** `resources/views/modals/main.blade.php`

Add at the top:
```php
@php
    $registrationOpen = !\App\Models\Game::exists()
        || now()->lt(\App\Models\Game::min('game_date'));
@endphp
```

Replace both `@if (@now()<'2025-09-01')` occurrences with `@if($registrationOpen)`.

## Behaviour

| DB state | `$registrationOpen` | Registration tab |
|---|---|---|
| No games in DB | `true` | Visible |
| Earliest game is in the future | `true` | Visible |
| Earliest game has started or passed | `false` | Hidden |

## Out of Scope

- Admin UI to manually override the deadline
- Any change to the `settings` table
- Any controller changes

## Tests

One feature test class `RegistrationDeadlineTest` with three cases:

1. **No games** — modal HTML contains the registration tab button
2. **Future first game** — modal HTML contains the registration tab button
3. **Past first game** — modal HTML does NOT contain the registration tab button

Tests render the guest `main` view (or directly the modal partial) and assert on the output.
