# Points Trend & Rank Tracker Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add per-round cumulative points trend and rank history to the leaderboard — each row gets a rank-change badge and an expandable SVG chart panel.

**Architecture:** New `PointController::getAllUsersRoundHistory()` computes per-round cumulative totals and ranks for all group users in one pass (avoids N+1). `MainController::loadApp()` calls it once and merges history + rankChange into the existing `$points` array. `points.blade.php` renders Alpine.js expand toggles on each row with a server-side SVG chart and a per-round table.

**Tech Stack:** PHP 8.2 / Laravel 11, Alpine.js (already loaded), inline SVG in Blade, Bootstrap 5, custom.css

---

## File Map

| File | Change |
|---|---|
| `app/Http/Controllers/PointController.php` | Add `getAllUsersRoundHistory(int $groupID): array` |
| `app/Http/Controllers/MainController.php` | Call history method once, merge into `$points` |
| `resources/views/partials/points.blade.php` | Alpine expand, rank badge, SVG trend panel |
| `public/css/custom.css` | Trend panel, badge, legend, round table styles |
| `tests/Feature/PointTrendTest.php` | New — unit tests for `getAllUsersRoundHistory` |

---

### Task 1: `PointController::getAllUsersRoundHistory` + tests

**Files:**
- Modify: `app/Http/Controllers/PointController.php`
- Create: `tests/Feature/PointTrendTest.php`

- [ ] **Step 1: Create `tests/Feature/PointTrendTest.php` with failing tests**

```php
<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\Group;
use App\Models\Team;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PointTrendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        session(['guest' => 0]);
    }

    private function makeUser(int $groupId): User
    {
        $user = User::factory()->create();
        UserGroup::factory()->create(['user_id' => $user->id, 'group_id' => $groupId, 'guest' => 0]);
        return $user;
    }

    private function makeEvent(int $day): Event
    {
        return Event::create(['event' => "R{$day}", 'event_day' => $day, 'event_survival' => 0, 'rate' => 1]);
    }

    private function makeGame(int $eventId): Game
    {
        $home = Team::create(['team' => 'H' . uniqid()]);
        $away = Team::create(['team' => 'A' . uniqid()]);
        return Game::create([
            'game_date'    => now(),
            'event_id'     => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
        ]);
    }

    private function insertResult(int $userId, int $gameId, float $pts): void
    {
        DB::table('point_results')->insert([
            'user_id' => $userId, 'game_id' => $gameId,
            'winner_points' => 0, 'difference_points' => 0, 'bingo_points' => 0,
            'odds' => 1, 'odds_points' => 0, 'full_points' => $pts,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function insertSurvival(int $userId, int $eventId, int $pts): void
    {
        $team = Team::create(['team' => 'S' . uniqid()]);
        DB::table('point_survivals')->insert([
            'user_id' => $userId, 'event_id' => $eventId, 'team_id' => $team->id,
            'survival_points' => $pts, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function insertStanding(int $userId, int $groupPts): void
    {
        $team = Team::create(['team' => 'St' . uniqid()]);
        DB::table('point_standings')->insert([
            'user_id' => $userId, 'team_id' => $team->id,
            'group_position_points' => $groupPts, 'last32_points' => 0,
            'last16_points' => 0, 'quarterfinal_points' => 0,
            'semifinal_points' => 0, 'final_points' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_returns_empty_when_no_scored_games(): void
    {
        $group = Group::factory()->create();
        $user  = $this->makeUser($group->id);

        $result = app(\App\Http\Controllers\PointController::class)
            ->getAllUsersRoundHistory($group->id);

        $this->assertEmpty($result[$user->id] ?? []);
    }

    public function test_returns_one_entry_per_scored_event(): void
    {
        $group = Group::factory()->create();
        $user  = $this->makeUser($group->id);
        $e1    = $this->makeEvent(1);
        $e2    = $this->makeEvent(2);
        $this->insertResult($user->id, $this->makeGame($e1->id)->id, 80.0);
        $this->insertResult($user->id, $this->makeGame($e2->id)->id, 100.0);

        $history = app(\App\Http\Controllers\PointController::class)
            ->getAllUsersRoundHistory($group->id)[$user->id];

        $this->assertCount(2, $history);
        $this->assertEquals(1, $history[0]['event_day']);
        $this->assertEquals(2, $history[1]['event_day']);
    }

    public function test_cumulative_points_accumulate_across_rounds(): void
    {
        $group = Group::factory()->create();
        $user  = $this->makeUser($group->id);
        $e1    = $this->makeEvent(1);
        $e2    = $this->makeEvent(2);
        $this->insertResult($user->id, $this->makeGame($e1->id)->id, 80.0);
        $this->insertResult($user->id, $this->makeGame($e2->id)->id, 100.0);

        $history = app(\App\Http\Controllers\PointController::class)
            ->getAllUsersRoundHistory($group->id)[$user->id];

        $this->assertEquals(80.0,  $history[0]['cumulative_points']);
        $this->assertEquals(180.0, $history[1]['cumulative_points']);
    }

    public function test_round_points_is_delta_per_round(): void
    {
        $group = Group::factory()->create();
        $user  = $this->makeUser($group->id);
        $e1    = $this->makeEvent(1);
        $e2    = $this->makeEvent(2);
        $this->insertResult($user->id, $this->makeGame($e1->id)->id, 80.0);
        $this->insertResult($user->id, $this->makeGame($e2->id)->id, 100.0);

        $history = app(\App\Http\Controllers\PointController::class)
            ->getAllUsersRoundHistory($group->id)[$user->id];

        $this->assertEquals(80.0,  $history[0]['round_points']);
        $this->assertEquals(100.0, $history[1]['round_points']);
    }

    public function test_rank_computed_correctly_across_users(): void
    {
        $group = Group::factory()->create();
        $u1    = $this->makeUser($group->id);
        $u2    = $this->makeUser($group->id);
        $e1    = $this->makeEvent(1);
        $this->insertResult($u1->id, $this->makeGame($e1->id)->id, 80.0);
        $this->insertResult($u2->id, $this->makeGame($e1->id)->id, 120.0);

        $result = app(\App\Http\Controllers\PointController::class)
            ->getAllUsersRoundHistory($group->id);

        $this->assertEquals(2, $result[$u1->id][0]['rank']);
        $this->assertEquals(1, $result[$u2->id][0]['rank']);
    }

    public function test_standing_points_included_in_cumulative(): void
    {
        $group = Group::factory()->create();
        $user  = $this->makeUser($group->id);
        $e1    = $this->makeEvent(1);
        $this->insertResult($user->id, $this->makeGame($e1->id)->id, 80.0);
        $this->insertStanding($user->id, 100);

        $history = app(\App\Http\Controllers\PointController::class)
            ->getAllUsersRoundHistory($group->id)[$user->id];

        $this->assertEquals(180.0, $history[0]['cumulative_points']);
    }

    public function test_standing_points_excluded_from_round_points(): void
    {
        $group = Group::factory()->create();
        $user  = $this->makeUser($group->id);
        $e1    = $this->makeEvent(1);
        $e2    = $this->makeEvent(2);
        $this->insertResult($user->id, $this->makeGame($e1->id)->id, 80.0);
        $this->insertResult($user->id, $this->makeGame($e2->id)->id, 100.0);
        $this->insertStanding($user->id, 100);

        $history = app(\App\Http\Controllers\PointController::class)
            ->getAllUsersRoundHistory($group->id)[$user->id];

        $this->assertEquals(80.0,  $history[0]['round_points']);
        $this->assertEquals(100.0, $history[1]['round_points']);
    }

    public function test_survival_points_included_in_round_and_cumulative(): void
    {
        $group = Group::factory()->create();
        $user  = $this->makeUser($group->id);
        $e1    = $this->makeEvent(1);
        $this->insertResult($user->id, $this->makeGame($e1->id)->id, 80.0);
        $this->insertSurvival($user->id, $e1->id, 50);

        $history = app(\App\Http\Controllers\PointController::class)
            ->getAllUsersRoundHistory($group->id)[$user->id];

        $this->assertEquals(130.0, $history[0]['cumulative_points']);
        $this->assertEquals(130.0, $history[0]['round_points']);
    }
}
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
php artisan test tests/Feature/PointTrendTest.php
```

Expected: FAIL — `Call to undefined method App\Http\Controllers\PointController::getAllUsersRoundHistory()`

- [ ] **Step 3: Add `getAllUsersRoundHistory` to `PointController`**

In `app/Http/Controllers/PointController.php`, add this method before the closing `}` of the class:

```php
public function getAllUsersRoundHistory(int $groupID): array
{
    $userIDs = DB::table('users')
        ->join('user_groups', 'users.id', '=', 'user_groups.user_id')
        ->where('user_groups.group_id', '=', $groupID)
        ->where('user_groups.guest', '<=', session('guest'))
        ->pluck('users.id')
        ->toArray();

    if (empty($userIDs)) {
        return [];
    }

    $events = DB::table('events')
        ->join('games', 'games.event_id', '=', 'events.id')
        ->join('point_results', 'point_results.game_id', '=', 'games.id')
        ->whereIn('point_results.user_id', $userIDs)
        ->select('events.id', 'events.event_day')
        ->groupBy('events.id', 'events.event_day')
        ->orderBy('events.event_day')
        ->get();

    if ($events->isEmpty()) {
        return [];
    }

    $standingTotals = DB::table('point_standings')
        ->whereIn('user_id', $userIDs)
        ->selectRaw('user_id, SUM(
            IFNULL(group_position_points,0) + IFNULL(last32_points,0)
            + IFNULL(last16_points,0) + IFNULL(quarterfinal_points,0)
            + IFNULL(semifinal_points,0) + IFNULL(final_points,0)
        ) as total_points')
        ->groupBy('user_id')
        ->get()
        ->keyBy('user_id');

    $history         = [];
    $prevResultsSurv = array_fill_keys($userIDs, 0.0);

    foreach ($events as $event) {
        $resultPts = DB::table('point_results')
            ->join('games', 'games.id', '=', 'point_results.game_id')
            ->join('events', 'events.id', '=', 'games.event_id')
            ->whereIn('point_results.user_id', $userIDs)
            ->where('events.event_day', '<=', $event->event_day)
            ->selectRaw('point_results.user_id, SUM(point_results.full_points) as total')
            ->groupBy('point_results.user_id')
            ->get()->keyBy('user_id');

        $survPts = DB::table('point_survivals')
            ->join('events', 'events.id', '=', 'point_survivals.event_id')
            ->whereIn('point_survivals.user_id', $userIDs)
            ->where('events.event_day', '<=', $event->event_day)
            ->selectRaw('point_survivals.user_id, SUM(point_survivals.survival_points) as total')
            ->groupBy('point_survivals.user_id')
            ->get()->keyBy('user_id');

        $totals    = [];
        $rsPtsMap  = [];
        foreach ($userIDs as $uid) {
            $rPts          = (float) ($resultPts->get($uid)?->total         ?? 0);
            $sPts          = (float) ($survPts->get($uid)?->total           ?? 0);
            $stPts         = (float) ($standingTotals->get($uid)?->total_points ?? 0);
            $rsPtsMap[$uid] = $rPts + $sPts;
            $totals[$uid]   = round($rsPtsMap[$uid] + $stPts, 1);
        }

        arsort($totals);
        $ranks = [];
        $rank  = 1;
        foreach ($totals as $uid => $_) {
            $ranks[$uid] = $rank++;
        }

        foreach ($userIDs as $uid) {
            $roundPoints      = round($rsPtsMap[$uid] - $prevResultsSurv[$uid], 1);
            $history[$uid][]  = [
                'event_day'         => $event->event_day,
                'round_points'      => $roundPoints,
                'cumulative_points' => $totals[$uid],
                'rank'              => $ranks[$uid],
            ];
            $prevResultsSurv[$uid] = $rsPtsMap[$uid];
        }
    }

    return $history;
}
```

- [ ] **Step 4: Run tests to confirm they pass**

```bash
php artisan test tests/Feature/PointTrendTest.php
```

Expected: 8 tests, PASS

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/PointTrendTest.php app/Http/Controllers/PointController.php
git commit -m "feat: add getAllUsersRoundHistory to PointController"
```

---

### Task 2: Integrate history into `MainController::loadApp()`

**Files:**
- Modify: `app/Http/Controllers/MainController.php`

- [ ] **Step 1: Add history merge after `getAllUserPoints` call**

In `MainController.php`, locate the line:

```php
$points = $pointController->getAllUserPoints($groupID);
```

Add these lines immediately after it:

```php
$roundHistory = $pointController->getAllUsersRoundHistory($groupID);
foreach ($points as $i => &$point) {
    $point['roundHistory'] = $roundHistory[$point['userID']] ?? [];
    $lastRound             = end($point['roundHistory']) ?: null;
    $prevRank              = $lastRound ? $lastRound['rank'] : null;
    $point['rankChange']   = $prevRank !== null ? $prevRank - ($i + 1) : null;
}
unset($point);
```

- [ ] **Step 2: Run full test suite**

```bash
php artisan test
```

Expected: all existing tests pass.

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/MainController.php
git commit -m "feat: merge round history and rank change into leaderboard data"
```

---

### Task 3: CSS styles for trend panel

**Files:**
- Modify: `public/css/custom.css`

- [ ] **Step 1: Remove the old `lb-row:last-child` rule**

In `public/css/custom.css` at line 843, remove:

```css
.lb-row:last-child { border-bottom: none; }
```

- [ ] **Step 2: Append trend panel styles to the end of `custom.css`**

```css
/* ============================================================
   Leaderboard trend panel
   ============================================================ */
.lb-entry:last-child .lb-row { border-bottom: none; }

.lb-row-expandable { cursor: pointer; }
.lb-row-expandable:hover { background: #f8faff; }
.lb-me-row.lb-row-expandable:hover { background: #dbeafe; }

.lb-trend-badge {
  font-size: .7rem;
  font-weight: 600;
  min-width: 34px;
  text-align: center;
  flex-shrink: 0;
}
.lb-trend-up      { color: #16a34a; }
.lb-trend-down    { color: #dc2626; }
.lb-trend-neutral { color: var(--sb-muted); }

.lb-trend-chevron {
  font-size: .7rem;
  color: var(--sb-muted);
  flex-shrink: 0;
  width: 12px;
  text-align: center;
}

.lb-trend-panel {
  padding: 10px 0 6px;
  border-bottom: 1px solid #f1f5f9;
}

.lb-trend-panel-inner {
  display: flex;
  gap: 14px;
  align-items: flex-start;
}

.lb-trend-chart { flex: 2; min-width: 0; }

.lb-trend-chart-label {
  font-size: .65rem;
  color: var(--sb-muted);
  margin-bottom: 4px;
}

.lb-trend-chart svg { width: 100%; height: auto; display: block; }

.lb-trend-legend {
  display: flex;
  gap: 10px;
  font-size: .6rem;
  margin-top: 3px;
}
.lb-trend-legend-pts  { color: var(--sb-accent); }
.lb-trend-legend-rank { color: var(--sb-gold); }

.lb-trend-table {
  flex: 1;
  border-left: 1px solid #e2e8f0;
  padding-left: 12px;
  min-width: 110px;
}

.lb-trend-table-header {
  display: flex;
  justify-content: space-between;
  font-size: .65rem;
  color: var(--sb-muted);
  margin-bottom: 4px;
  font-weight: 600;
}

.lb-trend-row {
  display: flex;
  justify-content: space-between;
  font-size: .68rem;
  padding: 2px 0;
  gap: 4px;
}

.lb-trend-rnd  { color: var(--sb-muted); width: 24px; }
.lb-trend-rpts { color: var(--sb-text); }
.lb-trend-rank { color: var(--sb-text); text-align: right; }
.lb-trend-rank-up   { color: #16a34a; font-weight: 600; }
.lb-trend-rank-down { color: #dc2626; font-weight: 600; }
```

- [ ] **Step 3: Commit**

```bash
git add public/css/custom.css
git commit -m "feat: add trend panel CSS styles"
```

---

### Task 4: Update `points.blade.php` with expand toggle and trend panel

**Files:**
- Modify: `resources/views/partials/points.blade.php`

- [ ] **Step 1: Replace the entire file contents**

Replace `resources/views/partials/points.blade.php` with:

```blade
<div class="sb-card">
    <div class="sb-card-title"><i class="bi bi-trophy-fill sb-card-icon"></i> Taškų lentelė</div>
    @php
        $feeRequired = isset($groupDetails) && $groupDetails->fee > 0;
    @endphp
    @foreach($points as $point)
    @php
        $total     = $point['userGamePoints'] + $point['standingPoints']->total_points + $point['survivalPoints'];
        $rank      = $loop->iteration;
        $isMe      = session('userID') == $point['userID'];
        $fullName  = trim($point['name'] . ' ' . $point['surname']);
        $feeHtml   = '';
        if ($feeRequired) {
            $feeHtml = $point['userFee'] > 0
                ? '<div class="text-success" style="font-size:.78rem">&#10003; Mokestis sumokėtas</div>'
                : '<div class="text-danger" style="font-size:.78rem">&#10007; Mokestis nesumokėtas</div>';
        }
        $breakdown = 'R: ' . number_format($point['userGamePoints'], 1) . ' &nbsp;·&nbsp; E: ' . number_format($point['standingPoints']->total_points, 1);
        if (session('survivalGame') == 1) {
            $breakdown .= ' &nbsp;·&nbsp; I: ' . $point['survivalPoints'];
        }
        $breakdown .= ' &nbsp;·&nbsp; Avg: ' . number_format($point['averagePoints'], 1);
        if ($point['userGameBingo'] > 0) {
            $breakdown .= ' &nbsp;·&nbsp; &#127919; ' . $point['userGameBingo'];
        }
        $popoverContent = '<div style="font-size:.8rem">'
            . ($fullName ? '<strong>' . e($fullName) . '</strong>' : '')
            . $feeHtml
            . '<div class="text-muted mt-1">' . $breakdown . '</div>'
            . '</div>';

        $hasHistory = !empty($point['roundHistory']);
        $rankChange = $point['rankChange'] ?? null;
        if ($rankChange === null || !$hasHistory) {
            $badgeClass = 'lb-trend-neutral'; $badgeText = '—';
        } elseif ($rankChange > 0) {
            $badgeClass = 'lb-trend-up';   $badgeText = '▲ ' . $rankChange;
        } elseif ($rankChange < 0) {
            $badgeClass = 'lb-trend-down'; $badgeText = '▼ ' . abs($rankChange);
        } else {
            $badgeClass = 'lb-trend-neutral'; $badgeText = '—';
        }
    @endphp
    <div class="lb-entry" x-data="{ open: false }">
        <div class="lb-row {{ $isMe ? 'lb-me-row' : '' }} {{ $hasHistory ? 'lb-row-expandable' : '' }}"
             @if($hasHistory) x-on:click="open = !open" @endif>
            <div class="lb-rank {{ $rank <= 3 ? 'lb-rank-' . $rank : 'lb-rank-n' }}">{{ $rank }}</div>
            <div class="lb-name {{ $isMe ? 'lb-me-name' : '' }}">
                <span class="lb-name-btn"
                      tabindex="0"
                      data-bs-toggle="popover"
                      data-bs-trigger="click"
                      data-bs-html="true"
                      data-bs-title="{{ $fullName ?: $point['username'] }}"
                      data-bs-content="{{ $popoverContent }}"
                      x-on:click.stop>{{ $point['username'] }}</span>
            </div>
            <span class="lb-trend-badge {{ $badgeClass }}">{{ $badgeText }}</span>
            <div class="lb-total {{ $isMe ? 'lb-me-total' : '' }}">{{ number_format($total, 1) }}</div>
            @if($hasHistory)
                <span class="lb-trend-chevron" x-text="open ? '▾' : '▸'"></span>
            @endif
        </div>

        @if($hasHistory)
        <div x-show="open" x-transition.duration.150ms class="lb-trend-panel" style="display:none">
            @php
                $rounds  = $point['roundHistory'];
                $n       = count($rounds);
                $svgW    = max(120, ($n - 1) * 60);
                $maxCum  = max(max(array_column($rounds, 'cumulative_points')), 1);
                $maxRank = max(max(array_column($rounds, 'rank')), 1);

                $ptsPoly = '';
                $rnkPoly = '';
                $dots    = [];
                $lbls    = [];

                foreach ($rounds as $i => $r) {
                    $x        = $n > 1 ? round(($i / ($n - 1)) * $svgW, 1) : $svgW / 2;
                    $yPts     = round(10 + (1 - $r['cumulative_points'] / $maxCum) * 60, 1);
                    $yRnk     = $maxRank > 1 ? round(10 + (($r['rank'] - 1) / ($maxRank - 1)) * 60, 1) : 10.0;
                    $ptsPoly .= "{$x},{$yPts} ";
                    $rnkPoly .= "{$x},{$yRnk} ";
                    $dots[]   = ['x' => $x, 'y' => $yPts, 'last' => $i === $n - 1];
                    $lbls[]   = ['x' => $x, 'label' => 'R' . $r['event_day']];
                }
            @endphp
            <div class="lb-trend-panel-inner">
                <div class="lb-trend-chart">
                    <div class="lb-trend-chart-label">Taškai ir vieta per turus</div>
                    <svg viewBox="0 0 {{ $svgW }} 90" style="width:100%;height:90px">
                        <line x1="0" y1="80" x2="{{ $svgW }}" y2="80" stroke="#e2e8f0" stroke-width="0.5"/>
                        <line x1="0" y1="55" x2="{{ $svgW }}" y2="55" stroke="#e2e8f0" stroke-width="0.5" stroke-dasharray="3,3"/>
                        <line x1="0" y1="30" x2="{{ $svgW }}" y2="30" stroke="#e2e8f0" stroke-width="0.5" stroke-dasharray="3,3"/>
                        @if($n > 1)
                        <polyline points="{{ trim($ptsPoly) }}" fill="none" stroke="#2563eb" stroke-width="2" stroke-linejoin="round"/>
                        <polyline points="{{ trim($rnkPoly) }}" fill="none" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="4,2" stroke-linejoin="round"/>
                        @endif
                        @foreach($dots as $dot)
                        <circle cx="{{ $dot['x'] }}" cy="{{ $dot['y'] }}"
                                r="{{ $dot['last'] ? 4 : 3 }}" fill="#2563eb"
                                @if($dot['last']) stroke="#fff" stroke-width="1.5" @endif/>
                        @endforeach
                        @foreach($lbls as $lbl)
                        <text x="{{ $lbl['x'] }}" y="89" font-size="8" fill="#94a3b8" text-anchor="middle">{{ $lbl['label'] }}</text>
                        @endforeach
                    </svg>
                    <div class="lb-trend-legend">
                        <span class="lb-trend-legend-pts">— taškai</span>
                        <span class="lb-trend-legend-rank">-- vieta</span>
                    </div>
                </div>
                <div class="lb-trend-table">
                    <div class="lb-trend-table-header">
                        <span>Turas</span><span>+Tšk</span><span>Vieta</span>
                    </div>
                    @foreach($rounds as $idx => $r)
                    @php
                        $prev   = $idx > 0 ? $rounds[$idx - 1]['rank'] : null;
                        $rDir   = $prev !== null ? $prev - $r['rank'] : 0;
                        $rCls   = $rDir > 0 ? 'lb-trend-rank-up' : ($rDir < 0 ? 'lb-trend-rank-down' : '');
                        $rArrow = $rDir > 0 ? ' ▲' : ($rDir < 0 ? ' ▼' : '');
                    @endphp
                    <div class="lb-trend-row">
                        <span class="lb-trend-rnd">R{{ $r['event_day'] }}</span>
                        <span class="lb-trend-rpts">+{{ number_format($r['round_points'], 1) }}</span>
                        <span class="lb-trend-rank {{ $rCls }}">#{{ $r['rank'] }}{{ $rArrow }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
    @endforeach
</div>
```

- [ ] **Step 2: Run full test suite**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 3: Commit**

```bash
git add resources/views/partials/points.blade.php
git commit -m "feat: add expandable trend panel to leaderboard rows"
```

---

### Task 5: Verify in browser

- [ ] **Step 1: Start the dev server**

```bash
npm run dev &
php artisan serve
```

- [ ] **Step 2: Open the app and log in**

Navigate to `http://localhost:8000`. Log in as a user in a group that has scored games (i.e., `point_results` rows exist).

- [ ] **Step 3: Verify rank-change badges**

Each leaderboard row shows a `▲ N` (green), `▼ N` (red), or `—` (grey) badge between the username and total points.

- [ ] **Step 4: Verify expand toggle**

Click anywhere on a leaderboard row (not on the username). The trend panel slides open below the row, showing the SVG chart on the left and the round table on the right. Click again to collapse. Chevron (`▸`/`▾`) flips accordingly.

- [ ] **Step 5: Verify popover still works**

Click the dotted underlined username text. The Bootstrap popover opens with the points breakdown. The row does NOT expand when clicking the name.

- [ ] **Step 6: Verify edge case — no history**

For a group with no scored games: badges show `—`, rows are not clickable (no cursor change, no chevron).
