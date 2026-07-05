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

Scoring runs in `PointResultController::doUpdateGamePoints()` via `ScoringService`. All components are summed and multiplied by the event `rate`.

#### Group stage games (`is_knockout = 0`)

| Component | Formula |
|---|---|
| **Table points** | Lookup from `points_calculations` table keyed by `{actual_home_diff}_{actual_away_diff}` — covers winner + goal difference together |
| **Winner bonus** | `(1 + odds) × 5.0` if predicted winner/draw direction matches; else `0` |
| **Bingo** | `2.5` pts for exact score; else `0` |
| **Odds** | Baked into winner bonus — `odds` comes from `game_odds` table, scaled by crowd vote |

#### Knockout games (`is_knockout = 1`) — winner bonus (CONFIRMED RULE — do not change without explicit user instruction)

| Scenario | Winner bonus |
|---|---|
| ✅ Correct advancing team **and** correct ending (90-min win or draw→pens) | `(1 + predictedOdds) × 5.0` — full |
| 🟡 Correct advancing team **but** wrong ending (predicted pens, won in 90min, or vice-versa) | `(1 + actualOdds) × 2.5` — half |
| 🟡 Correct draw→pens path **but** wrong penalty winner | `(1 + actualOdds) × 2.5` — half |
| ❌ Wrong advancing team | `0` |

`actualOdds` is used for partial cases so half credit can never exceed full credit.

Bingo for knockout: exact score **and** correct penalty winner (if applicable) → `2.5` pts.

#### Prediction summary label colours (`resources/views/summary/results.blade.php`)

| Label colour | Condition |
|---|---|
| 🟢 Green (`sr-pred-ok`) | Fully correct: right team + right ending (or group game with correct winner) |
| 🟡 Amber (`sr-pred-partial`) | Knockout only: correct advancing team but wrong ending — gets **half** series points |
| 🔴 Red (`sr-pred-fail`) | Wrong advancing team / wrong direction |
| ⚪ Grey (`sr-pred-pending`) | Game not yet scored |

> **Warning:** The partial label detection reads team IDs from scores directly (same logic as `PointResultController`), not from `winner_points` — because `winner_points` is 0 for these cases. Do not rewrite the partial detection to use `winner_points`.

#### Recalculating scores

After any scoring change, visit `/admin/recalculateAllGamePoints` (superadmin only) to reprocess all scored games. There is also a tile on the admin dashboard.

When a single result is saved by an admin, `ResultController::updateResult()` chains: generate missing predictions → update survival → update game odds → recalculate all `point_results` for that game.

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
