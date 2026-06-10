# Leagues — Plan 3 of 3: Per-League Odds

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Prerequisite:** Plan 1 (Foundation) and Plan 2 (Membership UI) must be complete. `league_game_odds` table exists. `LeagueController`, `League`, and `LeagueMember` models are in place.

**Goal:** When a league opts into per-league odds (`use_league_odds = true`) and has ≥ 20 members, compute separate `league_game_odds` rows for that league from its members' predictions only, and recalculate `odds_points` at leaderboard query time using those per-league odds.

**Architecture:** `GameOddsController::updateGameOdds()` is extended to also write `league_game_odds` rows for each opt-in league with enough members. `PointResultController::getUserProfilePoints()` (called by `PointController::getAllUserPoints()`) gains a `$leagueId` parameter that, when a league has active per-league odds, recalculates `odds_points` on the fly using the league-specific odds rate — no new `point_results` rows are written.

**Tech Stack:** Laravel 11 · PHP 8.2 · MySQL (prod) · SQLite (tests) · PHPUnit

**Spec:** `docs/superpowers/specs/2026-06-10-leagues-design.md` — section "Odds Isolation"

**Related plans:**
- Plan 1 — Foundation (prerequisite)
- Plan 2 — Membership UI (prerequisite)

---

## Files

| Action | Path |
|---|---|
| Modify | `app/Http/Controllers/GameOddsController.php` |
| Modify | `app/Http/Controllers/PointResultController.php` |
| Modify | `app/Http/Controllers/PointController.php` |
| Modify | `app/Http/Controllers/LeagueController.php` (add toggle endpoint) |
| Modify | `resources/views/leagues/index.blade.php` (add toggle UI) |
| Modify | `routes/web.php` |
| Create | `tests/Feature/LeagueOddsTest.php` |

---

## Task 1: Extend GameOddsController to write per-league odds

**Files:**
- Modify: `app/Http/Controllers/GameOddsController.php`
- Create: `tests/Feature/LeagueOddsTest.php`

When `updateGameOdds($gameId)` is called (after a result is saved), it should also compute odds for every opt-in league with ≥ 20 members, counting only that league's members' predictions.

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/LeagueOddsTest.php
<?php
namespace Tests\Feature;

use App\Models\League;
use App\Models\LeagueMember;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeagueOddsTest extends TestCase
{
    use RefreshDatabase;

    private function makeLeagueWith20Members(bool $useLeagueOdds = true): array
    {
        $league = League::create([
            'name'            => 'Big League',
            'is_public'       => false,
            'use_league_odds' => $useLeagueOdds,
        ]);

        $users = [];
        for ($i = 0; $i < 20; $i++) {
            $user = User::factory()->create();
            UserSetting::create(['user_id' => $user->id, 'admin' => 0]);
            LeagueMember::create(['league_id' => $league->id, 'user_id' => $user->id, 'active' => false, 'is_guest' => false]);
            $users[] = $user;
        }

        return [$league, $users];
    }

    public function test_updateGameOdds_writes_league_game_odds_for_opt_in_league(): void
    {
        // Minimal game setup
        $event = \App\Models\Event::create(['event' => 'Test', 'rate' => 1.0, 'event_survival' => 0]);
        $homeTeam = \App\Models\Team::create(['team' => 'Home', 'group_name' => 'A', 'link' => '', 'active' => 1, 'position' => 1, 'final' => null]);
        $awayTeam = \App\Models\Team::create(['team' => 'Away', 'group_name' => 'A', 'link' => '', 'active' => 1, 'position' => 2, 'final' => null]);
        $game = \App\Models\Game::create([
            'event_id'       => $event->id,
            'home_team_id'   => $homeTeam->id,
            'away_team_id'   => $awayTeam->id,
            'game_date'      => now()->addDays(1)->toDateTimeString(),
        ]);

        [$league, $users] = $this->makeLeagueWith20Members(true);

        // 15 users predict home win (1-0), 5 predict draw (0-0)
        foreach (array_slice($users, 0, 15) as $user) {
            \App\Models\PredictionResult::create([
                'user_id'           => $user->id,
                'game_id'           => $game->id,
                'home_team_score'   => 1,
                'away_team_score'   => 0,
                'generated'         => false,
            ]);
        }
        foreach (array_slice($users, 15, 5) as $user) {
            \App\Models\PredictionResult::create([
                'user_id'           => $user->id,
                'game_id'           => $game->id,
                'home_team_score'   => 0,
                'away_team_score'   => 0,
                'generated'         => false,
            ]);
        }

        $controller = new \App\Http\Controllers\GameOddsController();
        $controller->updateGameOdds($game->id);

        // Global game_odds should exist
        $this->assertDatabaseHas('game_odds', ['game_id' => $game->id]);

        // Per-league odds should also exist
        $this->assertDatabaseHas('league_game_odds', [
            'league_id' => $league->id,
            'game_id'   => $game->id,
        ]);
    }

    public function test_updateGameOdds_skips_league_with_fewer_than_20_members(): void
    {
        $event = \App\Models\Event::create(['event' => 'Test2', 'rate' => 1.0, 'event_survival' => 0]);
        $homeTeam = \App\Models\Team::create(['team' => 'H2', 'group_name' => 'B', 'link' => '', 'active' => 1, 'position' => 1, 'final' => null]);
        $awayTeam = \App\Models\Team::create(['team' => 'A2', 'group_name' => 'B', 'link' => '', 'active' => 1, 'position' => 2, 'final' => null]);
        $game = \App\Models\Game::create([
            'event_id'       => $event->id,
            'home_team_id'   => $homeTeam->id,
            'away_team_id'   => $awayTeam->id,
            'game_date'      => now()->addDays(1)->toDateTimeString(),
        ]);

        // Only 5 members (< 20 threshold)
        $league = League::create(['name' => 'Small', 'is_public' => false, 'use_league_odds' => true]);
        for ($i = 0; $i < 5; $i++) {
            $user = User::factory()->create();
            LeagueMember::create(['league_id' => $league->id, 'user_id' => $user->id, 'active' => false, 'is_guest' => false]);
        }

        $controller = new \App\Http\Controllers\GameOddsController();
        $controller->updateGameOdds($game->id);

        $this->assertDatabaseMissing('league_game_odds', [
            'league_id' => $league->id,
            'game_id'   => $game->id,
        ]);
    }
}
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
php artisan test tests/Feature/LeagueOddsTest.php
```

Expected: FAIL — `updateGameOdds` doesn't write to `league_game_odds`.

- [ ] **Step 3: Extend GameOddsController::updateGameOdds()**

Read the current `app/Http/Controllers/GameOddsController.php` to understand the existing odds formula. The formula used is:

```
odds = 2 - (fraction of users predicting that outcome)
```

Where outcome is: home_win (home_score > away_score), draw (equal), away_win (away_score > home_score).

Add the following block at the **end** of `updateGameOdds($gameId)`, after the existing global `game_odds` upsert:

```php
// Per-league odds for opt-in leagues with >= 20 members
$optInLeagues = \App\Models\League::where('use_league_odds', true)->get();

foreach ($optInLeagues as $league) {
    $memberCount = \App\Models\LeagueMember::where('league_id', $league->id)
        ->where('is_guest', false)
        ->count();

    if ($memberCount < 20) {
        continue;
    }

    $memberIds = \App\Models\LeagueMember::where('league_id', $league->id)->pluck('user_id');

    $leaguePredictions = DB::table('prediction_results')
        ->where('game_id', $gameId)
        ->whereIn('user_id', $memberIds)
        ->where('generated', false)
        ->select('home_team_score', 'away_team_score')
        ->get();

    $total = $leaguePredictions->count();
    if ($total === 0) {
        continue;
    }

    $homeWins = $leaguePredictions->filter(fn($p) => $p->home_team_score > $p->away_team_score)->count();
    $draws    = $leaguePredictions->filter(fn($p) => $p->home_team_score == $p->away_team_score)->count();
    $awayWins = $leaguePredictions->filter(fn($p) => $p->home_team_score < $p->away_team_score)->count();

    $homeOdds = round(max(1.01, 2 - ($homeWins / $total)), 2);
    $drawOdds = round(max(1.01, 2 - ($draws    / $total)), 2);
    $awayOdds = round(max(1.01, 2 - ($awayWins / $total)), 2);

    DB::table('league_game_odds')->upsert(
        [
            'league_id'  => $league->id,
            'game_id'    => $gameId,
            'home_odds'  => $homeOdds,
            'draw_odds'  => $drawOdds,
            'away_odds'  => $awayOdds,
            'updated_at' => now(),
        ],
        ['league_id', 'game_id'],
        ['home_odds', 'draw_odds', 'away_odds', 'updated_at']
    );
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test tests/Feature/LeagueOddsTest.php
```

Expected: `Tests: 2 passed`

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/GameOddsController.php tests/Feature/LeagueOddsTest.php
git commit -m "feat: GameOddsController writes per-league odds for opt-in leagues >= 20 members"
```

---

## Task 2: Recalculate odds_points using per-league odds at leaderboard query time

**Files:**
- Modify: `app/Http/Controllers/PointResultController.php`
- Modify: `app/Http/Controllers/PointController.php`
- Modify: `tests/Feature/LeagueOddsTest.php`

The leaderboard calls `PointController::getAllUserPoints($leagueID)` → `PointResultController::getUserProfilePoints($userId)` per user. We need to pass `$leagueID` through so that when a league has per-league odds, the `odds_points` are recalculated using `league_game_odds` instead of `game_odds`.

**Key principle:** No new `point_results` rows are written. The per-league `odds_points` is a view-layer adjustment only.

- [ ] **Step 1: Write failing test**

Add to `tests/Feature/LeagueOddsTest.php`:

```php
public function test_league_odds_recalculated_in_leaderboard_when_active(): void
{
    $event = \App\Models\Event::firstOrCreate(
        ['event' => 'E3'],
        ['rate' => 1.0, 'event_survival' => 0]
    );
    $homeTeam = \App\Models\Team::create(['team' => 'H3', 'group_name' => 'C', 'link' => '', 'active' => 1, 'position' => 1, 'final' => null]);
    $awayTeam = \App\Models\Team::create(['team' => 'A3', 'group_name' => 'C', 'link' => '', 'active' => 1, 'position' => 2, 'final' => null]);
    $game = \App\Models\Game::create([
        'event_id'        => $event->id,
        'home_team_id'    => $homeTeam->id,
        'away_team_id'    => $awayTeam->id,
        'game_date'       => now()->subDay()->toDateTimeString(),
        'home_team_score' => 1,
        'away_team_score' => 0,
    ]);

    [$league, $users] = $this->makeLeagueWith20Members(true);
    $user = $users[0];

    // Global game_odds: home_odds = 1.8
    DB::table('game_odds')->insert([
        'game_id'    => $game->id,
        'home_odds'  => 1.8,
        'draw_odds'  => 1.5,
        'away_odds'  => 1.9,
        'updated_at' => now(),
    ]);

    // League-specific game_odds: home_odds = 1.5
    DB::table('league_game_odds')->insert([
        'league_id'  => $league->id,
        'game_id'    => $game->id,
        'home_odds'  => 1.5,
        'draw_odds'  => 1.6,
        'away_odds'  => 1.7,
        'updated_at' => now(),
    ]);

    // User predicted correct winner (home win 1-0)
    $prediction = \App\Models\PredictionResult::create([
        'user_id'           => $user->id,
        'game_id'           => $game->id,
        'home_team_score'   => 2,
        'away_team_score'   => 0,
        'generated'         => false,
    ]);

    // point_results with global odds: winner_points=50, odds_points=50*(1.8-1)=40
    DB::table('point_results')->insert([
        'user_id'           => $user->id,
        'game_id'           => $game->id,
        'winner_points'     => 50,
        'difference_points' => 30,
        'bingo_points'      => 0,
        'odds_points'       => 40, // 50 * (1.8 - 1)
        'full_points'       => 120,
        'streak_bonus'      => 0,
    ]);

    $controller = new \App\Http\Controllers\PointResultController();
    $profile    = $controller->getUserProfilePoints($user->id, $league->id);

    // With league odds (1.5): odds_points = 50 * (1.5-1) = 25
    $row = collect($profile)->firstWhere('game_id', $game->id);
    $this->assertNotNull($row);
    $this->assertEquals(25, $row['odds_points_league']);
    // full_points_in_league = 50 + 30 + 0 + 25 = 105
    $this->assertEquals(105, $row['full_points_league']);
}
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
php artisan test tests/Feature/LeagueOddsTest.php --filter test_league_odds_recalculated_in_leaderboard_when_active
```

Expected: FAIL — `getUserProfilePoints` does not accept `$leagueId` or compute league-specific odds.

- [ ] **Step 3: Update PointResultController::getUserProfilePoints()**

Find `getUserProfilePoints` in `app/Http/Controllers/PointResultController.php`. Add an optional `$leagueId = null` parameter. After fetching the standard `point_results`, if `$leagueId` is set and the league has `use_league_odds = true`, look up `league_game_odds` for each row and recalculate `odds_points`:

```php
public function getUserProfilePoints($userId, $leagueId = null): array
{
    // Existing query to get point_results rows — keep unchanged
    $rows = DB::table('point_results as pr')
        ->join('games as g', 'pr.game_id', '=', 'g.id')
        ->join('events as e', 'g.event_id', '=', 'e.id')
        ->join('teams as ht', 'g.home_team_id', '=', 'ht.id')
        ->join('teams as at', 'g.away_team_id', '=', 'at.id')
        ->where('pr.user_id', $userId)
        ->select(
            'pr.game_id',
            'pr.winner_points',
            'pr.difference_points',
            'pr.bingo_points',
            'pr.odds_points',
            'pr.full_points',
            'pr.streak_bonus',
            'g.home_team_score',
            'g.away_team_score',
            'ht.team as home_team',
            'at.team as away_team',
            'g.game_date',
            'e.rate'
        )
        ->get();

    // Check if this league uses per-league odds and has enough members
    $useLeagueOdds = false;
    if ($leagueId !== null) {
        $league = \App\Models\League::find($leagueId);
        if ($league && $league->use_league_odds) {
            $memberCount = \App\Models\LeagueMember::where('league_id', $leagueId)
                ->where('is_guest', false)
                ->count();
            $useLeagueOdds = $memberCount >= 20;
        }
    }

    $leagueOddsMap = [];
    if ($useLeagueOdds) {
        $gameIds = $rows->pluck('game_id');
        $leagueOddsMap = DB::table('league_game_odds')
            ->where('league_id', $leagueId)
            ->whereIn('game_id', $gameIds)
            ->get()
            ->keyBy('game_id');
    }

    $profile = [];
    foreach ($rows as $row) {
        $oddsPointsLeague = $row->odds_points;
        $fullPointsLeague = $row->full_points;

        if ($useLeagueOdds && isset($leagueOddsMap[$row->game_id])) {
            $lo = $leagueOddsMap[$row->game_id];

            // Determine which odds rate to use based on who won
            if ($row->home_team_score !== null && $row->away_team_score !== null) {
                if ($row->home_team_score > $row->away_team_score) {
                    $leagueOddsRate = $lo->home_odds;
                } elseif ($row->home_team_score == $row->away_team_score) {
                    $leagueOddsRate = $lo->draw_odds;
                } else {
                    $leagueOddsRate = $lo->away_odds;
                }

                // Only apply odds bonus if user predicted correct winner (winner_points > 0)
                $oddsPointsLeague = $row->winner_points > 0
                    ? round($row->winner_points * ($leagueOddsRate - 1), 1)
                    : 0;

                $fullPointsLeague = $row->winner_points
                    + $row->difference_points
                    + $row->bingo_points
                    + $oddsPointsLeague;
            }
        }

        $profile[] = [
            'game_id'           => $row->game_id,
            'home_team'         => $row->home_team,
            'away_team'         => $row->away_team,
            'game_date'         => $row->game_date,
            'winner_points'     => $row->winner_points,
            'difference_points' => $row->difference_points,
            'bingo_points'      => $row->bingo_points,
            'odds_points'       => $row->odds_points,
            'odds_points_league'=> $oddsPointsLeague,
            'full_points'       => $row->full_points,
            'full_points_league'=> $fullPointsLeague,
            'streak_bonus'      => $row->streak_bonus,
            'rate'              => $row->rate,
        ];
    }

    return $profile;
}
```

- [ ] **Step 4: Update PointController::getAllUserPoints() to pass leagueId**

In `app/Http/Controllers/PointController.php`, in `getAllUserPoints($leagueID)`, the line that calls `getUserProfilePoints`:

```php
// OLD:
$profile = $pointsResultController->getUserProfilePoints($user->id);
$userGamePoints = array_sum(array_column($profile, 'full_points'));

// NEW:
$profile         = $pointsResultController->getUserProfilePoints($user->id, $leagueID);
$userGamePoints  = array_sum(array_column($profile, 'full_points_league'));
$userStreakPoints = array_sum(array_column($profile, 'streak_bonus'));
```

- [ ] **Step 5: Run tests**

```bash
php artisan test tests/Feature/LeagueOddsTest.php
```

Expected: `Tests: 3 passed`

- [ ] **Step 6: Run full test suite to catch regressions**

```bash
php artisan test
```

Expected: all tests pass. If any test calls `getUserProfilePoints` with one argument and now fails because the return array has new keys, it's fine — new keys are backward-compatible.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/PointResultController.php \
        app/Http/Controllers/PointController.php \
        tests/Feature/LeagueOddsTest.php
git commit -m "feat: leaderboard recalculates odds_points using per-league odds when available"
```

---

## Task 3: League admin toggle for use_league_odds

**Files:**
- Modify: `app/Http/Controllers/LeagueController.php`
- Modify: `resources/views/leagues/index.blade.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/LeagueOddsTest.php`

League admins need a UI toggle to enable or disable per-league odds for their league.

- [ ] **Step 1: Write failing test**

Add to `tests/Feature/LeagueOddsTest.php`:

```php
public function test_league_owner_can_toggle_use_league_odds(): void
{
    $owner = User::factory()->create();
    UserSetting::create(['user_id' => $owner->id, 'admin' => 0]);
    $league = League::create(['name' => 'Tog', 'is_public' => false, 'owner_id' => $owner->id, 'use_league_odds' => false]);
    LeagueMember::create(['league_id' => $league->id, 'user_id' => $owner->id, 'is_admin' => true, 'active' => true]);

    $this->actingAs($owner);
    session(['userID' => $owner->id]);

    $this->post(route('leagues.toggleOdds'), ['leagueID' => $league->id, 'use_league_odds' => 1])
         ->assertRedirect();

    $this->assertDatabaseHas('leagues', ['id' => $league->id, 'use_league_odds' => true]);
}
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
php artisan test tests/Feature/LeagueOddsTest.php --filter test_league_owner_can_toggle_use_league_odds
```

Expected: FAIL.

- [ ] **Step 3: Add toggleOdds method to LeagueController**

Add to `app/Http/Controllers/LeagueController.php`:

```php
public function toggleOdds(Request $request): \Illuminate\Http\RedirectResponse
{
    $userId   = session('userID');
    $leagueId = $request->input('leagueID');

    // Only admin can toggle
    $membership = LeagueMember::where('user_id', $userId)
        ->where('league_id', $leagueId)
        ->where('is_admin', true)
        ->firstOrFail();

    $league = League::findOrFail($leagueId);
    $league->update(['use_league_odds' => (bool) $request->input('use_league_odds', false)]);

    return redirect()->route('leagues.index')->with('info', 'Lygos koeficientai atnaujinti');
}
```

- [ ] **Step 4: Add route**

Add to `routes/web.php` (inside authenticated group):

```php
Route::post('/leagues/toggleOdds', [LeagueController::class, 'toggleOdds'])->name('leagues.toggleOdds');
```

- [ ] **Step 5: Add toggle UI to leagues index view**

In `resources/views/leagues/index.blade.php`, inside the Manage modal (after the invite section), add:

```blade
{{-- Odds Toggle --}}
<div class="mt-3">
  <h6>Koeficientai</h6>
  <p class="text-muted small">Per-lygos koeficientai aktyvuojami kai lyga turi ≥ 20 narių.</p>
  <form method="POST" action="{{ route('leagues.toggleOdds') }}" id="oddsToggleForm">
    @csrf
    <input type="hidden" name="leagueID" id="oddsLeagueID">
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" name="use_league_odds" id="useLeagueOddsToggle"
             value="1" onchange="document.getElementById('oddsToggleForm').submit()">
      <label class="form-check-label small" for="useLeagueOddsToggle">
        Naudoti per-lygos koeficientus
      </label>
    </div>
  </form>
</div>
```

Update the `openManageModal` JavaScript function to also set `oddsLeagueID` and the toggle state:

```javascript
function openManageModal(leagueId, leagueName, useLeagueOdds) {
    activeManageLeagueId = leagueId;
    document.getElementById('manageModalTitle').textContent = 'Valdyti: ' + leagueName;
    document.getElementById('inviteLeagueID').value = leagueId;
    document.getElementById('oddsLeagueID').value = leagueId;
    document.getElementById('useLeagueOddsToggle').checked = useLeagueOdds;
    document.getElementById('searchResults').innerHTML = '';
    document.getElementById('inviteSearch').value = '';
    new bootstrap.Modal(document.getElementById('manageModal')).show();
}
```

Update the Manage button in the leagues card to pass `use_league_odds`:

```blade
<button type="button" class="btn btn-outline-secondary btn-sm"
        onclick="openManageModal({{ $league->id }}, {{ json_encode($league->name) }}, {{ $league->use_league_odds ? 'true' : 'false' }})">
  Valdyti
</button>
```

- [ ] **Step 6: Run all tests**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/LeagueController.php \
        resources/views/leagues/index.blade.php \
        routes/web.php \
        tests/Feature/LeagueOddsTest.php
git commit -m "feat: league admin can toggle use_league_odds from leagues page"
```

---

## Task 4: Final smoke test and push

- [ ] **Step 1: Start the dev server**

```bash
php artisan serve
```

- [ ] **Step 2: Verify end-to-end per-league odds flow**

1. Log in as a league admin with ≥ 20 members (use `php artisan migrate:fresh --seed` with appropriate seed data, or create users via tinker).
2. Visit `/leagues` → open Manage modal → enable "Per-lygos koeficientai" toggle.
3. Save a game result via admin panel → verify `league_game_odds` row is written (`php artisan tinker --execute="dd(DB::table('league_game_odds')->first());"`).
4. Visit the main leaderboard → verify points render without errors.

- [ ] **Step 3: Run full test suite**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 4: Push**

```bash
git push
```

---

## Leagues feature complete

All three plans deliver:

| Plan | Scope | Status |
|---|---|---|
| Plan 1 — Foundation | DB schema, data migration, session/controller rewiring | Done after Plan 1 |
| Plan 2 — Membership UI | `/leagues` hub, navbar switcher, invite flow, create/leave | Done after Plan 2 |
| Plan 3 — Per-league odds | Per-league `league_game_odds` computation, leaderboard recalculation | Done after Plan 3 |
