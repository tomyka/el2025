# Code Quality Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remove dead code left over from the "previous round" feature and replace the N+1 leaderboard query loop with three bulk queries.

**Architecture:** Two sequential tasks — safe deletion first (no behaviour change, no tests needed), then TDD bulk methods + leaderboard refactor. The three new bulk methods mirror the shape of their per-user counterparts so `getAllUserPoints()` stays easy to follow. Existing per-user methods (`getUserProfilePoints`, `getStandingsUserPoints`, `getPredictionSurvivalUserPoints`) are preserved — they're still used by profile pages.

**Tech Stack:** Laravel 11, PHP 8.2+, PHPUnit, SQLite (tests), DB query builder

---

## File Map

| File | Change |
|---|---|
| `app/Http/Controllers/PointController.php` | Delete 3 deprecated methods; rewrite `getAllUserPoints()` to use bulk methods |
| `app/Http/Controllers/MainController.php` | Remove 2 dead lines (comment + `$previousRoundPoints = []`) |
| `resources/views/partials/previous.blade.php` | **Delete** — never `@include`d, always empty |
| `app/Http/Controllers/PointResultController.php` | Add `getBulkUserGamePoints(array $userIds, int $leagueId): array` |
| `app/Http/Controllers/PointStandingController.php` | Add `getBulkUserStandingPoints(array $userIds): array` |
| `app/Http/Controllers/PointSurvivalController.php` | Add `getBulkUserSurvivalPoints(array $userIds): array` |
| `tests/Feature/BulkPointsTest.php` | **Create** — tests for all three bulk methods |

---

## Task 1: Delete dead code

No tests needed — these are unreachable paths with zero callers outside themselves.

**Files:**
- Modify: `app/Http/Controllers/PointController.php`
- Modify: `app/Http/Controllers/MainController.php`
- Delete: `resources/views/partials/previous.blade.php`

- [ ] **Step 1: Delete three methods from `PointController`**

Open `app/Http/Controllers/PointController.php` and remove the following block (lines 62–110 inclusive — the `@deprecated` docblock through the end of `getPointSurvivalUserEvent`):

```php
    /**
     * @deprecated No longer used — Praėjusio turo lyderiai section removed from main view.
     */
    public function getPointEventTotal($eventID, $leagueID){
        // ... entire method body
    }

    public function getPointPredictionUserEvent($userID, $eventID){
        // ... entire method body
    }

    public function getPointSurvivalUserEvent($userID, $eventID){
        // ... entire method body
    }
```

After deletion, `PointController` should contain only: `getAllUserPoints`, `getAllUsersGameHistory`, `getRankHistory`, `getPredictionStandingsUserPoints`.

Also remove both now-unused model imports from the top of the file — neither `PointResult` nor `PointSurvival` is used by any remaining method:

```php
// DELETE both of these lines:
use App\Models\PointResult;
use App\Models\PointSurvival;
```

- [ ] **Step 2: Remove two dead lines from `MainController`**

In `app/Http/Controllers/MainController.php`, find and remove lines 43–44:

```php
            // @deprecated previousRoundPoints removed from view
            $previousRoundPoints = [];
```

The surrounding code before and after those lines must remain unchanged.

- [ ] **Step 3: Delete the orphaned Blade partial**

```bash
rm resources/views/partials/previous.blade.php
```

- [ ] **Step 4: Run the full test suite**

```bash
php artisan test
```

Expected: same pass count as before (185 tests, 0 failures). No test referenced any of the deleted code.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "chore: remove deprecated previousRoundPoints methods and view partial"
```

---

## Task 2: Bulk query methods (TDD)

Write all tests first, watch them fail, then implement.

**Files:**
- Create: `tests/Feature/BulkPointsTest.php`
- Modify: `app/Http/Controllers/PointResultController.php`
- Modify: `app/Http/Controllers/PointStandingController.php`
- Modify: `app/Http/Controllers/PointSurvivalController.php`

### 2a — `getBulkUserGamePoints`

- [ ] **Step 1: Create `tests/Feature/BulkPointsTest.php` with the game-points test**

```php
<?php

namespace Tests\Feature;

use App\Http\Controllers\PointResultController;
use App\Http\Controllers\PointStandingController;
use App\Http\Controllers\PointSurvivalController;
use App\Models\Event;
use App\Models\Game;
use App\Models\League;
use App\Models\LeagueMember;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BulkPointsTest extends TestCase
{
    use RefreshDatabase;

    private League $league;

    protected function setUp(): void
    {
        parent::setUp();
        $this->league = League::factory()->create(['use_league_odds' => false]);
    }

    private function makeUser(): User
    {
        $user = User::factory()->create();
        LeagueMember::factory()->create([
            'user_id'   => $user->id,
            'league_id' => $this->league->id,
            'is_guest'  => false,
        ]);
        return $user;
    }

    private function makeGame(): Game
    {
        $event = Event::create([
            'event' => 'Test', 'event_day' => 1,
            'event_survival' => 0, 'active' => 1, 'rate' => 1,
        ]);
        $home = Team::create(['team' => 'H' . uniqid()]);
        $away = Team::create(['team' => 'A' . uniqid()]);
        return Game::create([
            'event_id'        => $event->id,
            'home_team_id'    => $home->id,
            'away_team_id'    => $away->id,
            'game_date'       => now(),
            'home_team_score' => 1,
            'away_team_score' => 0,
        ]);
    }

    private function insertResult(int $userId, int $gameId, array $data = []): void
    {
        DB::table('point_results')->insert(array_merge([
            'user_id'           => $userId,
            'game_id'           => $gameId,
            'winner_points'     => 0,
            'difference_points' => 0,
            'bingo_points'      => 0,
            'odds'              => 1,
            'odds_points'       => 0,
            'full_points'       => 0,
            'streak_bonus'      => null,
            'created_at'        => now(),
            'updated_at'        => now(),
        ], $data));
    }

    private function insertStanding(int $userId, array $data = []): void
    {
        DB::table('point_standings')->insert(array_merge([
            'user_id'              => $userId,
            'team_id'              => Team::create(['team' => 'T' . uniqid()])->id,
            'group_position_points' => 0,
            'last32_points'        => 0,
            'last16_points'        => 0,
            'quarterfinal_points'  => 0,
            'semifinal_points'     => 0,
            'final_points'         => 0,
        ], $data));
    }

    private function insertSurvival(int $userId, int $eventId, float $pts): void
    {
        DB::table('point_survivals')->insert([
            'user_id'         => $userId,
            'event_id'        => $eventId,
            'survival_points' => $pts,
            'team_id'         => Team::create(['team' => 'S' . uniqid()])->id,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    // ── getBulkUserGamePoints ─────────────────────────────────────────

    public function test_bulk_game_points_matches_per_user_calls(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        $game1 = $this->makeGame();
        $game2 = $this->makeGame();

        $this->insertResult($userA->id, $game1->id, [
            'full_points' => 100.0, 'streak_bonus' => 10.0, 'bingo_points' => 50,
        ]);
        $this->insertResult($userA->id, $game2->id, [
            'full_points' => 50.0, 'streak_bonus' => 0, 'bingo_points' => 0,
        ]);
        $this->insertResult($userB->id, $game1->id, [
            'full_points' => 75.0, 'streak_bonus' => 5.0, 'bingo_points' => 50,
        ]);

        $controller = app(PointResultController::class);
        $bulk       = $controller->getBulkUserGamePoints(
            [$userA->id, $userB->id], $this->league->id
        );

        $this->assertArrayHasKey($userA->id, $bulk);
        $this->assertArrayHasKey($userB->id, $bulk);

        // userA: 100 + 50 = 150 game pts, 10 + 0 = 10 streak, 1 bingo (game1 only), 2 games
        $this->assertEquals(150.0, $bulk[$userA->id]['game_points']);
        $this->assertEquals(10.0,  $bulk[$userA->id]['streak_points']);
        $this->assertEquals(1,     $bulk[$userA->id]['bingo_count']);
        $this->assertEquals(2,     $bulk[$userA->id]['game_count']);

        // userB: 75 game pts, 5 streak, 1 bingo, 1 game
        $this->assertEquals(75.0, $bulk[$userB->id]['game_points']);
        $this->assertEquals(5.0,  $bulk[$userB->id]['streak_points']);
        $this->assertEquals(1,    $bulk[$userB->id]['bingo_count']);
        $this->assertEquals(1,    $bulk[$userB->id]['game_count']);
    }

    public function test_bulk_game_points_returns_zeros_for_user_with_no_results(): void
    {
        $user       = $this->makeUser();
        $controller = app(PointResultController::class);
        $bulk       = $controller->getBulkUserGamePoints([$user->id], $this->league->id);

        $this->assertArrayNotHasKey($user->id, $bulk);
    }

    public function test_bulk_game_points_empty_array_returns_empty(): void
    {
        $controller = app(PointResultController::class);
        $this->assertSame([], $controller->getBulkUserGamePoints([], $this->league->id));
    }
}
```

- [ ] **Step 2: Run to confirm the test fails**

```bash
php artisan test tests/Feature/BulkPointsTest.php --filter test_bulk_game_points
```

Expected: FAIL — `Call to undefined method getBulkUserGamePoints()`

- [ ] **Step 3: Add `getBulkUserGamePoints` to `PointResultController`**

Add this method to `app/Http/Controllers/PointResultController.php` (after `getUserProfilePoints`):

```php
public function getBulkUserGamePoints(array $userIds, int $leagueId): array
{
    if (empty($userIds)) {
        return [];
    }

    $rows = DB::table('point_results as pr')
        ->join('games as g', 'pr.game_id', '=', 'g.id')
        ->join('events as e', 'g.event_id', '=', 'e.id')
        ->whereIn('pr.user_id', $userIds)
        ->select(
            'pr.user_id', 'pr.game_id',
            'pr.full_points', 'pr.streak_bonus', 'pr.bingo_points',
            'pr.winner_points', 'pr.difference_points',
            'g.home_team_score', 'g.away_team_score',
            'e.rate'
        )
        ->get();

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
            $result[$uid] = [
                'game_points'   => 0.0,
                'streak_points' => 0.0,
                'bingo_count'   => 0,
                'game_count'    => 0,
            ];
        }

        $fullPointsLeague = (float) $row->full_points;

        if (!empty($leagueOddsMap)
            && isset($leagueOddsMap[$row->game_id])
            && $row->home_team_score !== null
            && $row->away_team_score !== null
        ) {
            $lo = $leagueOddsMap[$row->game_id];
            if ($row->home_team_score > $row->away_team_score) {
                $leagueOddsRate = (float) $lo->home_odds;
            } elseif ($row->home_team_score == $row->away_team_score) {
                $leagueOddsRate = (float) $lo->draw_odds;
            } else {
                $leagueOddsRate = (float) $lo->away_odds;
            }

            $winnerPointsLeague = $row->winner_points > 0
                ? round((1 + $leagueOddsRate) * 5.0 * (float) $row->rate, 1)
                : 0.0;

            $fullPointsLeague = round(
                $winnerPointsLeague
                + (float) $row->difference_points
                + (float) $row->bingo_points,
                1
            );
        }

        $result[$uid]['game_points']   += $fullPointsLeague;
        $result[$uid]['streak_points'] += (float) ($row->streak_bonus ?? 0);
        $result[$uid]['bingo_count']   += ($row->bingo_points != 0 ? 1 : 0);
        $result[$uid]['game_count']    += 1;
    }

    return $result;
}
```

- [ ] **Step 4: Run tests to confirm they pass**

```bash
php artisan test tests/Feature/BulkPointsTest.php --filter test_bulk_game_points
```

Expected: 3 tests PASS.

---

### 2b — `getBulkUserStandingPoints`

- [ ] **Step 5: Add standing-points tests to `BulkPointsTest.php`**

Append these methods to the `BulkPointsTest` class:

```php
    // ── getBulkUserStandingPoints ─────────────────────────────────────

    public function test_bulk_standing_points_matches_per_user_calls(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();

        $this->insertStanding($userA->id, ['group_position_points' => 30, 'last16_points' => 60]);
        $this->insertStanding($userA->id, ['quarterfinal_points' => 90]);
        $this->insertStanding($userB->id, ['semifinal_points' => 120, 'final_points' => 200]);

        $controller = app(PointStandingController::class);
        $bulk       = $controller->getBulkUserStandingPoints([$userA->id, $userB->id]);

        $this->assertArrayHasKey($userA->id, $bulk);
        $this->assertArrayHasKey($userB->id, $bulk);

        // userA: 30 + 60 + 90 = 180
        $this->assertEquals(180, $bulk[$userA->id]->total_points);
        $this->assertEquals(30,  $bulk[$userA->id]->group_position_points);
        $this->assertEquals(60,  $bulk[$userA->id]->last16_points);
        $this->assertEquals(90,  $bulk[$userA->id]->quarterfinal_points);

        // userB: 120 + 200 = 320
        $this->assertEquals(320, $bulk[$userB->id]->total_points);
    }

    public function test_bulk_standing_points_fills_zeros_for_user_with_no_rows(): void
    {
        $user       = $this->makeUser();
        $controller = app(PointStandingController::class);
        $bulk       = $controller->getBulkUserStandingPoints([$user->id]);

        $this->assertArrayHasKey($user->id, $bulk);
        $this->assertEquals('0', $bulk[$user->id]->total_points);
    }

    public function test_bulk_standing_points_empty_array_returns_empty(): void
    {
        $controller = app(PointStandingController::class);
        $this->assertSame([], $controller->getBulkUserStandingPoints([]));
    }
```

- [ ] **Step 6: Run to confirm these tests fail**

```bash
php artisan test tests/Feature/BulkPointsTest.php --filter test_bulk_standing
```

Expected: FAIL — `Call to undefined method getBulkUserStandingPoints()`

- [ ] **Step 7: Add `getBulkUserStandingPoints` to `PointStandingController`**

Add this method to `app/Http/Controllers/PointStandingController.php` (after `getStandingsUserPoints`):

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

    $zero                        = new \stdClass();
    $zero->group_position_points = '0';
    $zero->last32_points         = '0';
    $zero->last16_points         = '0';
    $zero->quarterfinal_points   = '0';
    $zero->semifinal_points      = '0';
    $zero->final_points          = '0';
    $zero->total_points          = '0';

    foreach ($userIds as $uid) {
        if (!isset($result[$uid])) {
            $result[$uid] = clone $zero;
        }
    }

    return $result;
}
```

- [ ] **Step 8: Run tests to confirm they pass**

```bash
php artisan test tests/Feature/BulkPointsTest.php --filter test_bulk_standing
```

Expected: 3 tests PASS.

---

### 2c — `getBulkUserSurvivalPoints`

- [ ] **Step 9: Add survival-points tests to `BulkPointsTest.php`**

Append these methods to the `BulkPointsTest` class:

```php
    // ── getBulkUserSurvivalPoints ─────────────────────────────────────

    public function test_bulk_survival_points_matches_per_user_calls(): void
    {
        $event1 = Event::create([
            'event' => 'R1', 'event_day' => 1, 'event_survival' => 1, 'active' => 1, 'rate' => 1,
        ]);
        $event2 = Event::create([
            'event' => 'R2', 'event_day' => 2, 'event_survival' => 1, 'active' => 1, 'rate' => 1,
        ]);
        $userA  = $this->makeUser();
        $userB  = $this->makeUser();
        $userC  = $this->makeUser();

        // userA: two rows across different events = 30 + 20 = 50
        $this->insertSurvival($userA->id, $event1->id, 30.0);
        $this->insertSurvival($userA->id, $event2->id, 20.0);
        $this->insertSurvival($userB->id, $event1->id, 75.0);
        // userC has no survival rows

        $controller = app(PointSurvivalController::class);
        $bulk       = $controller->getBulkUserSurvivalPoints([$userA->id, $userB->id, $userC->id]);

        $this->assertArrayHasKey($userA->id, $bulk);
        $this->assertArrayHasKey($userB->id, $bulk);
        $this->assertArrayHasKey($userC->id, $bulk);

        $this->assertEquals(50.0, $bulk[$userA->id]);
        $this->assertEquals(75.0, $bulk[$userB->id]);
        $this->assertEquals(0.0,  $bulk[$userC->id]);
    }

    public function test_bulk_survival_points_empty_array_returns_empty(): void
    {
        $controller = app(PointSurvivalController::class);
        $this->assertSame([], $controller->getBulkUserSurvivalPoints([]));
    }
```

- [ ] **Step 10: Run to confirm these tests fail**

```bash
php artisan test tests/Feature/BulkPointsTest.php --filter test_bulk_survival
```

Expected: FAIL — `Call to undefined method getBulkUserSurvivalPoints()`

- [ ] **Step 11: Add `getBulkUserSurvivalPoints` to `PointSurvivalController`**

Add this method to `app/Http/Controllers/PointSurvivalController.php` (after `getPredictionSurvivalUserPoints`):

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

- [ ] **Step 12: Run all bulk tests to confirm they all pass**

```bash
php artisan test tests/Feature/BulkPointsTest.php
```

Expected: all tests PASS.

- [ ] **Step 13: Run the full suite to confirm no regressions**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 14: Commit**

```bash
git add tests/Feature/BulkPointsTest.php \
        app/Http/Controllers/PointResultController.php \
        app/Http/Controllers/PointStandingController.php \
        app/Http/Controllers/PointSurvivalController.php
git commit -m "feat: add bulk leaderboard query methods (getBulkUserGamePoints, standing, survival)"
```

---

## Task 3: Rewrite `getAllUserPoints` using bulk methods

Write an integration test that captures the expected output first, then refactor and verify the test still passes.

**Files:**
- Modify: `app/Http/Controllers/PointController.php`
- Modify: `tests/Feature/BulkPointsTest.php`

- [ ] **Step 1: Add a golden-master integration test to `BulkPointsTest.php`**

Append this method to the `BulkPointsTest` class:

```php
    // ── getAllUserPoints integration ───────────────────────────────────

    public function test_get_all_user_points_returns_correct_order_and_totals(): void
    {
        session(['guest' => 0]);

        $event = Event::create([
            'event' => 'R1', 'event_day' => 1, 'event_survival' => 0, 'active' => 1, 'rate' => 1,
        ]);
        $home = Team::create(['team' => 'H' . uniqid()]);
        $away = Team::create(['team' => 'A' . uniqid()]);
        $game = Game::create([
            'event_id'        => $event->id,
            'home_team_id'    => $home->id,
            'away_team_id'    => $away->id,
            'game_date'       => now(),
            'home_team_score' => 2,
            'away_team_score' => 1,
        ]);

        $userA = $this->makeUser(); // will rank 1st
        $userB = $this->makeUser(); // will rank 2nd
        $userC = $this->makeUser(); // will rank 3rd

        // userA: 200 game pts
        $this->insertResult($userA->id, $game->id, ['full_points' => 200.0]);
        // userB: 100 game pts + 50 standing pts = 150 total
        $this->insertResult($userB->id, $game->id, ['full_points' => 100.0]);
        $this->insertStanding($userB->id, ['group_position_points' => 50]);
        // userC: 50 game pts + 30 survival pts = 80 total
        $this->insertResult($userC->id, $game->id, ['full_points' => 50.0]);
        $this->insertSurvival($userC->id, $event->id, 30.0);

        $controller = new \App\Http\Controllers\PointController();
        $result     = $controller->getAllUserPoints($this->league->id);

        $this->assertCount(3, $result);
        $this->assertEquals($userA->id, $result[0]['userID']);  // 200 pts
        $this->assertEquals($userB->id, $result[1]['userID']);  // 150 pts
        $this->assertEquals($userC->id, $result[2]['userID']);  // 80 pts

        $this->assertEquals(200.0, $result[0]['userGamePoints']);
        $this->assertEquals(100.0, $result[1]['userGamePoints']);
        $this->assertEquals(50.0,  $result[1]['standingPoints']->total_points);
        $this->assertEquals(30.0,  $result[2]['survivalPoints']);
    }
```

- [ ] **Step 2: Run the test against the current (old) implementation to confirm it passes**

```bash
php artisan test tests/Feature/BulkPointsTest.php --filter test_get_all_user_points
```

Expected: PASS. This is the golden master — the refactor must keep it passing.

- [ ] **Step 3: Rewrite `PointController::getAllUserPoints()`**

Replace the entire `getAllUserPoints` method in `app/Http/Controllers/PointController.php`:

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
        $gp = $gamePoints[$user->id] ?? [
            'game_points'   => 0.0,
            'streak_points' => 0.0,
            'bingo_count'   => 0,
            'game_count'    => 0,
        ];

        $userAllPoints[] = [
            'userID'          => $user->id,
            'username'        => $user->username,
            'name'            => $user->name,
            'surname'         => $user->surname,
            'userFee'         => null,
            'userGamePoints'  => round($gp['game_points'], 1),
            'userStreakPoints' => round($gp['streak_points'], 1),
            'userGameBingo'   => $gp['bingo_count'],
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

(Both unused model imports were already removed in Task 1.)

- [ ] **Step 4: Run the golden-master test**

```bash
php artisan test tests/Feature/BulkPointsTest.php --filter test_get_all_user_points
```

Expected: PASS — same order and totals as the old implementation.

- [ ] **Step 5: Run the full suite**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/PointController.php tests/Feature/BulkPointsTest.php
git commit -m "perf: replace N+1 leaderboard queries with bulk fetches in getAllUserPoints"
```

---

## Final verification

- [ ] **Run the full suite one last time**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Push**

```bash
git push
```
