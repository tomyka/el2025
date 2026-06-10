# Leagues Feature Design

## Goal

Replace the existing `groups`/`user_groups` system with a first-class **Leagues** concept. Leagues are user-created competitive pools with invite-based membership, configurable fees, and optional per-league odds isolation. A public league auto-exists and is joined by every new registrant.

## Architecture

New tables (`leagues`, `league_members`, `league_invites`, `league_game_odds`) replace `groups` and `user_groups`. All existing data is migrated. Controllers, session keys, and views are updated throughout. The scoring engine gains optional per-league odds recalculation at leaderboard query time — no extra `point_results` rows needed.

**Tech stack:** Laravel 11 · Blade · Bootstrap 5 · Alpine.js · MySQL (prod) · SQLite (tests)

---

## Data Model

### `leagues`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | varchar | display name |
| `description` | text null | |
| `is_public` | bool | `true` for the one public league; auto-joins all new registrants |
| `owner_id` | FK users null | null for the public league |
| `base_fee` | int null | fixed entry fee (informational) |
| `penalty_step` | int null | extra owed per finishing place beyond 1st |
| `use_league_odds` | bool default false | opt-in per-league odds; only active when member count ≥ 20 |
| `reward_description` | text null | free-text prize info |
| `created_at` / `updated_at` | timestamps | |

**Fee formula:** `total_owed = base_fee + (position − 1) × penalty_step`
Example: base €10, step €5 → 1st owes €10, 2nd €15, 3rd €20.
Amounts are displayed only — no payment processing.

### `league_members`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `league_id` | FK leagues | |
| `user_id` | FK users | |
| `is_admin` | bool default false | league admin flag; owner is auto-admin on creation |
| `is_guest` | bool default false | hidden from leaderboard |
| `active` | bool default false | which league this user is currently viewing; exactly one row per user should be `true` |
| `created_at` / `updated_at` | timestamps | |

Unique constraint: `(league_id, user_id)`.

### `league_invites`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `league_id` | FK leagues | |
| `invited_user_id` | FK users | |
| `invited_by_id` | FK users | |
| `status` | enum: pending, accepted, declined | |
| `created_at` / `updated_at` | timestamps | |

Unique constraint: `(league_id, invited_user_id)` — prevents duplicate pending invites.
On accept: create `league_members` row, delete invite.
On decline: delete invite.

### `league_game_odds`

| Column | Type | Notes |
|---|---|---|
| `league_id` | FK leagues | composite PK |
| `game_id` | FK games | composite PK |
| `home_odds` | decimal(5,2) | |
| `draw_odds` | decimal(5,2) | |
| `away_odds` | decimal(5,2) | |
| `updated_at` | timestamp | |

Only populated for leagues where `use_league_odds = true` AND member count ≥ 20. Recalculated by `GameOddsController` alongside the global `game_odds` update whenever a result is saved.

---

## Membership Rules

- **Public league:** one row in `leagues` with `is_public = true`. Every new user registration auto-creates a `league_members` row with `active = true`. Users cannot leave the public league.
- **Private leagues:** any authenticated user can create one, becoming its admin (`is_admin = true`). No limit on number of leagues a user can create or join.
- **Invites:** league admins search registered users by name/username within the platform and send invites. A user already in the league or with a pending invite cannot be invited again.
- **Multi-league:** users can be active members of multiple leagues simultaneously. The `active` flag on `league_members` controls which league's leaderboard is displayed by default.
- **Dropping:** users may drop from any private league. Dropping removes the `league_members` row. If the dropped league was `active`, the system sets the public league as `active` instead.
- **Admin leaving:** a league owner must transfer ownership to another member before dropping. If no other member exists, the league is deleted on drop.
- **Co-admins:** league owners can promote any member to `is_admin = true`. Co-admins can invite and remove members but cannot delete the league or transfer ownership.

---

## League Switcher UI

### Navbar dropdown (quick switch)

- Current active league name shown as a pill in the top navbar (between main nav links and user menu).
- Clicking the pill opens a dropdown listing all leagues the user belongs to.
- Active league is highlighted. Selecting another league POSTs to `POST /switchLeague` with `leagueID`, updates `league_members.active`, and redirects back to the current page.
- Pending invite count shown as a badge on the pill (e.g. `🏆 Public League (2)`).

### Leagues page (`/leagues`)

A dedicated hub page accessible from the navbar. Sections:

1. **My Leagues** — card per league showing: name, member count, user's current rank, `active` indicator. Each card has: Switch button (sets active), Manage button (admins only), Leave button (private leagues only).
2. **Invite Inbox** — list of pending invites with league name, invited-by username, Accept / Decline actions.
3. **Create League** — form: name, description, base_fee, penalty_step, use_league_odds toggle.

---

## Odds Isolation

**Default (all leagues):** global `game_odds` table used, calculated from all non-generated predictions across all users. Current behaviour preserved.

**Per-league opt-in:** league admin enables `use_league_odds` on their league. When this is true AND the league has ≥ 20 members, `GameOddsController::updateGameOdds()` also computes and upserts a `league_game_odds` row using only that league's members' predictions.

**Leaderboard calculation:** when rendering a leaderboard for a league with active per-league odds, `odds_points` is recalculated on the fly:

```
odds_points = winner_points × (league_odds − 1)   [if winner was correct]
full_points_in_league = winner_points + difference_points + bingo_points + odds_points_league
```

No new `point_results` rows. The base `point_results` table remains globally scoped. Per-league odds adjustment is a view-layer calculation only.

**Fallback:** if `use_league_odds = true` but member count < 20, silently falls back to global `game_odds`.

---

## Session & Controller Changes

- Session key `groupID` → `leagueID`
- `SessionController::setSession()` — load active league instead of active group
- All controllers reading `session('groupID')` updated to `session('leagueID')`
- `UserGroupController` → `LeagueController` (new controller, handles all user-facing league actions)
- `GroupController` → replaced by admin section of `LeagueController`; the old `GroupController` managed `groups` CRUD in the admin panel and is no longer needed once `groups` is dropped

---

## Migration

1. Create migrations for `leagues`, `league_members`, `league_invites`, `league_game_odds`
2. Seed: create the public league (`is_public = true`, `owner_id = null`)
3. Migrate `groups` rows → `leagues` rows (map `group` → `name`, `fee` → `base_fee`, `reward_description` → `reward_description`; drop `reward_ratio` — leagues use `penalty_step` for prize distribution instead)
4. Migrate `user_groups` rows → `league_members` rows (map `active` → `active`, `guest` → `is_guest`; set `is_admin = false` for all migrated rows)
5. Assign all existing users without a `league_members` row to the public league with `active = true`
6. Drop `groups` and `user_groups` tables
7. Update all controller/view references from `group_id`/`groupID` to `league_id`/`leagueID`

---

## Out of Scope

- Payment processing or automated prize transfers
- Email notifications for invites
- Mobile push notifications
- Public league discovery / browse page (private leagues are invite-only by design)
- Automated ownership transfer on account deletion (handle manually for now)
