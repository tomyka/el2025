# Points Trend & Rank Tracker — Design Spec

**Date:** 2026-06-08  
**Status:** Approved

## Summary

Add per-round cumulative points trend and rank history to the leaderboard. Each leaderboard row gets a rank-change badge and an expandable panel showing an SVG chart plus a per-round table.

## Decisions

| Question | Decision |
|---|---|
| What to track | Both cumulative points trend and rank position per round |
| Display location | Expandable row in existing leaderboard |
| Data computation | On-the-fly from existing tables (no snapshot table) |
| Chart rendering | Inline SVG, server-side rendered in Blade |
| Panel layout | Chart (left) + per-round points & rank table (right) |

---

## Data Layer

### New method: `PointController::getAllUsersRoundHistory(int $groupID): array`

Returns round history for **all users in the group** at once (keyed by `userID`) to avoid N+1 queries and allow rank computation across users at each checkpoint.

```php
[
  $userID => [
    [
      'event_day'          => 1,       // round label (R1, R2, …)
      'round_points'       => 88.0,    // match results + survival for this event only
      'cumulative_points'  => 88.0,    // running total incl. current standing points
      'rank'               => 3,       // leaderboard position among group users at this round
    ],
    // …one entry per completed event
  ],
  // …one key per group user
]
```

**Query logic:**

1. Load all group users (same guest filter as `getAllUserPoints`).
2. Load all events ordered by `event_day`.
3. For each event N, compute each user's total:
   - `point_results.full_points` for all games in events with `event_day <= N`
   - `point_survivals.survival_points` for events with `event_day <= N`
   - current `point_standings` total (all phases, as-is — not event-tied)
4. Sort all users by total at event N → derive each user's rank at that round.
5. `round_points` = total at event N minus total at event N-1.

**Constraint:** Standing points are not tied to a specific event in the schema — they represent tournament-phase awards (group position, quarterfinal, etc.) scored whenever admin runs `/admin/updateStandingPoints`. Including them as a fixed addend in every round's cumulative is acceptable for now. If standings change mid-tournament the trend updates on next page load.

### `MainController::loadApp()` change

After building `$points`, call `getAllUsersRoundHistory($groupID)` once. Merge each user's history array into the corresponding entry in `$points` as `roundHistory` key. Pass merged array to view unchanged (same `$points` variable, extended).

Also compute `rankChange` per user: current rank (loop position in sorted `$points`) minus rank at the most recent completed round in their history. Attach as `rankChange` key.

---

## Leaderboard Row (`points.blade.php`)

### Rank-change badge

Added between name and total points:

- `▲ N` in green (`text-success`) — moved up N places since last round
- `▼ N` in red (`text-danger`) — moved down N places
- `—` in muted grey — no change, or no previous round data yet

### Expand toggle

Each row wrapped in Alpine.js `x-data="{ open: false }"`. Clicking the row toggles `open`. Chevron icon (`▸` / `▾`) at row end reflects state. The expanded panel uses `x-show="open"` with `x-transition` for a smooth slide.

---

## Expanded Panel

Two-column layout inside the expanded section, separated by a subtle divider.

### Left — SVG chart

Server-side Blade generates an `<svg viewBox="0 0 {width} 80">` where `width = max(120, (count($rounds) - 1) * 60)`.

**Two lines:**
- **Blue solid** (`#3b82f6`, `stroke-width="2"`) — cumulative points. Y-axis: max cumulative = top of chart area, 0 = bottom.
- **Gold dashed** (`#f6c90e`, `stroke-width="1.5"`, `stroke-dasharray="4,2"`) — rank position. Y-axis inverted: rank 1 = top, max rank = bottom.

Dots at each round on the cumulative line. Round labels (`R1`, `R2`, …) below X-axis. Light horizontal grid lines at 25%, 50%, 75%.

Legend row below chart: `— cumulative pts` / `-- rank`.

### Right — Per-round table

Simple column layout (no `<table>` tag, flex rows for compactness):

```
R1   +88.0   #3
R2   +105.5  #2
R3   +92.0   #2 ▲  ← gold + arrow on rank improvement
R4   +57.0   #1 ▲
```

Rank cell highlighted gold + `▲` when rank improved vs. previous round; red + `▼` when worsened.

---

## Files Changed

| File | Change |
|---|---|
| `app/Http/Controllers/PointController.php` | Add `getUserRoundHistory()` |
| `app/Http/Controllers/MainController.php` | Call history method, attach to `$points` |
| `resources/views/partials/points.blade.php` | Rank badge, Alpine expand toggle, SVG panel |
| `public/css/custom.css` | Minor styles for panel layout if needed |

No new routes. No new DB tables. No new JS dependencies.

---

## Testing

- With no completed events: trend panel shows empty state ("No rounds completed yet").
- With one event: single dot on chart, no rank-change line (nothing to compare to), `—` badge.
- With multiple events: full chart renders, rank badge reflects last-round delta.
- Guest users follow `session('guest')` filter already applied in `getAllUserPoints()` — same filter used in `getUserRoundHistory()`.
- Survival game disabled (`session('survivalGame') != 1`): survival points are 0, still works correctly.
