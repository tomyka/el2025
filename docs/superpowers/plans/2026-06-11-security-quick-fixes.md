# Security Quick Fixes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Patch three concrete security issues — XSS in the chart view, missing rate limiting on auth routes, and raw SQL concatenation in streak recalculation.

**Architecture:** All three fixes are surgical single-file changes with no new abstractions needed. Tests are added to existing feature/unit test files. Each task is independently committable.

**Tech Stack:** Laravel 11, PHP 8.2+, PHPUnit, SQLite (tests), MySQL (production)

---

## File Map

| File | Change |
|---|---|
| `app/Http/Controllers/ChartController.php` | Add `JSON_HEX_*` flags to both `json_encode()` calls (lines 93–94) |
| `routes/auth.php` | Add `throttle` middleware to register, forgot-password, reset-password routes |
| `app/Http/Controllers/PointResultController.php` | Replace SQL string concatenation with `?` bindings in `recalculateStreaks()` (lines 97–104) |
| `tests/Feature/ChartXssTest.php` | New test file — verifies HTML chars are escaped in chart JSON output |
| `tests/Feature/RegistrationDeadlineTest.php` | Add rate-limit test — asserts HTTP 429 after 3 rapid POST /register requests |

---

## Task 1: Fix XSS in ChartController

**Files:**
- Modify: `app/Http/Controllers/ChartController.php:93-94`
- Create: `tests/Feature/ChartXssTest.php`

The chart view uses `{!! $gameLabels !!}` and `{!! $datasets !!}` — unescaped Blade. `json_encode()` without flags passes `<`, `>`, `"`, `'`, `&` through raw. A username like `</script><script>alert(1)` would execute JS in every viewer's browser. Fix: add `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` to both `json_encode()` calls. These flags encode `<` → `<` etc., making inline JS safe regardless of input content.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ChartXssTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\League;
use App\Models\LeagueMember;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChartXssTest extends TestCase
{
    use RefreshDatabase;

    public function test_chart_escapes_html_in_username(): void
    {
        $user = User::factory()->create(['username' => '</script><script>alert(1)']);
        $league = League::factory()->create();
        LeagueMember::factory()->create(['user_id' => $user->id, 'league_id' => $league->id]);

        $event    = Event::create(['event' => 'T', 'event_day' => 1, 'event_survival' => 0, 'active' => 1, 'rate' => 1]);
        $homeTeam = Team::create(['team' => 'Home']);
        $awayTeam = Team::create(['team' => 'Away']);
        Game::create([
            'event_id'        => $event->id,
            'home_team_id'    => $homeTeam->id,
            'away_team_id'    => $awayTeam->id,
            'game_date'       => now()->subHour(),
            'home_team_score' => 1,
            'away_team_score' => 0,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['leagueID' => $league->id, 'guest' => 0])
            ->get(route('summary.chart'));

        $response->assertOk();
        // Raw angle brackets must NOT appear inside the JS block
        $this->assertStringNotContainsString('</script><script>', $response->getContent());
        // Unicode escapes MUST appear instead
        $this->assertStringContainsString('<', $response->getContent());
    }

    public function test_chart_escapes_html_in_team_names(): void
    {
        $user = User::factory()->create(['username' => 'normal']);
        $league = League::factory()->create();
        LeagueMember::factory()->create(['user_id' => $user->id, 'league_id' => $league->id]);

        $event    = Event::create(['event' => 'T', 'event_day' => 1, 'event_survival' => 0, 'active' => 1, 'rate' => 1]);
        $homeTeam = Team::create(['team' => '<b>Bold</b>']);
        $awayTeam = Team::create(['team' => 'Away']);
        Game::create([
            'event_id'        => $event->id,
            'home_team_id'    => $homeTeam->id,
            'away_team_id'    => $awayTeam->id,
            'game_date'       => now()->subHour(),
            'home_team_score' => 1,
            'away_team_score' => 0,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['leagueID' => $league->id, 'guest' => 0])
            ->get(route('summary.chart'));

        $response->assertOk();
        $this->assertStringNotContainsString('<b>Bold</b>', $response->getContent());
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test tests/Feature/ChartXssTest.php
```

Expected: both tests fail — `assertStringNotContainsString` fails because raw `</script>` is present in the response.

- [ ] **Step 3: Fix ChartController.php**

In `app/Http/Controllers/ChartController.php`, replace lines 93–94:

```php
// Before:
        return view('summary.chart')
            ->with('datasets',   json_encode($datasets))
            ->with('gameLabels', json_encode($gameLabels))
            ->with('gameCount',  count($games));

// After:
        $jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

        return view('summary.chart')
            ->with('datasets',   json_encode($datasets,   $jsonFlags))
            ->with('gameLabels', json_encode($gameLabels, $jsonFlags))
            ->with('gameCount',  count($games));
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
php artisan test tests/Feature/ChartXssTest.php
```

Expected: both tests pass.

- [ ] **Step 5: Run full suite to check for regressions**

```bash
php artisan test
```

Expected: all tests pass (previously passing count + 2 new).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/ChartController.php tests/Feature/ChartXssTest.php
git commit -m "fix: escape HTML chars in chart JSON output to prevent XSS"
```

---

## Task 2: Rate limiting on auth routes

**Files:**
- Modify: `routes/auth.php:16-19, 29-30, 35-36`
- Modify: `tests/Feature/RegistrationDeadlineTest.php` — add rate-limit assertion

Laravel's built-in `throttle:N,M` middleware (N requests per M minutes per IP) returns HTTP 429 with a `Retry-After` header when the limit is exceeded. It uses the cache driver, not the database, so no migration is needed.

Limits applied:
- `GET /register` → `throttle:10,1` (stops page-scraping bots)
- `POST /register` → `throttle:3,1` (3 submissions per minute per IP)
- `POST /forgot-password` → `throttle:5,1` (prevents email-spam abuse)
- `POST /reset-password` → `throttle:5,1` (prevents token brute-force)

- [ ] **Step 1: Write the failing test**

Add this test to `tests/Feature/RegistrationDeadlineTest.php`, inside the class after the existing `makeGame()` helper:

```php
public function test_registration_rate_limited_after_three_attempts(): void
{
    // Make 3 valid-looking (but failing — duplicate email) registration attempts
    for ($i = 0; $i < 3; $i++) {
        $this->post(route('register'), [
            'username'              => 'bot' . $i,
            'name'                  => 'Bot',
            'email'                 => 'bot' . $i . '@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);
    }

    // 4th attempt must be throttled
    $response = $this->post(route('register'), [
        'username'              => 'bot4',
        'name'                  => 'Bot',
        'email'                 => 'bot4@test.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(429);
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Feature/RegistrationDeadlineTest.php --filter test_registration_rate_limited_after_three_attempts
```

Expected: FAIL — response is 302 (redirect), not 429, because no throttle exists yet.

- [ ] **Step 3: Add throttle middleware to routes/auth.php**

Replace the `Route::middleware('guest')->group(...)` block in `routes/auth.php` (lines 15–40):

```php
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
                ->middleware('throttle:10,1')
                ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store'])
                ->middleware('throttle:3,1');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
                ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
                ->middleware('throttle:5,1')
                ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
                ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
                ->middleware('throttle:5,1')
                ->name('password.store');

    Route::get('auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test tests/Feature/RegistrationDeadlineTest.php --filter test_registration_rate_limited_after_three_attempts
```

Expected: PASS — 4th request returns HTTP 429.

- [ ] **Step 5: Run full suite to check for regressions**

```bash
php artisan test
```

Expected: all tests pass. Note: if other registration tests fail with 429, they're running too close together. Fix by adding `$this->flushSession()` or clearing the cache between tests in `RegistrationDeadlineTest::setUp()`:

```php
protected function setUp(): void
{
    parent::setUp();
    \Illuminate\Support\Facades\Cache::flush();
}
```

- [ ] **Step 6: Commit**

```bash
git add routes/auth.php tests/Feature/RegistrationDeadlineTest.php
git commit -m "fix: add throttle middleware to registration and password-reset routes"
```

---

## Task 3: Parameterize SQL in recalculateStreaks()

**Files:**
- Modify: `app/Http/Controllers/PointResultController.php:96-104`

The current code builds a `CASE id WHEN x THEN y` statement by concatenating `$id` (int) and `$bonus` (float) directly into the SQL string. Values come from a prior DB query so actual injection risk is near zero — but the pattern violates parameterized-query discipline and would be dangerous if the value source ever changed.

Fix: build the same statement using `WHEN ? THEN ?` placeholders and pass a flat bindings array. Behaviour is identical; all values go through PDO's prepared-statement path.

- [ ] **Step 1: Confirm existing streak tests pass (baseline)**

```bash
php artisan test tests/Feature/StreakBonusTest.php
```

Expected: all 8 tests pass. This is the regression baseline — the fix must not change any observable behaviour.

- [ ] **Step 2: Replace the concatenation block in PointResultController.php**

In `app/Http/Controllers/PointResultController.php`, replace lines 96–104:

```php
        // Before:
        // Single batch update via CASE WHEN
        $cases  = '';
        $ids    = [];
        foreach ($updates as $id => $bonus) {
            $cases .= " WHEN {$id} THEN {$bonus}";
            $ids[]  = $id;
        }
        $idList = implode(',', $ids);
        DB::statement("UPDATE point_results SET streak_bonus = CASE id {$cases} END WHERE id IN ({$idList})");

        // After:
        // Single batch update via CASE WHEN — fully parameterized
        $whenClauses    = implode(' ', array_fill(0, count($updates), 'WHEN ? THEN ?'));
        $inPlaceholders = implode(',',  array_fill(0, count($updates), '?'));

        $bindings = [];
        foreach ($updates as $id => $bonus) {
            $bindings[] = (int)   $id;
            $bindings[] = (float) $bonus;
        }
        $ids = array_keys($updates);
        foreach ($ids as $id) {
            $bindings[] = (int) $id;
        }

        DB::statement(
            "UPDATE point_results SET streak_bonus = CASE id {$whenClauses} END WHERE id IN ({$inPlaceholders})",
            $bindings
        );
```

- [ ] **Step 3: Run streak tests to confirm behaviour unchanged**

```bash
php artisan test tests/Feature/StreakBonusTest.php
```

Expected: all 8 tests still pass — output identical, only parameterization changed.

- [ ] **Step 4: Run full suite**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/PointResultController.php
git commit -m "fix: parameterize CASE WHEN SQL in recalculateStreaks to remove concatenation"
```

---

## Final verification

- [ ] **Run full test suite one last time**

```bash
php artisan test
```

Expected: all tests pass with 3 new tests added (2 XSS + 1 rate-limit).

- [ ] **Push**

```bash
git push
```
