# UX Redesign — Navigation, Dashboard Layout & Engagement Design

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the two-tier navigation with a single slim dark navbar, restructure the main dashboard into a 60/40 two-column layout, and add three engagement features: a personal snapshot card, a tournament progress bar, and a live activity feed.

**Architecture:** Five independent UI changes applied to the existing Laravel/Blade stack. No new routes or models required — new controller methods are added inline to `MainController` and a new `ActivityFeedController`. All data already exists in `point_results`, `games`, `events`, and `league_members`.

**Tech Stack:** Laravel 11, Blade, Bootstrap 5.0.1, Alpine.js, custom CSS (public/css/custom.css)

---

## Current structure (for reference)

- `resources/views/partials/header.blade.php` — two-tier nav: white `.sb-top-bar` + blue `.sb-blue-bar`
- `resources/views/main.blade.php` — `row g-3` with equal `col-lg-6` left (games) + `col-lg-6` right (tabbed: leaderboard / standings)
- `app/Http/Controllers/MainController.php` — `loadApp()` passes `$points`, `$predictionResultsWithStats`, `$standings`, `$predictionStandingsPoints` to the view

---

## Task 1 — Slim dark navbar

**Files:**
- Modify: `resources/views/partials/header.blade.php`
- Modify: `public/css/custom.css` (navbar section, search for `sb-top-bar`, `sb-blue-bar`)

**Design:**

Replace the two-bar structure with a single `<div class="sb-topnav">` inside the existing `<nav class="sb-navbar">`:

```
[⚽ SportBet] [Pradžia] [Spėjimai] [Eiga] [Išlikimas?] [sep] [Suvestinė▾] [Informacija▾] [Lygos] ···· [Draugai▾] [TK avatar]
```

- Background: `#0f172a`
- Height: `48px`
- Logo: same as current (img + text), white text
- Nav links: `color: #94a3b8`, `font-size: .8rem`, hover → `#e2e8f0`
- Active link: `color: #fff`, `border-bottom: 2px solid #3b82f6`, `padding-bottom: calc(14px - 2px)` so it touches the bottom edge
- Separator `<span class="sb-nav-sep">`: vertical `rgba(255,255,255,.15)` line, same as current but visible on dark bg
- League switcher (right): small pill button, `background: rgba(255,255,255,.07)`, white text
- Avatar (right): circular, `background: #1d4ed8`, initials, same dropdown as current

**Mobile:**
- Hamburger button: white icon, same collapse behavior
- Collapse panel: same dark background (`#0f172a`), existing group/link structure unchanged
- Bottom nav: no changes

**CSS removals:** Delete or comment out `.sb-top-bar`, `.sb-top-bar-inner`, `.sb-blue-bar`, `.sb-blue-bar-inner` blocks. Add `.sb-topnav` block with the new single-bar styles.

---

## Task 2 — Tournament progress bar

**Files:**
- Create: `resources/views/partials/progress-bar.blade.php`
- Modify: `app/Http/Controllers/MainController.php` — add `getTournamentProgress()` private method, call it in `loadApp()`, pass result as `$tournamentProgress`
- Modify: `resources/views/main.blade.php` — include partial just inside `@auth` block, before the column grid

**Data shape** (returned by `getTournamentProgress()`):

```php
[
  'event_name'    => 'Grupių etapas',   // e.event
  'total_games'   => 36,                // COUNT(g.id) in that event
  'scored_games'  => 24,                // games with scores
  'today_games'   => 2,                 // scored=null AND date=today
  'pct'           => 67,                // (scored / total) * 100
]
// Returns null when no active event (all games scored, next not seeded yet)
```

**Query** (inside `getTournamentProgress()`):

```php
private function getTournamentProgress(): ?array
{
    $eventID = session('eventID');
    if (!$eventID) return null;

    $event = DB::table('events')->where('id',
        DB::table('games')->where('id', $eventID)->value('event_id')
    )->first();
    if (!$event) return null;

    $total  = DB::table('games')->where('event_id', $event->id)->count();
    $scored = DB::table('games')->where('event_id', $event->id)
                ->whereNotNull('home_team_score')->count();
    $today  = DB::table('games')->where('event_id', $event->id)
                ->whereNull('home_team_score')
                ->whereDate('game_date', now()->toDateString())->count();

    return [
        'event_name'   => $event->event,
        'total_games'  => $total,
        'scored_games' => $scored,
        'today_games'  => $today,
        'pct'          => $total > 0 ? (int) round($scored / $total * 100) : 0,
    ];
}
```

**View** (`progress-bar.blade.php`):

```blade
@if($tournamentProgress)
<div class="sb-progress-strip">
  <span class="sb-progress-label">{{ $tournamentProgress['event_name'] }}</span>
  <div class="sb-progress-bar-wrap">
    <div class="sb-progress-bar-fill" style="width:{{ $tournamentProgress['pct'] }}%"></div>
  </div>
  <span class="sb-progress-count">{{ $tournamentProgress['scored_games'] }} / {{ $tournamentProgress['total_games'] }}</span>
  @if($tournamentProgress['today_games'] > 0)
  <span class="sb-progress-today">⏱ {{ $tournamentProgress['today_games'] }} šiandien</span>
  @endif
</div>
@endif
```

**CSS:**

```css
.sb-progress-strip {
  background: #fff;
  border-bottom: 1px solid var(--sb-border);
  padding: 7px 20px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: .75rem;
}
.sb-progress-label { font-weight: 700; color: var(--sb-accent); white-space: nowrap; }
.sb-progress-bar-wrap { flex: 1; height: 6px; background: #e2e8f0; border-radius: 99px; overflow: hidden; }
.sb-progress-bar-fill { height: 100%; background: linear-gradient(90deg, var(--sb-accent), #3b82f6); border-radius: 99px; transition: width .4s ease; }
.sb-progress-count { color: var(--sb-muted); white-space: nowrap; }
.sb-progress-today { background: #f59e0b; color: #fff; font-size: .68rem; font-weight: 700; border-radius: 4px; padding: 2px 7px; white-space: nowrap; }
```

---

## Task 3 — Two-column dashboard layout

**Files:**
- Modify: `resources/views/main.blade.php`

**New layout structure (auth block):**

```blade
@auth
  {{-- Full-width: fee / messages / warnings --}}
  @if(...)  {{-- only render the card if any of these have content --}}
  <div class="sb-card mb-3">
      @include('partials.fee')
      @include('partials.messages')
      @include('partials.warnings')
  </div>
  @endif

  @include('partials.progress-bar')   {{-- tournament progress --}}

  <div class="row g-3 align-items-start mt-0">

    {{-- PRIMARY COLUMN (left, 7/12) --}}
    <div class="col-lg-7 col-12">
      @if(session('eventID') != 0)
        @include('partials.games')
      @endif
      @include('partials.points')     {{-- leaderboard --}}
    </div>

    {{-- SIDEBAR COLUMN (right, 5/12) --}}
    <div class="col-lg-5 col-12">
      @include('partials.snapshot-card')
      @if(($firstGameStarted ?? false) && !empty($standings))
        @include('partials.standings')
      @endif
      @include('partials.pointsStandings')
      @include('partials.activity-feed')
    </div>

  </div>

  {{-- tab JS block: remove entirely --}}
@else
  @include('welcome')
  @include('modals.main')
@endauth
```

**Mobile order** (Bootstrap stacks columns top-to-bottom on `col-12`): primary column renders first in DOM, so on mobile the order is: games → leaderboard → snapshot card → standings → activity feed. No `order-*` tricks needed — the natural DOM order is acceptable on mobile.

**Remove:** The `.sb-tabs-nav`, `.sb-tab-btn`, `.sb-tab-pane` markup and the inline `<script>` tab-switching block. The tab CSS classes in `custom.css` can stay (used elsewhere) or be cleaned up separately.

---

## Task 4 — Personal snapshot card

**Files:**
- Create: `resources/views/partials/snapshot-card.blade.php`
- Modify: `app/Http/Controllers/MainController.php` — add `getSnapshotData()` private method, call in `loadApp()`, pass as `$snapshot`

**Data shape:**

```php
[
  'rank'        => 3,
  'total'       => 1987.5,
  'streak_pts'  => 42.0,       // userStreakPoints
  'bingo_count' => 7,
  'average'     => 124.5,
  'last5'       => [           // newest first, up to 5 entries
    ['type' => 'bingo'],       // bingo_points > 0
    ['type' => 'win'],         // winner_points > 0, bingo_points = 0
    ['type' => 'miss'],        // winner_points = 0
    ...
  ],
]
// Returns null if user has no scored games yet
```

**Method** (uses already-computed `$points` array — no extra DB query for rank/total):

```php
private function getSnapshotData(array $points, int $userID): ?array
{
    $rank = null;
    $mine = null;
    foreach ($points as $i => $p) {
        if ($p['userID'] === $userID) { $rank = $i + 1; $mine = $p; break; }
    }
    if (!$mine) return null;

    $last5 = DB::table('point_results as pr')
        ->join('games as g', 'g.id', '=', 'pr.game_id')
        ->where('pr.user_id', $userID)
        ->whereNotNull('g.home_team_score')
        ->orderByDesc('g.game_date')->orderByDesc('g.id')
        ->limit(5)
        ->select('pr.bingo_points', 'pr.winner_points')
        ->get()
        ->map(fn($r) => [
            'type' => $r->bingo_points > 0 ? 'bingo'
                    : ($r->winner_points > 0 ? 'win' : 'miss')
        ])->toArray();

    return [
        'rank'        => $rank,
        'total'       => round($mine['userGamePoints'] + ($mine['userStreakPoints'] ?? 0)
                               + $mine['standingPoints']->total_points + $mine['survivalPoints'], 1),
        'streak_pts'  => round($mine['userStreakPoints'] ?? 0, 1),
        'bingo_count' => $mine['userGameBingo'],
        'average'     => $mine['averagePoints'],
        'last5'       => $last5,
    ];
}
```

**View** (`snapshot-card.blade.php`):

```blade
@if($snapshot ?? null)
<div class="sb-card sn-card">
  <div class="sn-grid">
    <div class="sn-stat">
      <div class="sn-val">#{{ $snapshot['rank'] }}</div>
      <div class="sn-lbl">vieta</div>
    </div>
    <div class="sn-stat">
      <div class="sn-val">{{ number_format($snapshot['total'], 1) }}</div>
      <div class="sn-lbl">taškai</div>
    </div>
    <div class="sn-stat">
      <div class="sn-val">{{ $snapshot['bingo_count'] > 0 ? '★ '.$snapshot['bingo_count'] : '—' }}</div>
      <div class="sn-lbl">bingo</div>
    </div>
    <div class="sn-stat">
      <div class="sn-val">{{ number_format($snapshot['average'], 1) }}</div>
      <div class="sn-lbl">vid./žaidimas</div>
    </div>
  </div>
  @if(count($snapshot['last5']) > 0)
  <div class="sn-dots">
    <span class="sn-dots-lbl">Paskutinės {{ count($snapshot['last5']) }}</span>
    <div class="sn-dots-row">
      @foreach($snapshot['last5'] as $r)
      <div class="sn-dot sn-dot--{{ $r['type'] }}" title="{{ $r['type'] === 'bingo' ? 'Bingo!' : ($r['type'] === 'win' ? 'Nugalėtojas' : 'Praleista') }}"></div>
      @endforeach
    </div>
  </div>
  @endif
</div>
@endif
```

**CSS:**

```css
.sn-card { background: linear-gradient(135deg, #1e3a8a, #1d4ed8); color: #fff; border: none; }
.sn-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 16px; margin-bottom: 12px; }
.sn-stat { display: flex; flex-direction: column; gap: 1px; }
.sn-val { font-size: 1.35rem; font-weight: 800; line-height: 1.1; }
.sn-lbl { font-size: .62rem; opacity: .65; text-transform: uppercase; letter-spacing: .05em; }
.sn-dots { display: flex; align-items: center; gap: 8px; }
.sn-dots-lbl { font-size: .62rem; opacity: .6; white-space: nowrap; }
.sn-dots-row { display: flex; gap: 5px; }
.sn-dot { width: 16px; height: 16px; border-radius: 50%; }
.sn-dot--bingo { background: #22c55e; }
.sn-dot--win   { background: rgba(255,255,255,.5); }
.sn-dot--miss  { background: rgba(255,255,255,.15); }
```

---

## Task 5 — Activity feed

**Files:**
- Create: `resources/views/partials/activity-feed.blade.php`
- Create: `app/Http/Controllers/ActivityFeedController.php`
- Modify: `app/Http/Controllers/MainController.php` — instantiate `ActivityFeedController`, call `getFeed()`, pass as `$activityFeed`

**Feed item types and icons:**

| Type | Condition | Icon | Text |
|------|-----------|------|------|
| `bingo` | `bingo_points > 0` | 🎯 | "username tiksliai: HT score–AT score" |
| `streak` | `streak_bonus > 0` | 🔥 | "username — serija!" |
| `odds` | `winner_points > 0 AND odds > 0.3` | ⭐ | "username nugalėtojas ×{odds+1}" |

Priority when multiple apply to one row: bingo > streak > odds. Each game result per user shows as at most one feed item.

**Controller** (`ActivityFeedController.php`):

```php
<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ActivityFeedController extends Controller
{
    public function getFeed(int $leagueID, int $limit = 20): array
    {
        $rows = DB::table('point_results as pr')
            ->join('games as g', 'g.id', '=', 'pr.game_id')
            ->join('league_members as lm', 'lm.user_id', '=', 'pr.user_id')
            ->join('users as u', 'u.id', '=', 'pr.user_id')
            ->join('teams as ht', 'ht.id', '=', 'g.home_team_id')
            ->join('teams as at', 'at.id', '=', 'g.away_team_id')
            ->where('lm.league_id', $leagueID)
            ->where('lm.is_guest', '<=', session('guest', 0))
            ->whereNotNull('g.home_team_score')
            ->where(function ($q) {
                $q->where('pr.bingo_points', '>', 0)
                  ->orWhere('pr.streak_bonus', '>', 0)
                  ->orWhere(function ($q2) {
                      $q2->where('pr.winner_points', '>', 0)->where('pr.odds', '>', 0.3);
                  });
            })
            ->orderByDesc('g.game_date')
            ->orderByDesc('pr.bingo_points')
            ->limit($limit)
            ->select(
                'u.username', 'g.game_date',
                'g.home_team_score', 'g.away_team_score',
                'ht.team as home_team', 'at.team as away_team',
                'pr.bingo_points', 'pr.winner_points',
                'pr.streak_bonus', 'pr.odds'
            )
            ->get();

        return $rows->map(function ($r) {
            if ($r->bingo_points > 0) {
                $type = 'bingo';
                $icon = '🎯';
                $text = "tiksliai: {$r->home_team} {$r->home_team_score}–{$r->away_team_score} {$r->away_team}";
            } elseif ($r->streak_bonus > 0) {
                $type = 'streak';
                $icon = '🔥';
                $text = 'serija!';
            } else {
                $type = 'odds';
                $icon = '⭐';
                $mult = number_format(1 + (float)$r->odds, 2);
                $text = "nugalėtojas ×{$mult}";
            }
            return [
                'type'     => $type,
                'icon'     => $icon,
                'username' => $r->username,
                'text'     => $text,
                'ago'      => Carbon::parse($r->game_date)->diffForHumans(now(), true),
            ];
        })->toArray();
    }
}
```

**View** (`activity-feed.blade.php`):

```blade
@if(!empty($activityFeed))
<div class="sb-card af-card">
  <div class="sb-card-title"><i class="bi bi-lightning-fill sb-card-icon"></i> Aktyvumas</div>
  <div class="af-list">
    @foreach($activityFeed as $item)
    <div class="af-item">
      <span class="af-icon">{{ $item['icon'] }}</span>
      <div class="af-body">
        <span class="af-user">{{ $item['username'] }}</span>
        <span class="af-text"> {{ $item['text'] }}</span>
        <div class="af-ago">{{ $item['ago'] }}</div>
      </div>
    </div>
    @endforeach
  </div>
</div>
@endif
```

**CSS:**

```css
.af-list { display: flex; flex-direction: column; gap: 0; }
.af-item { display: flex; align-items: flex-start; gap: 8px; padding: 7px 0; border-bottom: 1px solid var(--sb-border); }
.af-item:last-child { border-bottom: none; }
.af-icon { font-size: 1rem; flex-shrink: 0; line-height: 1.4; }
.af-body { font-size: .78rem; color: var(--sb-text); }
.af-user { font-weight: 700; }
.af-text { color: var(--sb-muted); }
.af-ago  { font-size: .65rem; color: var(--sb-muted); margin-top: 1px; }
```

---

## Mobile behaviour summary

| Component | Mobile (< lg) |
|---|---|
| Navbar | Single dark bar, hamburger collapse (unchanged) |
| Progress bar | Full width, wraps gracefully |
| Dashboard columns | Single column; sidebar renders first (snapshot → then primary col below) |
| Snapshot card | Top of page, full width |
| Activity feed | Below standings at bottom |

---

## What is NOT changing

- All existing prediction pages (`/prediction/*`)
- Admin panel
- Summary pages
- Bottom nav tabs
- Dark mode support (all new CSS must use `var(--sb-*)` tokens)
- All existing partial internals (games, points, standings, pointsStandings)
