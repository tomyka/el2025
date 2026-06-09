# Position Trend Card — Design Spec

## Goal

Add a full-width card to the main screen showing the logged-in player's rank position after every scored game, giving a visual arc of their whole tournament journey.

---

## What the user sees

A card below the existing leaderboard/upcoming-games grid containing:

**Stats strip (4 tiles)**
| Tile | Value | Colour |
|---|---|---|
| Dabar (current) | #N | Blue |
| Geriausia (best ever) | #N | Green |
| Blogiausia (worst ever) | #N | Amber |
| Pokytis (last-game change) | ▲N / ▼N / — | Green / Red / Grey |

**SVG rank chart**
- X axis: scored games in chronological order (1 … total)
- Y axis: rank, **inverted** — #1 at top, higher numbers lower
- Rendered as a single polyline with a gradient fill below it
- No individual data-point dots — line only (too many games to show dots)
- One highlighted endpoint circle at the current/final position, labelled with the current rank
- Three faint horizontal grid lines at #1, midpoint rank, and max rank
- Game number labels at start, midpoint, and end of X axis only

**Hidden when** `$rankHistory` has fewer than 2 entries (tournament not yet started or only 1 game played).

---

## Data flow

### Query: scored games (ordered)

```sql
SELECT g.id
FROM games g
WHERE g.home_team_score IS NOT NULL
ORDER BY g.game_date ASC, g.id ASC
```

### Query: all group users' match points per game

```sql
SELECT pr.game_id, pr.user_id,
       ROUND(IFNULL(por.full_points, 0), 2) AS game_points
FROM prediction_results pr
    JOIN games g ON pr.game_id = g.id
    JOIN user_groups ug ON pr.user_id = ug.user_id
        AND ug.group_id = :groupID
        AND ug.guest <= :guest
    LEFT JOIN point_results por
        ON por.user_id = pr.user_id AND por.game_id = pr.game_id
WHERE g.home_team_score IS NOT NULL
```

### PHP computation

For each scored game in order:
1. Add each user's `game_points` to their running cumulative total.
2. Sort users by cumulative total descending.
3. Record the logged-in user's 1-based rank in the sorted list.

Result: `$rankHistory = [3, 5, 4, 2, 1, 2, ...]` — one integer per scored game.

Complexity: O(games × users). For 120 games × 20 users = 2 400 iterations — negligible.

---

## Files changed

| File | Change |
|---|---|
| `app/Http/Controllers/PointController.php` | Add `getRankHistory(int $groupID, int $userID): array` |
| `app/Http/Controllers/MainController.php` | Call `getRankHistory()` inside the authenticated branch; pass `$rankHistory` to view |
| `resources/views/main.blade.php` | Add full-width row below existing grid: `@include('partials.positionTrend')` |
| `resources/views/partials/positionTrend.blade.php` | New partial — `@php` block for SVG geometry, stats strip, polyline chart |
| `public/css/custom.css` | New `.pt-*` classes |

---

## SVG geometry (computed in `@php`)

```
$n      = count($rankHistory)          // total data points
$maxRank = max($rankHistory)           // worst rank (bottom of Y)

viewBox width  = max(200, ($n - 1) * 4)   // 4px per game, min 200
viewBox height = 90

// Map game index i → X
$x = $n > 1 ? ($i / ($n - 1)) * $svgW : $svgW / 2

// Map rank r → Y  (inverted: rank 1 = Y 8, rank maxRank = Y 72)
$y = 8 + (($r - 1) / max($maxRank - 1, 1)) * 64
```

Grid lines are drawn at Y values for ranks 1, ceil($maxRank/2), and $maxRank.

---

## CSS classes (`.pt-*`)

```
.pt-card          — wrapper (uses existing .sb-card)
.pt-stats         — 4-tile flex strip
.pt-stat          — individual stat tile
.pt-stat-val      — large number
.pt-stat-lbl      — small uppercase label below
.pt-chart-wrap    — SVG container, padding
.pt-up            — green colour for positive movement
.pt-down          — red colour for negative movement
.pt-neutral       — grey for no change
```

---

## Edge cases

- **0 or 1 scored games**: card not rendered (not enough history for a meaningful trend).
- **Tied ranks**: users with equal cumulative points get the same rank number. Standard dense ranking (`1, 2, 2, 3`).
- **User not in group**: `getRankHistory()` returns `[]`; card not rendered.
- **Single player in group**: rank is always #1; card renders but is trivially flat — acceptable.
