# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Install PHP dependencies
composer install

# Install JS dependencies / build assets
npm install
npm run dev      # Vite dev server (HMR)
npm run build    # Production build

# Run all tests
php artisan test

# Run a single test file
php artisan test tests/Feature/PredictionResultTest.php

# Run a single test method
php artisan test --filter test_user_can_make_prediction_on_game

# Database
php artisan migrate
php artisan migrate:fresh --seed

# Docker (full stack)
docker compose up -d
docker compose run --rm artisan migrate
docker compose run --rm npm run build
```

## Architecture

**Laravel 11** sports prediction game for football tournaments (e.g. Euro 2024). PHP 8.2+, MySQL in production (Docker), SQLite for tests, Blade/Alpine.js/Tailwind frontend.

### Session-driven state

`SessionController::setSession()` runs on every authenticated page load and populates all critical session keys:

| Session key | Source |
|---|---|
| `userID`, `groupID` | `users`, `user_groups` |
| `eventID` | First game with no score yet |
| `admin`, `guest`, `fee` | `user_settings`, `user_groups` |
| `survivalGame`, `timeDifference` | `settings` table |

Most controllers read directly from `session()` instead of receiving parameters. Raw SQL queries embed session values inline (no binding), which is an existing pattern in this codebase.

### Three prediction types

1. **Match results** (`prediction_results`) — predict score of each game. Scored in `PointResultController`.
2. **Team standings** (`prediction_standings`) — predict group positions and knockout advancement per team. Scored in `PointStandingController::updateStandingPoints()` (triggered via `GET /admin/updateStandingPoints`).
3. **Survival** (`prediction_survivals`) — pick a team to survive each round. Scored in `PointSurvivalController`.

### Scoring logic (PointResultController)

Points per game = all of the below × event `rate`:
- **Winner**: 50 pts if predicted correct winner
- **Difference**: `50 - |actual_diff - predicted_diff|`
- **Bingo**: 50 pts for exact score, 20 pts if goal difference delta is zero
- **Odds**: `winner_points × (odds - 1)` — only if winner was correct; odds come from `game_odds` table

When a result is saved by an admin, `ResultController::updateResult()` chains: generate missing predictions → update survival → update game odds → recalculate all `point_results` for that game.

### Controllers that are called as classes (not via routing)

Many controllers instantiate other controllers directly rather than using dependency injection. For example, `MainController::loadApp()` creates instances of `PointController`, `PredictionResultController`, etc. inline. This is the established pattern.

### Groups

Users belong to one active group at a time (`user_groups.active = true`). Points, leaderboards, and messages are all scoped to a group. `UserGroup.guest` controls whether guest users are included in leaderboards.

### Admin panel

All admin routes are under `/admin/*`. There is no middleware protecting admin routes — admin access is gated by `session('admin')` checks in views/controllers. `user_settings.admin` is the source of truth.

### Key settings table values

- `survivalGame`: 0 = disabled, non-zero = enabled
- `timeDifference`: hour offset added to `NOW()` for game lock time comparisons (handles timezone drift)

### Frontend

Blade templates with partials in `resources/views/partials/`. Two layout masters: `layouts/master.blade.php` (main app) and `admin/layouts/master.blade.php` (admin). Alpine.js used for interactivity; no separate JS build other than Vite bundling.
