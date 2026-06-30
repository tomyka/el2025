# Multi-Tournament Architecture Design

**Date:** 2026-06-30  
**Status:** Approved

---

## Goal

Transform SportBet from a single-tournament application into a multi-tournament platform where users can participate in multiple simultaneous or consecutive tournaments (World Cup, Champions League, domestic leagues, basketball, etc.) under one account, without recreating accounts per tournament.

---

## Approach: Chain Scoping (Option A)

Add a `tournaments` table and attach `tournament_id` to only three existing tables: `events`, `teams`, and `leagues`. All other data — predictions, points, odds, survival — scopes to a tournament automatically through existing foreign key chains. This minimises schema changes and migration risk.

---

## 1. Data Model

### New table: `tournaments`

```sql
id           — primary key
name         — "World Football Cup 2026", "LKL 2025/26"
slug         — "world-cup-2026" (URL identifier, unique)
sport        — "football", "basketball", …
status       — upcoming | active | finished
start_date
end_date
description  — shown on welcome page card
cover_image  — banner image for the tournament card
is_public    — whether anonymous users can browse
survival_game — boolean (moves from global settings table)
timestamps
```

### Schema changes — 3 columns added

| Table    | Column added      | Why |
|----------|-------------------|-----|
| `events` | `tournament_id`   | Scopes games, prediction_results, point_results, game_odds, prediction_survivals, point_survivals through game→event chain |
| `teams`  | `tournament_id`   | Scopes prediction_standings, point_standings through team FK |
| `leagues`| `tournament_id`   | Scopes league_members, league_game_odds, messages; enables per-tournament league listing on welcome page |

### How scope propagates (no extra columns needed)

```
tournaments
 └─ events (tournament_id) → games → prediction_results, point_results, game_odds
                           → prediction_survivals, point_survivals
 └─ teams  (tournament_id) → prediction_standings, point_standings
 └─ leagues(tournament_id) → league_members, league_game_odds, messages
```

### Settings split

| Setting           | Old location           | New location                   |
|-------------------|------------------------|--------------------------------|
| `survivalGame`    | `settings` table       | `tournaments.survival_game`    |
| `timeDifference`  | `settings` table       | Stays in `settings` (global)   |
| `rate`            | `events.rate`          | Stays per-event (unchanged)    |

---

## 2. Welcome Page & Navigation

### Welcome page (homepage becomes tournament hub)

The welcome/home page lists all tournaments in three sections: **Active**, **Upcoming**, and **Past**. This replaces the current single-tournament landing page.

Each tournament card shows:
- Name, sport, participant count
- For logged-in users: their league badges if already joined
- **"Enter →"** if already in a league, **"View / Join →"** if not, **"Join early →"** for upcoming, **"View results →"** for finished

Anonymous users see all cards but "Enter →" redirects to login.

### Entering a tournament

- **Already in a league:** "Enter →" sets `session('tournamentID')` + `session('leagueID')` (first active league) and lands on the existing dashboard.
- **Not yet in a league:** "View / Join →" lands on a tournament intro page with public leaderboard and "Create league" / "Join via invite" options.

### In-tournament navbar (after entering)

```
[ SportBet ]  Pradžia  Spėjimai  Eiga  …    [ ← Turnyrai ]  League▾  [ avatar ]
```

- **"← Turnyrai"** replaces any tournament/league switcher concept — clicking it clears `tournamentID` and `leagueID` from session and returns to the hub.
- **League▾** switcher remains for switching between leagues within the same tournament.

### Session keys (updated)

| Key                  | Change                                              |
|----------------------|-----------------------------------------------------|
| `session('tournamentID')` | **New** — set when user enters a tournament     |
| `session('leagueID')`     | Unchanged                                       |
| `session('eventID')`      | Now filtered by `tournamentID`                  |
| `session('survivalGame')` | Now read from `tournaments.survival_game`       |
| `session('userID')`, `session('admin')`, … | Unchanged                  |

---

## 3. Admin Panel Changes

### New tile: Turnyrai

A new "Turnyrai" tile in the admin panel lists all tournaments. From there, admin can:

1. **Create tournament** — fills: name, sport, slug, dates, description, cover image, survival on/off → status starts as `upcoming`
2. **Edit / enter tournament context** — switches the admin's session to that tournament, so all existing tools (Rungtynės, Komandos, Turai, Rezultatai, Lygos) operate on it
3. **Set status** → active (when predictions open), finished (after last result)

### Existing tiles — unchanged

Rezultatai, Rungtynės, Komandos, Turai, Vartotojai, Lygos all work exactly as today. The only difference: they operate on whichever tournament is currently active in the admin's session. No functional changes required.

---

## 4. Migration Plan

Zero prediction or points data is touched. The migration is purely additive.

**Steps (single migration file):**

1. Create `tournaments` table with all columns listed above
2. Insert **World Football Cup 2026** row (`status = active`, `slug = world-cup-2026`)
3. Add nullable `tournament_id` to `events`, `teams`, `leagues`
4. Set all existing rows → `tournament_id = 1`
5. Make `tournament_id` NOT NULL with FK to `tournaments`
6. Copy `settings.survivalGame` value into `tournaments.survival_game` for row 1
7. Update `SessionController::setSession()` to populate `session('tournamentID')` from the active league's tournament
8. Update `SessionController`'s event lookup to filter by `tournament_id`
9. Update welcome page route to query `tournaments` table for the hub listing

---

## 5. Scope of Work

### Database
- 1 new table (`tournaments`)
- 3 new FK columns (`events.tournament_id`, `teams.tournament_id`, `leagues.tournament_id`)
- 1 new column on `tournaments` (`survival_game`)
- 1 migration file (schema + backfill)

### Backend
- `SessionController` — populate `tournamentID`, filter events by it, read `survival_game` from tournament
- New `TournamentController` — index (hub list), show (intro/join page), admin CRUD
- Queries in `EventController`, `GameController`, `TeamController` — add tournament filter
- New routes for tournament hub and admin tournament management

### Frontend
- New welcome page — tournament hub with Active/Upcoming/Past sections
- "← Turnyrai" link in `partials/header.blade.php`
- New tournament intro/join page
- New admin tournament tile + create/edit form in `admin/index.blade.php`

---

## Key Constraints

- Tournaments are fully independent silos — no shared leagues, standings, or predictions across tournaments
- All existing prediction mechanics (match results, standings, survival) apply to every tournament unchanged
- The `settings` table global `timeDifference` key stays global — not per-tournament
- All existing data belongs to tournament 1 (World Football Cup 2026); the migration assigns it there
- `session('tournamentID')` persists across page loads within a session; `SessionController::setSession()` reads it and populates dependent keys (`eventID`, `survivalGame`). Clicking "← Turnyrai" clears it explicitly — subsequent page loads with no `tournamentID` in session redirect to the tournament hub
