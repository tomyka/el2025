# Position Trend Card — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a full-width card to the main screen showing the logged-in player's rank position after every scored game, as a smooth SVG line chart with a 4-tile stats strip.

**Architecture:** A new `getRankHistory()` method in `PointController` queries all scored games in order, builds per-user cumulative `point_results` totals in PHP, and returns the logged-in user's rank array. A new Blade partial renders the SVG chart server-side (no JS) using `@php`-computed geometry. `MainController` wires the data to the view.

**Tech Stack:** Laravel 11, PHP 8.2, Blade, inline SVG, custom CSS (`.pt-*`). SQLite for tests.

---

## File map

| File | Change |
|---|---|
| `app/Http/Controllers/PointController.php` | Add `getRankHistory(int $groupID, int $userID): array` |
| `app/Http/Controllers/MainController.php` | Call `getRankHistory()`, pass `$rankHistory` to view |
| `resources/views/main.blade.php` | Add full-width row including `partials.positionTrend` |
| `resources/views/partials/positionTrend.blade.php` | New partial — stats strip + SVG rank chart |
| `public/css/custom.css` | Add `.pt-*` classes |
| `tests/Feature/PointTrendTest.php` | Add 4 tests for `getRankHistory()` |

---

## Task 1: `PointController::getRankHistory()`

**Files:**
- Modify: `app/Http/Controllers/PointController.php` (add after `getAllUsersGameHistory()`)
- Modify: `tests/Feature/PointTrendTest.php` (add test methods)

### Context

`PointController` already has `getAllUsersGameHistory()` which computes game-by-game cumulative totals for the last 10 games. `getRankHistory()` follows the same pattern but covers **all** scored games and returns only the logged-in user's rank array.

`tests/Feature/PointTrendTest.php` already exists with helpers: `makeUser()`, `makeEvent()`, `makeGame()`, `insertResult()`. Add new test methods to this file — do not modify the existing tests.

- [ ] **Step 1: Write the failing tests**

Add these four test methods to the bottom of `tests/Feature/PointTrendTest.php`, before the closing `}`:

```php
public function test_getRankHistory_returns_empty_with_no_scored_games(): void
{
    $group = Group::factory()->create();
    $user  = $this->makeUser($group->id);

    $result = app(\App\Http\Controllers\PointController::class)
        ->getRankHistory($group->id, $user->id);

    $this->assertSame([], $result);
}

public function test_getRankHistory_single_player_always_rank_one(): void
{
    $group = Group::factory()->create();
    $user  = $this->makeUser($group->id);
    $event = $this->makeEvent(1);
    $game  = $this->makeGame($event->id);
    $game->update(['home_team_score' => 1, 'away_team_score' => 0]);
    $this->insertResult($user->id, $game->id, 50.0);

    $result = app(\App\Http\Controllers\PointController::class)
        ->getRankHistory($group->id, $user->id);

    $this->assertSame([1], $result);
}

public function test_getRankHistory_rank_reflects_cumulative_points(): void
{
    $group  = Group::factory()->create();
    $u1     = $this->makeUser($group->id);
    $u2     = $this->makeUser($group->id);
    $event  = $this->makeEvent(1);
    $g1     = $this->makeGame($event->id);
    $g2     = $this->makeGame($event->id);
    $g1->update(['home_team_score' => 1, 'away_team_score' => 0, 'game_date' => now()->subHours(2)]);
    $g2->update(['home_team_score' => 2, 'away_team_score' => 0, 'game_date' => now()->subHour()]);

    // After g1: u2 leads (100 vs 50) → u1 is #2
    $this->insertResult($u1->id, $g1->id, 50.0);
    $this->insertResult($u2->id, $g1->id, 100.0);
    // After g2: u1 overtakes (250 total vs 100) → u1 is #1
    $this->insertResult($u1->id, $g2->id, 200.0);
    $this->insertResult($u2->id, $g2->id, 0.0);

    $result = app(\App\Http\Controllers\PointController::class)
        ->getRankHistory($group->id, $u1->id);

    $this->assertSame([2, 1], $result);
}

public function test_getRankHistory_user_not_in_group_returns_empty(): void
{
    $group   = Group::factory()->create();
    $outsider = User::factory()->create(); // NOT added to group

    $result = app(\App\Http\Controllers\PointController::class)
        ->getRankHistory($group->id, $outsider->id);

    $this->assertSame([], $result);
}
```

- [ ] **Step 2: Run tests to confirm they fail**

```
php artisan test tests/Feature/PointTrendTest.php --filter getRankHistory
```

Expected: 4 failures with `Call to undefined method ... getRankHistory()`.

- [ ] **Step 3: Add `getRankHistory()` to `PointController`**

Add this method after `getAllUsersGameHistory()` in `app/Http/Controllers/PointController.php`:

```php
public function getRankHistory(int $groupID, int $userID): array
{
    $guest = session('guest', 0);

    $userIDs = DB::table('user_groups')
        ->where('group_id', $groupID)
        ->where('guest', '<=', $guest)
        ->pluck('user_id')
        ->toArray();

    if (empty($userIDs) || !in_array($userID, $userIDs)) {
        return [];
    }

    $gameIDs = DB::table('games')
        ->whereNotNull('home_team_score')
        ->orderBy('game_date')
        ->orderBy('id')
        ->pluck('id')
        ->toArray();

    if (empty($gameIDs)) {
        return [];
    }

    $rows = DB::table('point_results')
        ->whereIn('user_id', $userIDs)
        ->whereIn('game_id', $gameIDs)
        ->select('user_id', 'game_id', 'full_points')
        ->get();

    $pointsMap = [];
    foreach ($rows as $row) {
        $pointsMap[$row->user_id][$row->game_id] = (float) $row->full_points;
    }

    $totals = array_fill_keys($userIDs, 0.0);
    $ranks  = [];

    foreach ($gameIDs as $gameID) {
        foreach ($userIDs as $uid) {
            $totals[$uid] += $pointsMap[$uid][$gameID] ?? 0.0;
        }
        $sorted = $totals;
        arsort($sorted);
        $rank = 1;
        foreach ($sorted as $uid => $_) {
            if ($uid === $userID) {
                $ranks[] = $rank;
                break;
            }
            $rank++;
        }
    }

    return $ranks;
}
```

- [ ] **Step 4: Run tests to confirm they pass**

```
php artisan test tests/Feature/PointTrendTest.php --filter getRankHistory
```

Expected: 4 tests, 4 passed.

- [ ] **Step 5: Run full test suite to confirm no regressions**

```
php artisan test
```

Expected: all existing tests still pass.

- [ ] **Step 6: Commit**

```
git add app/Http/Controllers/PointController.php tests/Feature/PointTrendTest.php
git commit -m "feat: add PointController::getRankHistory() with tests"
```

---

## Task 2: Wire `MainController` to supply `$rankHistory`

**Files:**
- Modify: `app/Http/Controllers/MainController.php`

### Context

`MainController::loadApp()` already instantiates `$pointController = new PointController()` and calls `$pointController->getAllUserPoints($groupID)`. Add one call after that. The authenticated branch ends with a `return view('main')->with(...)` — extend that chain with `->with('rankHistory', $rankHistory)`.

- [ ] **Step 1: Add the call and pass the variable**

In `app/Http/Controllers/MainController.php`, inside the `if (isset($user))` branch, after the line:

```php
$points = $pointController->getAllUserPoints($groupID);
```

add:

```php
$rankHistory = $pointController->getRankHistory($groupID, $userID);
```

Then extend the existing `return view('main')->with(...)`  chain to include `->with('rankHistory', $rankHistory)`. The full return statement becomes:

```php
return view('main')
    ->with('messages',               $messages)
    ->with('points',                 $points)
    ->with('predictionGames',        $predictionResultsWithStats)
    ->with('eventDaySurvivalStatus', $eventDaySurvivalStatus)
    ->with('groupDetails',           $feeController->getGroupDetails())
    ->with('userDetails',            $feeController->getUserDetails())
    ->with('fund',                   $feeController->getFund())
    ->with('fundCollected',          $feeController->getFundCollected())
    ->with('standings',              $standings)
    ->with('predictionStandingsPoints', $predictionStandingsPoints)
    ->with('rankHistory',            $rankHistory);
```

- [ ] **Step 2: Verify the app loads without errors**

Visit `http://localhost/` while logged in. No 500 error, page renders normally.

- [ ] **Step 3: Commit**

```
git add app/Http/Controllers/MainController.php
git commit -m "feat: pass rankHistory to main view"
```

---

## Task 3: `positionTrend.blade.php` partial

**Files:**
- Create: `resources/views/partials/positionTrend.blade.php`

### Context

The partial receives `$rankHistory` (array of integers). It computes all SVG geometry in a `@php` block, then emits the card. The card is hidden (not rendered at all) when fewer than 2 data points exist.

The existing `.sb-card` and `.sb-card-title` classes are used for the outer shell, matching all other partials. The `.pt-*` classes (defined in Task 4) handle the inner layout.

`var(--sb-accent)` is the app's blue accent colour (`#3b82f6`). `var(--sb-border)` is the border colour. `var(--sb-muted)` is the muted text colour. These are defined in `public/css/custom.css`.

- [ ] **Step 1: Create the file**

Create `resources/views/partials/positionTrend.blade.php` with this content:

```blade
@if(count($rankHistory) >= 2)
@php
$n        = count($rankHistory);
$curRank  = $rankHistory[$n - 1];
$bestRank = min($rankHistory);
$worstRank = max($rankHistory);
$prevRank = $rankHistory[$n - 2];
$delta    = $prevRank - $curRank;   // positive = improved (rank number fell)

$svgW = max(200, ($n - 1) * 4);
$svgH = 90;

$mapY = function (int $r) use ($worstRank): float {
    return $worstRank > 1
        ? round(8 + (($r - 1) / ($worstRank - 1)) * 64, 1)
        : 40.0;
};

$pts = '';
for ($i = 0; $i < $n; $i++) {
    $x    = $n > 1 ? round(($i / ($n - 1)) * $svgW, 1) : ($svgW / 2);
    $y    = $mapY($rankHistory[$i]);
    $pts .= "{$x},{$y} ";
}
$pts = trim($pts);

$ex = $svgW;
$ey = $mapY($curRank);

$gridRanks = array_unique([1, (int) ceil($worstRank / 2), $worstRank]);
@endphp

<div class="sb-card pt-card">
    <div class="sb-card-title">
        <i class="bi bi-graph-up sb-card-icon"></i> Mano vieta
    </div>

    <div class="pt-stats">
        <div class="pt-stat">
            <div class="pt-stat-val pt-accent">#{{ $curRank }}</div>
            <div class="pt-stat-lbl">Dabar</div>
        </div>
        <div class="pt-stat">
            <div class="pt-stat-val pt-up">#{{ $bestRank }}</div>
            <div class="pt-stat-lbl">Geriausia</div>
        </div>
        <div class="pt-stat">
            <div class="pt-stat-val pt-amber">#{{ $worstRank }}</div>
            <div class="pt-stat-lbl">Blogiausia</div>
        </div>
        <div class="pt-stat">
            <div class="pt-stat-val {{ $delta > 0 ? 'pt-up' : ($delta < 0 ? 'pt-down' : 'pt-neutral') }}">
                @if($delta > 0)▲{{ $delta }}@elseif($delta < 0)▼{{ abs($delta) }}@else&nbsp;—@endif
            </div>
            <div class="pt-stat-lbl">Pokytis</div>
        </div>
    </div>

    <div class="pt-chart-wrap">
        <svg viewBox="0 0 {{ $svgW }} {{ $svgH }}"
             style="width:100%;height:90px;display:block;"
             preserveAspectRatio="none">
            <defs>
                <linearGradient id="ptGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%"   stop-color="#3b82f6" stop-opacity=".2"/>
                    <stop offset="100%" stop-color="#3b82f6" stop-opacity="0"/>
                </linearGradient>
            </defs>

            @foreach($gridRanks as $gr)
            @php $gy = $mapY($gr); @endphp
            <line x1="0" y1="{{ $gy }}" x2="{{ $svgW }}" y2="{{ $gy }}"
                  stroke="#f1f5f9" stroke-width="1"/>
            <text x="3" y="{{ $gy - 2 }}" font-size="7" fill="#cbd5e1">#{{ $gr }}</text>
            @endforeach

            <polygon points="{{ $pts }} {{ $svgW }},{{ $svgH }} 0,{{ $svgH }}"
                     fill="url(#ptGrad)"/>
            <polyline points="{{ $pts }}"
                      fill="none" stroke="#3b82f6" stroke-width="2"
                      stroke-linejoin="round" stroke-linecap="round"/>
            <circle cx="{{ $ex }}" cy="{{ $ey }}" r="5"
                    fill="#3b82f6" stroke="#fff" stroke-width="2"/>

            <text x="1"              y="{{ $svgH - 1 }}" font-size="7" fill="#cbd5e1">1</text>
            <text x="{{ $svgW / 2 }}" y="{{ $svgH - 1 }}" font-size="7" fill="#cbd5e1" text-anchor="middle">{{ (int) ($n / 2) }}</text>
            <text x="{{ $svgW - 1 }}" y="{{ $svgH - 1 }}" font-size="7" fill="#3b82f6"  text-anchor="end">{{ $n }}</text>
        </svg>
        <div class="pt-chart-hint">#1 = viršuje &nbsp;·&nbsp; ↑ geriau</div>
    </div>
</div>
@endif
```

- [ ] **Step 2: Verify the partial has no syntax errors**

```
php artisan view:clear
```

Then visit `http://localhost/` while logged in. The card should appear below the leaderboard (CSS will be added in Task 4, so it may look unstyled — that's expected here).

- [ ] **Step 3: Commit**

```
git add resources/views/partials/positionTrend.blade.php
git commit -m "feat: add positionTrend partial with SVG rank chart"
```

---

## Task 4: CSS + wire into `main.blade.php`

**Files:**
- Modify: `public/css/custom.css` (add `.pt-*` section before the Legacy comment)
- Modify: `resources/views/main.blade.php` (add new full-width row)

### Context

CSS is added in the same pattern as other feature sections in `custom.css` — a named comment block before the `.pt-*` rules. The `main.blade.php` includes the partial in a new Bootstrap row **inside** the `@auth` block, after the existing two-column `<div class="row g-3">` closes.

- [ ] **Step 1: Add CSS**

In `public/css/custom.css`, find the line:

```css
/* ============================================================
   Legacy / Admin utility classes
```

Insert the following block **immediately before** it:

```css
/* ============================================================
   Position trend card (.pt-*)
   ============================================================ */
.pt-stats {
  display: flex;
  border: 1px solid var(--sb-border);
  border-radius: 10px;
  overflow: hidden;
  margin-bottom: 14px;
}
.pt-stat {
  flex: 1;
  text-align: center;
  padding: 10px 6px;
  border-right: 1px solid var(--sb-border);
}
.pt-stat:last-child { border-right: none; }
.pt-stat-val { font-size: 1.4rem; font-weight: 800; line-height: 1; }
.pt-stat-lbl { font-size: .68rem; color: var(--sb-muted); margin-top: 3px; text-transform: uppercase; letter-spacing: .04em; }
.pt-accent  { color: var(--sb-accent); }
.pt-up      { color: #22c55e; }
.pt-down    { color: #ef4444; }
.pt-neutral { color: var(--sb-muted); }
.pt-amber   { color: #f59e0b; }
.pt-chart-wrap  { padding: 0 4px 6px; }
.pt-chart-hint  { font-size: .65rem; color: #cbd5e1; text-align: right; margin-top: 3px; }

```

- [ ] **Step 2: Add the partial include to `main.blade.php`**

In `resources/views/main.blade.php`, find the closing `</div>` of the `<div class="row g-3">` block (it's the `</div>` on the line just before `@else`). Insert a new row after that closing div:

```blade
    @if(isset($rankHistory) && count($rankHistory) >= 2)
    <div class="row mt-3">
        <div class="col-12">
            @include('partials.positionTrend')
        </div>
    </div>
    @endif
```

The `@auth` block should now look like:

```blade
@auth
    <div class="sb-card">
        @include('partials.fee')
        @include('partials.messages')
        @include('partials.warnings')
    </div>

    <div class="row g-3">
        {{-- existing columns unchanged --}}
    </div>

    @if(isset($rankHistory) && count($rankHistory) >= 2)
    <div class="row mt-3">
        <div class="col-12">
            @include('partials.positionTrend')
        </div>
    </div>
    @endif
@else
    @include('welcome')
@endauth
```

- [ ] **Step 3: Verify the card renders correctly**

Visit `http://localhost/` while logged in and at least 2 games have been scored. Confirm:
- The card appears below the leaderboard and upcoming-games cards
- Stats strip shows 4 tiles: current rank (blue), best (green), worst (amber), last-game movement
- SVG line chart fills the full card width
- Card is absent when fewer than 2 games have been scored

- [ ] **Step 4: Run full test suite**

```
php artisan test
```

Expected: all tests pass.

- [ ] **Step 5: Commit**

```
git add public/css/custom.css resources/views/main.blade.php
git commit -m "feat: position trend card on main screen — full tournament rank history"
```
