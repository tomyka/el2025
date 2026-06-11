# Code Quality Refactor — Design Spec

**Date:** 2026-06-11
**Scope:** Sub-project D of the broader security audit
**Status:** Approved

---

## Problem

Two quality issues identified during the security audit:

1. **Dead code** — Three deprecated methods in `PointController` (`getPointEventTotal`, `getPointPredictionUserEvent`, `getPointSurvivalUserEvent`) have no callers outside each other. A dead variable assignment in `MainController` and an orphaned Blade partial (`previous.blade.php`) accompany them.

2. **N+1 leaderboard queries** — `PointController::getAllUserPoints()` fires approximately 3N database queries for a league with N members: one call to `getUserProfilePoints()`, one to `getStandingsUserPoints()`, and one to `getPredictionSurvivalUserPoints()` per user. For a 20-member league this is ~60 queries per leaderboard page load.

---

## Part 1: Dead Code Removal

### Files to delete

| File | Reason |
|---|---|
| `resources/views/partials/previous.blade.php` | Never `@include`d anywhere; references `$previousRoundPoints` which is always an empty array |

### Methods to delete

| File | Method | Reason |
|---|---|---|
| `app/Http/Controllers/PointController.php` | `getPointEventTotal($eventID, $leagueID)` | Marked `@deprecated`; no external callers |
| `app/Http/Controllers/PointController.php` | `getPointPredictionUserEvent($userID, $eventID)` | Only called by `getPointEventTotal` |
| `app/Http/Controllers/PointController.php` | `getPointSurvivalUserEvent($userID, $eventID)` | Only called by `getPointEventTotal` |

### Lines to remove

| File | Lines | Content |
|---|---|---|
| `app/Http/Controllers/MainController.php` | 43–44 | `// @deprecated previousRoundPoints removed from view` + `$previousRoundPoints = [];` |

Database migrations are untouched. No data is affected.

---

## Part 2: Bulk Leaderboard Queries

### Current behaviour

`PointController::getAllUserPoints(int $leagueID)`:

1. Fetches league members — 1 query
2. For each user (N iterations):
   - `PointResultController::getUserProfilePoints($userId, $leagueId)` — 1+ queries (point_results join; optionally league, league_members, league_game_odds)
   - `PointStandingController::getStandingsUserPoints($userId)` — 1 query
   - `PointSurvivalController::getPredictionSurvivalUserPoints($userId)` — 1 query
3. Total: **1 + 3N+ queries**

### New behaviour

Replace the three per-user calls with three new bulk methods. Total: **4–5 queries**, independent of league size.

---

### New method 1: `PointResultController::getBulkUserGamePoints(array $userIds, int $leagueId): array`

Fetches all game point rows for the given users in a single query, handles league-odds adjustment in PHP (once, not per user), and returns per-user aggregates.

**Returns:** `array<int, array{game_points: float, streak_points: float, bingo_points: int, game_count: int}>`

**Implementation:**

```php
public function getBulkUserGamePoints(array $userIds, int $leagueId): array
{
    if (empty($userIds)) {
        return [];
    }

    $rows = DB::table('point_results as pr')
        ->join('games as g', 'pr.game_id', '=', 'g.id')
        ->whereIn('pr.user_id', $userIds)
        ->select('pr.user_id', 'pr.game_id', 'pr.full_points', 'pr.streak_bonus',
                 'pr.bingo_points', 'pr.winner_points', 'pr.odds_points',
                 'g.home_team_score', 'g.away_team_score')
        ->get();

    // Determine if league uses custom odds (one check, not per-user)
    $leagueOddsMap = [];
    $league = \App\Models\League::find($leagueId);
    if ($league && $league->use_league_odds) {
        $memberCount = \App\Models\LeagueMember::where('league_id', $leagueId)
            ->where('is_guest', false)->count();
        if ($memberCount >= 20) {
            $gameIds = $rows->pluck('game_id')->unique();
            $leagueOddsMap = DB::table('league_game_odds')
                ->where('league_id', $leagueId)
                ->whereIn('game_id', $gameIds)
                ->get()->keyBy('game_id');
        }
    }

    $result = [];
    foreach ($rows as $row) {
        $uid = $row->user_id;
        if (!isset($result[$uid])) {
            $result[$uid] = ['game_points' => 0.0, 'streak_points' => 0.0,
                             'bingo_points' => 0, 'game_count' => 0];
        }

        $gamePoints = (float) $row->full_points;

        if (!empty($leagueOddsMap) && isset($leagueOddsMap[$row->game_id])) {
            $lo = $leagueOddsMap[$row->game_id];
            $winnerPts = (float) $row->winner_points;
            if ($winnerPts > 0 && $row->home_team_score !== null && $row->away_team_score !== null) {
                if ($row->home_team_score > $row->away_team_score) {
                    $oddsRate = (float) $lo->home_odds;
                } elseif ($row->home_team_score == $row->away_team_score) {
                    $oddsRate = (float) $lo->draw_odds;
                } else {
                    $oddsRate = (float) $lo->away_odds;
                }
                $gamePoints = $gamePoints - (float) $row->odds_points
                            + $winnerPts * ($oddsRate - 1);
            }
        }

        $result[$uid]['game_points']   += $gamePoints;
        $result[$uid]['streak_points'] += (float) ($row->streak_bonus ?? 0);
        $result[$uid]['bingo_points']  += (int)   $row->bingo_points;
        $result[$uid]['game_count']    += 1;
    }

    return $result;
}
```

**Note on league odds:** The join on `games` fetches `home_team_score` and `away_team_score` needed to determine winner direction (home win / draw / away win), which selects the correct odds column. This matches the logic in the existing `getUserProfilePoints` method.

---

### New method 2: `PointStandingController::getBulkUserStandingPoints(array $userIds): array`

Single aggregate query returning the same per-user shape as `getStandingsUserPoints()`.

**Returns:** `array<int, object>` — keyed by user ID, each value is a stdClass with the same columns as the existing single-user method (`group_position_points`, `last32_points`, `last16_points`, `quarterfinal_points`, `semifinal_points`, `final_points`, `total_points`).

**Implementation:**

```php
public function getBulkUserStandingPoints(array $userIds): array
{
    if (empty($userIds)) {
        return [];
    }

    $rows = DB::table('point_standings')
        ->selectRaw('
            user_id,
            SUM(IFNULL(group_position_points,0))  AS group_position_points,
            SUM(IFNULL(last32_points,0))          AS last32_points,
            SUM(IFNULL(last16_points,0))          AS last16_points,
            SUM(IFNULL(quarterfinal_points,0))    AS quarterfinal_points,
            SUM(IFNULL(semifinal_points,0))       AS semifinal_points,
            SUM(IFNULL(final_points,0))           AS final_points,
            SUM(
                IFNULL(group_position_points,0) + IFNULL(last32_points,0)
                + IFNULL(last16_points,0) + IFNULL(quarterfinal_points,0)
                + IFNULL(semifinal_points,0) + IFNULL(final_points,0)
            ) AS total_points
        ')
        ->whereIn('user_id', $userIds)
        ->groupBy('user_id')
        ->get();

    $result = [];
    foreach ($rows as $row) {
        $result[$row->user_id] = $row;
    }

    // Fill missing users with zero object (matches existing getStandingsUserPoints behaviour)
    foreach ($userIds as $uid) {
        if (!isset($result[$uid])) {
            $zero = new \stdClass();
            $zero->group_position_points = '0';
            $zero->last32_points         = '0';
            $zero->last16_points         = '0';
            $zero->quarterfinal_points   = '0';
            $zero->semifinal_points      = '0';
            $zero->final_points          = '0';
            $zero->total_points          = '0';
            $result[$uid] = $zero;
        }
    }

    return $result;
}
```

---

### New method 3: `PointSurvivalController::getBulkUserSurvivalPoints(array $userIds): array`

**Returns:** `array<int, float>` — keyed by user ID.

**Implementation:**

```php
public function getBulkUserSurvivalPoints(array $userIds): array
{
    if (empty($userIds)) {
        return [];
    }

    $rows = DB::table('point_survivals')
        ->selectRaw('user_id, SUM(survival_points) as total')
        ->whereIn('user_id', $userIds)
        ->groupBy('user_id')
        ->get();

    $result = array_fill_keys($userIds, 0.0);
    foreach ($rows as $row) {
        $result[$row->user_id] = (float) $row->total;
    }

    return $result;
}
```

---

### Rewritten `PointController::getAllUserPoints()`

```php
public function getAllUserPoints($leagueID): array
{
    $users = DB::table('users')
        ->join('league_members', 'users.id', '=', 'league_members.user_id')
        ->where('league_members.league_id', '=', $leagueID)
        ->where('league_members.is_guest', '<=', session('guest'))
        ->select('users.id', 'users.username', 'users.name', 'users.surname')
        ->get();

    if ($users->isEmpty()) {
        return [];
    }

    $userIds = $users->pluck('id')->toArray();

    $pointsResultController  = app(PointResultController::class);
    $pointStandingController = app(PointStandingController::class);
    $pointSurvivalController = new PointSurvivalController();

    $gamePoints     = $pointsResultController->getBulkUserGamePoints($userIds, $leagueID);
    $standingPoints = $pointStandingController->getBulkUserStandingPoints($userIds);
    $survivalPoints = $pointSurvivalController->getBulkUserSurvivalPoints($userIds);

    $userAllPoints = [];
    foreach ($users as $user) {
        $gp = $gamePoints[$user->id] ?? ['game_points' => 0.0, 'streak_points' => 0.0,
                                          'bingo_points' => 0, 'game_count' => 0];

        $userAllPoints[] = [
            'userID'          => $user->id,
            'username'        => $user->username,
            'name'            => $user->name,
            'surname'         => $user->surname,
            'userFee'         => null,
            'userGamePoints'  => round($gp['game_points'], 1),
            'userStreakPoints' => round($gp['streak_points'], 1),
            'userGameBingo'   => $gp['bingo_points'],
            'averagePoints'   => $gp['game_count'] > 0
                                    ? round($gp['game_points'] / $gp['game_count'], 1)
                                    : 0,
            'standingPoints'  => $standingPoints[$user->id],
            'survivalPoints'  => $survivalPoints[$user->id],
        ];
    }

    usort($userAllPoints, function ($a, $b) {
        return $b['userGamePoints'] + $b['userStreakPoints']
             + $b['standingPoints']->total_points + $b['survivalPoints']
           <=> $a['userGamePoints'] + $a['userStreakPoints']
             + $a['standingPoints']->total_points + $a['survivalPoints'];
    });

    return $userAllPoints;
}
```

---

## Existing methods preserved

The following per-user methods are **not deleted** — they are still used by profile pages and other callers:

- `PointResultController::getUserProfilePoints(int $userID, ?int $leagueId)` — used by user profile view
- `PointStandingController::getStandingsUserPoints(int $userID)` — used by individual profile
- `PointSurvivalController::getPredictionSurvivalUserPoints($userID)` — used by profile

---

## Testing

| Change | Test approach |
|---|---|
| Dead code removal | Run full test suite — no regressions expected |
| `getBulkUserGamePoints` | Unit: assert output matches calling `getUserProfilePoints` per-user on a seeded dataset |
| `getBulkUserStandingPoints` | Unit: assert output matches calling `getStandingsUserPoints` per-user |
| `getBulkUserSurvivalPoints` | Unit: assert output matches calling `getPredictionSurvivalUserPoints` per-user |
| `getAllUserPoints` refactor | Integration: assert leaderboard order and point totals are identical before and after |

---

## Out of Scope

- Session dependency injection refactor (large architectural change)
- Return type annotations across all controllers
- MessageController null return when neither update nor delete key present
- `deleteGame` route-level guard (pre-existing gap, separate task)
