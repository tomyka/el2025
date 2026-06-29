# UX Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the two-tier navigation with a single slim dark navbar, restructure the main dashboard into a 60/40 two-column layout, and add three engagement features: personal snapshot card, tournament progress bar, and activity feed.

**Architecture:** Five sequential tasks, each independently shippable. Tasks 1–3 restructure shell and layout. Tasks 4–5 add new data-driven partials. All data lives in existing tables; two private methods are added to `MainController` and one new controller is created for the activity feed.

**Tech Stack:** Laravel 11, Blade, Bootstrap 5.0.1, Alpine.js, `public/css/custom.css`

---

## File map

| Action | File |
|--------|------|
| Modify | `resources/views/partials/header.blade.php` |
| Modify | `public/css/custom.css` |
| Create | `resources/views/partials/progress-bar.blade.php` |
| Create | `resources/views/partials/snapshot-card.blade.php` |
| Create | `resources/views/partials/activity-feed.blade.php` |
| Create | `app/Http/Controllers/ActivityFeedController.php` |
| Modify | `app/Http/Controllers/MainController.php` |
| Modify | `resources/views/main.blade.php` |

**Not changing:** admin layout (`admin/layouts/master.blade.php` uses `admin.partials.header`, unaffected), all prediction pages, summary pages, bottom nav.

---

## Task 1: Slim dark navbar

**Files:**
- Modify: `resources/views/partials/header.blade.php`
- Modify: `public/css/custom.css`

### Background

The current nav has two separate bars:
- White `.sb-top-bar` (48 px): logo + avatar dropdown
- Blue `.sb-blue-bar` (40 px): nav links

The new design collapses both into a single `.sb-topnav` (48 px) on a dark `#0f172a` background. Mobile collapse behavior is unchanged.

---

- [ ] **Step 1: Add new CSS block for `.sb-topnav` to `public/css/custom.css`**

Find the existing `/* Outer nav wrapper */` comment (around line 82) and **replace** the block from that comment through `.sb-top-bar .dropdown-divider { ... }` (around line 231) with the following. Everything after line 231 (`.sb-brand`, `.sb-nav-link`, etc.) stays untouched.

```css
/* Outer nav wrapper */
.sb-navbar {
  background: #0f172a;
  padding: 0;
  box-shadow: 0 1px 8px rgba(0,0,0,.25);
}

/* ── Single dark topnav bar ── */
.sb-topnav {
  display: flex;
  align-items: center;
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 20px;
  height: 48px;
  width: 100%;
  gap: 2px;
}

.sb-topnav-links {
  display: flex;
  align-items: stretch;
  gap: 0;
  flex: 1;
  margin-left: 8px;
}

.sb-topnav-right {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-left: auto;
}

/* Brand override for dark background */
.sb-topnav .sb-brand {
  color: #fff !important;
}
.sb-topnav .sb-brand .sb-brand-dot {
  color: #3b82f6;
}

/* Nav links: underline-active style (overrides default pill style) */
.sb-topnav .sb-nav-link {
  color: #94a3b8;
  font-size: .8rem;
  font-weight: 500;
  padding: 0 10px;
  height: 48px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  border-radius: 0;
  border-bottom: 2px solid transparent;
  transition: color .15s, border-color .15s, background .15s;
  white-space: nowrap;
  background: transparent;
  text-decoration: none;
}
.sb-topnav .sb-nav-link:hover {
  color: #e2e8f0;
  background: rgba(255,255,255,.05);
  border-bottom-color: rgba(255,255,255,.15);
}
.sb-topnav .sb-nav-link.active {
  color: #fff;
  font-weight: 600;
  background: transparent;
  border-bottom-color: #3b82f6;
}
.sb-topnav .sb-nav-link.dropdown-toggle::after { display: none; }

/* Dropdowns in topnav: dark-themed */
.sb-topnav .dropdown-menu {
  margin-top: 4px;
  background: #1e2d45;
  border: 1px solid rgba(255,255,255,.1);
  border-radius: 8px;
  box-shadow: 0 8px 24px rgba(0,0,0,.3);
  padding: 4px;
  min-width: 160px;
}
.sb-topnav .dropdown-item {
  border-radius: 5px;
  font-size: .82rem;
  padding: 7px 12px;
  color: rgba(255,255,255,.8);
  display: flex;
  align-items: center;
  gap: 7px;
}
.sb-topnav .dropdown-item:hover,
.sb-topnav .dropdown-item:focus {
  background: rgba(255,255,255,.1);
  color: #fff;
}
.sb-topnav .dropdown-item.active {
  background: rgba(59,130,246,.25);
  color: #93c5fd;
}
.sb-topnav .dropdown-divider {
  border-color: rgba(255,255,255,.1);
  margin: 3px 0;
}

/* Theme button and hamburger override for dark bar */
.sb-topnav .sb-theme-btn  { color: #64748b; }
.sb-topnav .sb-theme-btn:hover { color: #e2e8f0; }
.sb-topnav .sb-toggler     { color: #64748b; }
.sb-topnav .sb-toggler:hover { color: #e2e8f0; }
```

- [ ] **Step 2: Also add a dark-mode override** for `.sb-topnav` at the bottom of `public/css/custom.css`, just after the existing `[data-theme="dark"] .sb-top-bar { ... }` block (around line 2645):

```css
/* ── Topnav dark mode (already dark enough; fix dropdown bg) ── */
[data-theme="dark"] .sb-navbar { background: #0a1120; }
[data-theme="dark"] .sb-topnav .dropdown-menu { background: #152033; }
```

- [ ] **Step 3: Replace `resources/views/partials/header.blade.php` entirely** with the following:

```blade

<nav class="sb-navbar">

  {{-- ============================================================
       Single dark topnav bar (all screen sizes)
       ============================================================ --}}
  <div class="sb-topnav">

    {{-- Brand --}}
    <a class="sb-brand" @auth href="{{ route('main') }}" @else href="{{ route('/') }}" @endauth>
      <img src="{{ asset('img/logo.png') }}" alt="SportBet" style="height:28px;">
      <span>Sport<span class="sb-brand-dot">Bet</span></span>
    </a>

    @auth
    {{-- Desktop: nav links ── d-none d-lg-flex --}}
    <div class="sb-topnav-links d-none d-lg-flex">

      <a class="sb-nav-link {{ request()->routeIs('main') || request()->routeIs('/') ? 'active' : '' }}"
         href="{{ route('main') }}">Pradžia</a>

      <a class="sb-nav-link {{ request()->routeIs('prediction.results') ? 'active' : '' }}"
         href="{{ route('prediction.results') }}">
        <span class="material-icons" style="font-size:1rem;vertical-align:middle;">sports_soccer</span> Spėjimai
      </a>

      <a class="sb-nav-link {{ request()->routeIs('prediction.standings') ? 'active' : '' }}"
         href="{{ route('prediction.standings') }}">
        <i class="bi bi-table"></i> Eiga
      </a>

      @if(session('eventSurvival') == 1 && session('survivalGame') == 1)
      <a class="sb-nav-link {{ request()->routeIs('prediction.survival') ? 'active' : '' }}"
         href="{{ route('prediction.survival') }}">
        <i class="bi bi-bullseye"></i> Išlikimas
      </a>
      @endif

      <span class="sb-nav-sep"></span>

      @if(session('disabled') != '')
      <div class="dropdown">
        <a class="sb-nav-link {{ request()->routeIs('summary.*') ? 'active' : '' }} dropdown-toggle"
           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-bar-chart-line"></i> Suvestinė
        </a>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item {{ request()->routeIs('summary.prediction.results') ? 'active' : '' }}"
                 href="{{ route('summary.prediction.results') }}">
              <i class="bi bi-list-check"></i> Prognozės</a></li>
          <li><a class="dropdown-item {{ request()->routeIs('summary.prediction.standings') ? 'active' : '' }}"
                 href="{{ route('summary.prediction.standings') }}">
              <i class="bi bi-table"></i> Eiga</a></li>
          @if(session('survivalGame') != 0)
          <li><a class="dropdown-item {{ request()->routeIs('summary.prediction.survivals') ? 'active' : '' }}"
                 href="{{ route('summary.prediction.survivals') }}">
              <i class="bi bi-shield-check"></i> Išlikimas</a></li>
          @endif
          <li><a class="dropdown-item {{ request()->routeIs('summary.chart') ? 'active' : '' }}"
                 href="{{ route('summary.chart') }}">
              <i class="bi bi-bar-chart-line"></i> Grafikas</a></li>
        </ul>
      </div>
      @endif

      <div class="dropdown">
        <a class="sb-nav-link {{ request()->routeIs('rules','help','charity','privacy') ? 'active' : '' }} dropdown-toggle"
           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-info-circle"></i> Informacija
        </a>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item {{ request()->routeIs('rules') ? 'active' : '' }}"
                 href="{{ route('rules') }}">
              <i class="bi bi-journal-text"></i> Taisyklės</a></li>
          <li><a class="dropdown-item {{ request()->routeIs('help') ? 'active' : '' }}"
                 href="{{ route('help') }}">
              <i class="bi bi-question-circle"></i> Pagalba</a></li>
          <li><a class="dropdown-item {{ request()->routeIs('charity') ? 'active' : '' }}"
                 href="{{ route('charity') }}">
              <i class="bi bi-heart"></i> Jaunimo linija</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item {{ request()->routeIs('privacy') ? 'active' : '' }}"
                 href="{{ route('privacy') }}">
              <i class="bi bi-shield-lock"></i> Privatumas</a></li>
        </ul>
      </div>

      <span class="sb-nav-sep"></span>

      <a class="sb-nav-link {{ request()->routeIs('leagues.*') ? 'active' : '' }}"
         href="{{ route('leagues.index') }}">
        <i class="bi bi-trophy"></i> Lygos
      </a>

    </div>{{-- /.sb-topnav-links --}}

    {{-- Desktop: right side — theme + league + avatar ── d-none d-lg-flex --}}
    <div class="sb-topnav-right d-none d-lg-flex">
      <button class="sb-theme-btn" onclick="sbToggleTheme()" title="Keisti temą" aria-label="Keisti temą">
        <i class="bi bi-sun-fill sb-theme-sun"></i>
        <i class="bi bi-moon-fill sb-theme-moon"></i>
      </button>
      @if(session('userID'))
      <span style="font-size:.72rem;color:#64748b;white-space:nowrap;">Lyga</span>
      @include('partials.league-switcher')
      @endif
      <div class="dropdown">
        <button class="sb-top-avatar dropdown-toggle" type="button"
                data-bs-toggle="dropdown" aria-expanded="false">
          {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}{{ strtoupper(substr(Auth::user()->surname ?? '', 0, 1)) }}
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item {{ request()->routeIs('userProfile') ? 'active' : '' }}"
                 href="{{ route('userProfile') }}">
              <i class="bi bi-person-fill"></i>Profilis</a></li>
          @if(session('admin') >= 1)
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item {{ request()->routeIs('admin*') ? 'active' : '' }}"
                 href="{{ route('admin') }}">
              <i class="bi bi-database-gear"></i>Admin</a></li>
          @endif
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="{{ route('logout') }}"
                 onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
              <i class="bi bi-box-arrow-right"></i>Atsijungti</a></li>
        </ul>
      </div>
    </div>

    {{-- Mobile: theme toggle + hamburger ── d-flex d-lg-none --}}
    <div class="d-flex d-lg-none align-items-center ms-auto gap-1">
      <button class="sb-theme-btn" onclick="sbToggleTheme()" title="Keisti temą" aria-label="Keisti temą">
        <i class="bi bi-sun-fill sb-theme-sun"></i>
        <i class="bi bi-moon-fill sb-theme-moon"></i>
      </button>
      <button class="sb-toggler" type="button"
              data-bs-toggle="collapse" data-bs-target="#sbNavMobile"
              aria-controls="sbNavMobile" aria-expanded="false" aria-label="Atidaryti meniu">
        <i class="bi bi-list"></i>
      </button>
    </div>

    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>

    @else
    {{-- Guest --}}
    <div class="ms-auto d-flex align-items-center gap-1">
      <button class="sb-theme-btn" onclick="sbToggleTheme()" title="Keisti temą" aria-label="Keisti temą">
        <i class="bi bi-sun-fill sb-theme-sun"></i>
        <i class="bi bi-moon-fill sb-theme-moon"></i>
      </button>
      <a class="sb-nav-pill sb-nav-pill--ghost" style="margin-left:0" href="{{ route('charity') }}">
        <i class="bi bi-heart-fill" style="font-size:.75rem;"></i> Jaunimo linija
      </a>
      <a class="sb-nav-pill ms-0" href="{{ route('login') }}">Prisijungti</a>
    </div>
    @endauth

  </div>{{-- /.sb-topnav --}}

  {{-- ============================================================
       Mobile collapse panel — same dark background
       ============================================================ --}}
  @auth
  <div class="collapse sb-mobile-collapse d-lg-none w-100" id="sbNavMobile">

    <div class="sb-mobile-group">
      <div class="sb-mobile-label">Spėjimai</div>
      <a class="sb-nav-link {{ request()->routeIs('prediction.results') ? 'active' : '' }}"
         href="{{ route('prediction.results') }}">
        <span class="material-icons" style="font-size:1rem;vertical-align:middle;">sports_soccer</span> Rungtynių spėjimai
      </a>
      <a class="sb-nav-link {{ request()->routeIs('prediction.standings') ? 'active' : '' }}"
         href="{{ route('prediction.standings') }}">
        <i class="bi bi-table"></i> Turnyro eiga
      </a>
      @if(session('eventSurvival') == 1 && session('survivalGame') == 1)
      <a class="sb-nav-link {{ request()->routeIs('prediction.survival') ? 'active' : '' }}"
         href="{{ route('prediction.survival') }}">
        <i class="bi bi-bullseye"></i> Išlikimas
      </a>
      @endif
    </div>

    @if(session('disabled') != '')
    <div class="sb-mobile-group">
      <div class="sb-mobile-label">Suvestinė</div>
      <a class="sb-nav-link {{ request()->routeIs('summary.prediction.results') ? 'active' : '' }}"
         href="{{ route('summary.prediction.results') }}">
        <i class="bi bi-list-check"></i> Prognozės
      </a>
      <a class="sb-nav-link {{ request()->routeIs('summary.prediction.standings') ? 'active' : '' }}"
         href="{{ route('summary.prediction.standings') }}">
        <i class="bi bi-table"></i> Eiga
      </a>
      @if(session('survivalGame') != 0)
      <a class="sb-nav-link {{ request()->routeIs('summary.prediction.survivals') ? 'active' : '' }}"
         href="{{ route('summary.prediction.survivals') }}">
        <i class="bi bi-shield-check"></i> Išlikimas
      </a>
      @endif
      <a class="sb-nav-link {{ request()->routeIs('summary.chart') ? 'active' : '' }}"
         href="{{ route('summary.chart') }}">
        <i class="bi bi-bar-chart-line"></i> Grafikas
      </a>
    </div>
    @endif

    <div class="sb-mobile-group">
      <div class="sb-mobile-label">Informacija</div>
      <a class="sb-nav-link {{ request()->routeIs('rules') ? 'active' : '' }}"
         href="{{ route('rules') }}">
        <i class="bi bi-journal-text"></i> Taisyklės
      </a>
      <a class="sb-nav-link {{ request()->routeIs('help') ? 'active' : '' }}"
         href="{{ route('help') }}">
        <i class="bi bi-question-circle"></i> Pagalba
      </a>
      <a class="sb-nav-link {{ request()->routeIs('charity') ? 'active' : '' }}"
         href="{{ route('charity') }}">
        <i class="bi bi-heart"></i> Jaunimo linija
      </a>
      <a class="sb-nav-link {{ request()->routeIs('privacy') ? 'active' : '' }}"
         href="{{ route('privacy') }}">
        <i class="bi bi-shield-lock"></i> Privatumas
      </a>
    </div>

    @if(session('userID'))
    <div class="sb-mobile-group">
      <div class="sb-mobile-label">Lyga</div>
      <a class="sb-nav-link {{ request()->routeIs('leagues.*') ? 'active' : '' }}"
         href="{{ route('leagues.index') }}">
        <i class="bi bi-gear"></i> Tvarkyti lygą
      </a>
    </div>
    @endif

    <div class="sb-mobile-group">
      <div class="sb-mobile-label">Paskyra</div>
      <a class="sb-nav-link {{ request()->routeIs('userProfile') ? 'active' : '' }}"
         href="{{ route('userProfile') }}">
        <i class="bi bi-person-fill"></i> Profilis
      </a>
      @if(session('admin') >= 1)
      <a class="sb-nav-link {{ request()->routeIs('admin*') ? 'active' : '' }}"
         href="{{ route('admin') }}">
        <i class="bi bi-database-gear"></i> Admin
      </a>
      @endif
      <a class="sb-nav-link" href="{{ route('logout') }}"
         onclick="event.preventDefault(); document.getElementById('logout-form-m').submit();">
        <i class="bi bi-box-arrow-right"></i> Atsijungti
      </a>
      <form id="logout-form-m" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
    </div>

  </div>
  @endauth

</nav>
```

- [ ] **Step 4: Verify visually in the browser**

Navigate to the main page. You should see:
- Single dark (`#0f172a`) bar, ~48 px tall
- Logo left, nav links center-left, league switcher + avatar right
- Active page link has a blue underline at the bottom
- No white bar, no separate blue bar below it
- Mobile: hamburger still collapses the menu, dark collapse panel unchanged

- [ ] **Step 5: Commit**

```bash
git add resources/views/partials/header.blade.php public/css/custom.css
git commit -m "feat: replace two-tier nav with single slim dark navbar"
```

---

## Task 2: Tournament progress bar

**Files:**
- Create: `resources/views/partials/progress-bar.blade.php`
- Modify: `app/Http/Controllers/MainController.php`
- Modify: `resources/views/main.blade.php`

---

- [ ] **Step 1: Add CSS for progress bar to `public/css/custom.css`** (append after existing blocks, before the dark-mode section)

```css
/* ============================================================
   Tournament progress bar
   ============================================================ */
.sb-progress-strip {
  background: var(--sb-card);
  border-bottom: 1px solid var(--sb-border);
  padding: 7px 20px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: .75rem;
  flex-wrap: wrap;
}
.sb-progress-label  { font-weight: 700; color: var(--sb-accent); white-space: nowrap; }
.sb-progress-bar-wrap { flex: 1; min-width: 60px; height: 6px; background: #e2e8f0; border-radius: 99px; overflow: hidden; }
.sb-progress-bar-fill { height: 100%; background: linear-gradient(90deg, var(--sb-accent), #3b82f6); border-radius: 99px; transition: width .4s ease; }
.sb-progress-count  { color: var(--sb-muted); white-space: nowrap; }
.sb-progress-today  { background: #f59e0b; color: #fff; font-size: .68rem; font-weight: 700; border-radius: 4px; padding: 2px 7px; white-space: nowrap; }
[data-theme="dark"] .sb-progress-bar-wrap { background: #334155; }
```

- [ ] **Step 2: Create `resources/views/partials/progress-bar.blade.php`**

```blade
@if(!empty($tournamentProgress))
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

- [ ] **Step 3: Add `getTournamentProgress()` to `app/Http/Controllers/MainController.php`**

Add this private method inside the `MainController` class, just before the closing `}` of the class:

```php
private function getTournamentProgress(): ?array
{
    $eventID = session('eventID');
    if (!$eventID) return null;

    $eventId = DB::table('games')->where('id', $eventID)->value('event_id');
    if (!$eventId) return null;

    $event = DB::table('events')->where('id', $eventId)->first();
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

- [ ] **Step 4: Call the method in `loadApp()` and pass to the view**

In `MainController::loadApp()`, just before the `return view('main')->with(...)` line (around line 68), add:

```php
$tournamentProgress = $this->getTournamentProgress();
```

Then add `->with('tournamentProgress', $tournamentProgress)` to the `return view(...)` chain. The full return becomes:

```php
return view('main')
    ->with('messages', $messages)
    ->with('points', $points)
    ->with('predictionGames', $predictionResultsWithStats)
    ->with('eventDaySurvivalStatus', $eventDaySurvivalStatus)
    ->with('groupDetails', $feeController->getGroupDetails())
    ->with('userDetails', $feeController->getUserDetails())
    ->with('fund', $feeController->getFund())
    ->with('fundCollected', $feeController->getFundCollected())
    ->with('standings', $standings)
    ->with('predictionStandingsPoints', $predictionStandingsPoints)
    ->with('rankHistory', $rankHistory)
    ->with('firstGameStarted', $firstGameStarted)
    ->with('standingsMissing', $standingsMissing)
    ->with('tournamentProgress', $tournamentProgress);
```

- [ ] **Step 5: Include the partial in `resources/views/main.blade.php`**

In the `@auth` block, just after the opening `@auth` line and before the `<div class="sb-card">` (fee/messages/warnings), add:

```blade
@include('partials.progress-bar')
```

- [ ] **Step 6: Verify in the browser**

Load the main page. You should see a thin strip just below the navbar showing the current event name, a filled progress bar, and the game count. If there are games today (time check is UTC-based, may differ), the amber "⏱ N šiandien" badge appears.

- [ ] **Step 7: Commit**

```bash
git add resources/views/partials/progress-bar.blade.php \
        app/Http/Controllers/MainController.php \
        resources/views/main.blade.php \
        public/css/custom.css
git commit -m "feat: add tournament progress bar below navbar"
```

---

## Task 3: Two-column dashboard layout

**Files:**
- Modify: `resources/views/main.blade.php`

### Background

Current `@auth` content in `main.blade.php`:
```
<div class="sb-card">fee/messages/warnings</div>
<div class="row g-3">
  <div class="col-xl-6 col-lg-6 col-12"> games </div>
  <div class="col-xl-6 col-lg-6 col-12">
    <div class="sb-tabs-nav">...</div>
    <div id="main-tab-pts"> points </div>
    <div id="main-tab-eiga"> standings + pointsStandings </div>
  </div>
</div>
<script>tab switching</script>
```

New structure removes the tab system and adds a 60/40 split. The snapshot-card and activity-feed partials created in Tasks 4 and 5 are referenced here — they will render nothing until those tasks are complete (Blade `@include` of non-existent files throws an error, so create stub files first in Step 1).

---

- [ ] **Step 1: Create stub partials so the layout doesn't error**

Create `resources/views/partials/snapshot-card.blade.php` (empty for now):

```blade
{{-- stub: implemented in Task 4 --}}
```

Create `resources/views/partials/activity-feed.blade.php` (empty for now):

```blade
{{-- stub: implemented in Task 5 --}}
```

- [ ] **Step 2: Rewrite the `@auth` block of `resources/views/main.blade.php`**

Replace everything from the opening `@auth` through `@else` (keep `@else` and everything after it — the guest welcome view — untouched). The new auth block:

```blade
@auth
    @include('partials.progress-bar')

    @php $showNotices = !empty(session('fee')) || session()->has('info') || session()->has('error') || $standingsMissing; @endphp
    @if($showNotices)
    <div class="sb-card mb-3">
        @include('partials.fee')
        @include('partials.messages')
        @include('partials.warnings')
    </div>
    @endif

    <div class="row g-3 align-items-start">

        {{-- PRIMARY COLUMN: upcoming games + leaderboard --}}
        <div class="col-lg-7 col-12">
            @if(session('eventID') != 0)
                @include('partials.games')
            @endif
            @include('partials.points')
        </div>

        {{-- SIDEBAR COLUMN: snapshot + standings + activity --}}
        <div class="col-lg-5 col-12">
            @include('partials.snapshot-card')
            @if(($firstGameStarted ?? false) && !empty($standings))
                @include('partials.standings')
            @endif
            @include('partials.pointsStandings')
            @include('partials.activity-feed')
        </div>

    </div>
```

**Note:** The `@include('partials.progress-bar')` was already added in Task 2 Step 5. Remove that earlier include now that it's inside the new auth block structure — or keep it there if the file already only has it once. Check `main.blade.php` for duplicates after editing.

- [ ] **Step 3: Verify layout in browser on a wide desktop**

You should see:
- Left 60%: upcoming games card + leaderboard
- Right 40%: (empty snapshot stub) + standings + standings points
- No tab buttons anywhere on the page
- No "Taškai / Eigos taškai" tab bar

On a narrow window or mobile: single column, games → leaderboard → standings.

- [ ] **Step 4: Commit**

```bash
git add resources/views/main.blade.php \
        resources/views/partials/snapshot-card.blade.php \
        resources/views/partials/activity-feed.blade.php
git commit -m "feat: two-column dashboard layout, remove tab system"
```

---

## Task 4: Personal snapshot card

**Files:**
- Modify: `resources/views/partials/snapshot-card.blade.php` (replace stub)
- Modify: `app/Http/Controllers/MainController.php`
- Modify: `public/css/custom.css`

---

- [ ] **Step 1: Add CSS for snapshot card to `public/css/custom.css`**

Append after the progress bar CSS block added in Task 2:

```css
/* ============================================================
   Personal snapshot card
   ============================================================ */
.sn-card {
  background: linear-gradient(135deg, #1e3a8a, #1d4ed8);
  color: #fff;
  border: none;
}
.sn-card .sb-card-title { color: rgba(255,255,255,.7); }
.sn-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px 20px;
  margin-bottom: 14px;
}
.sn-stat { display: flex; flex-direction: column; gap: 2px; }
.sn-val  { font-size: 1.4rem; font-weight: 800; line-height: 1.1; }
.sn-lbl  { font-size: .6rem; opacity: .6; text-transform: uppercase; letter-spacing: .06em; }
.sn-dots { display: flex; align-items: center; gap: 8px; margin-top: 4px; }
.sn-dots-lbl { font-size: .62rem; opacity: .55; white-space: nowrap; }
.sn-dots-row { display: flex; gap: 5px; }
.sn-dot { width: 16px; height: 16px; border-radius: 50%; flex-shrink: 0; }
.sn-dot--bingo { background: #22c55e; }
.sn-dot--win   { background: rgba(255,255,255,.45); }
.sn-dot--miss  { background: rgba(255,255,255,.15); }
```

- [ ] **Step 2: Add `getSnapshotData()` to `app/Http/Controllers/MainController.php`**

Add this private method to the `MainController` class (alongside `getTournamentProgress()` and `standingsMissing()`):

```php
private function getSnapshotData(array $points, int $userID): ?array
{
    $rank = null;
    $mine = null;
    foreach ($points as $i => $p) {
        if ($p['userID'] === $userID) {
            $rank = $i + 1;
            $mine = $p;
            break;
        }
    }
    if (!$mine) return null;

    $last5 = DB::table('point_results as pr')
        ->join('games as g', 'g.id', '=', 'pr.game_id')
        ->where('pr.user_id', $userID)
        ->whereNotNull('g.home_team_score')
        ->orderByDesc('g.game_date')
        ->orderByDesc('g.id')
        ->limit(5)
        ->select('pr.bingo_points', 'pr.winner_points')
        ->get()
        ->map(fn($r) => [
            'type' => $r->bingo_points > 0 ? 'bingo'
                    : ($r->winner_points > 0 ? 'win' : 'miss'),
        ])
        ->toArray();

    if (empty($last5)) return null;

    return [
        'rank'        => $rank,
        'total'       => round(
            $mine['userGamePoints']
            + ($mine['userStreakPoints'] ?? 0)
            + $mine['standingPoints']->total_points
            + $mine['survivalPoints'],
            1
        ),
        'bingo_count' => $mine['userGameBingo'],
        'average'     => $mine['averagePoints'],
        'last5'       => $last5,
    ];
}
```

- [ ] **Step 3: Call `getSnapshotData()` in `loadApp()` and pass to view**

In `MainController::loadApp()`, after the line `$tournamentProgress = $this->getTournamentProgress();`, add:

```php
$snapshot = $this->getSnapshotData($points, $userID);
```

Add `->with('snapshot', $snapshot)` to the return chain.

- [ ] **Step 4: Replace stub with real `resources/views/partials/snapshot-card.blade.php`**

```blade
@if(!empty($snapshot))
<div class="sb-card sn-card mb-0">
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
            <div class="sn-lbl">vid. / žaidimas</div>
        </div>
    </div>
    @if(count($snapshot['last5']) > 0)
    <div class="sn-dots">
        <span class="sn-dots-lbl">Paskutinės {{ count($snapshot['last5']) }}</span>
        <div class="sn-dots-row">
            @foreach($snapshot['last5'] as $r)
            <div class="sn-dot sn-dot--{{ $r['type'] }}"
                 title="{{ $r['type'] === 'bingo' ? 'Bingo!' : ($r['type'] === 'win' ? 'Nugalėtojas' : 'Praleista') }}">
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endif
```

- [ ] **Step 5: Verify in browser**

Load the main page. In the right sidebar you should see a blue gradient card with your rank, total points, bingo count, average, and 5 colored dots (🟢 bingo, dim white win, very dim miss). If you have no scored games yet, the card is hidden.

- [ ] **Step 6: Commit**

```bash
git add resources/views/partials/snapshot-card.blade.php \
        app/Http/Controllers/MainController.php \
        public/css/custom.css
git commit -m "feat: personal snapshot card in sidebar"
```

---

## Task 5: Activity feed

**Files:**
- Create: `app/Http/Controllers/ActivityFeedController.php`
- Modify: `resources/views/partials/activity-feed.blade.php` (replace stub)
- Modify: `app/Http/Controllers/MainController.php`
- Modify: `public/css/custom.css`

---

- [ ] **Step 1: Add CSS for activity feed to `public/css/custom.css`**

Append after snapshot card CSS:

```css
/* ============================================================
   Activity feed
   ============================================================ */
.af-list { display: flex; flex-direction: column; }
.af-item {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 7px 0;
  border-bottom: 1px solid var(--sb-border);
}
.af-item:last-child { border-bottom: none; }
.af-icon { font-size: 1rem; flex-shrink: 0; line-height: 1.5; }
.af-body { font-size: .78rem; color: var(--sb-text); line-height: 1.4; }
.af-user { font-weight: 700; }
.af-text { color: var(--sb-muted); }
.af-ago  { font-size: .65rem; color: var(--sb-muted); margin-top: 1px; }
```

- [ ] **Step 2: Create `app/Http/Controllers/ActivityFeedController.php`**

```php
<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
                      $q2->where('pr.winner_points', '>', 0)
                         ->where('pr.odds', '>', 0.3);
                  });
            })
            ->orderByDesc('g.game_date')
            ->orderByDesc('pr.bingo_points')
            ->limit($limit)
            ->select(
                'u.username',
                'g.game_date',
                'g.home_team_score',
                'g.away_team_score',
                'ht.team as home_team',
                'at.team as away_team',
                'pr.bingo_points',
                'pr.winner_points',
                'pr.streak_bonus',
                'pr.odds'
            )
            ->get();

        return $rows->map(function ($r) {
            if ($r->bingo_points > 0) {
                $icon = '🎯';
                $text = "tiksliai: {$r->home_team} {$r->home_team_score}–{$r->away_team_score} {$r->away_team}";
            } elseif ($r->streak_bonus > 0) {
                $icon = '🔥';
                $text = 'serija!';
            } else {
                $icon = '⭐';
                $mult = number_format(1 + (float) $r->odds, 2);
                $text = "nugalėtojas ×{$mult}";
            }

            return [
                'icon'     => $icon,
                'username' => $r->username,
                'text'     => $text,
                'ago'      => Carbon::parse($r->game_date)->diffForHumans(now(), true),
            ];
        })->toArray();
    }
}
```

- [ ] **Step 3: Replace stub with real `resources/views/partials/activity-feed.blade.php`**

```blade
@if(!empty($activityFeed))
<div class="sb-card">
    <div class="sb-card-title">
        <i class="bi bi-lightning-fill sb-card-icon"></i> Aktyvumas
    </div>
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

- [ ] **Step 4: Wire up in `MainController::loadApp()`**

At the top of `loadApp()`, after the other controller instantiations, add:

```php
$activityFeedController = new ActivityFeedController();
```

After the line `$snapshot = $this->getSnapshotData($points, $userID);`, add:

```php
$activityFeed = $activityFeedController->getFeed($groupID);
```

Add `->with('activityFeed', $activityFeed)` to the return chain.

- [ ] **Step 5: Verify in browser**

Load the main page. At the bottom of the right sidebar you should see the "Aktyvumas" card with recent feed items. Each item shows an emoji, username, description, and a relative time ("prieš 3 val.", "Vakar", etc.). If nobody in your league has a bingo, streak, or odds win yet, the card is hidden.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/ActivityFeedController.php \
        resources/views/partials/activity-feed.blade.php \
        app/Http/Controllers/MainController.php \
        public/css/custom.css
git commit -m "feat: activity feed — bingos, streaks, contrarian wins"
```

---

## Self-review checklist (completed inline)

- **Spec coverage:** All 5 spec sections covered (navbar ✓, progress bar ✓, 2-col layout ✓, snapshot card ✓, activity feed ✓).
- **Placeholders:** None — all code blocks are complete.
- **Type consistency:** `getTournamentProgress()` returns `?array` with keys `event_name`, `total_games`, `scored_games`, `today_games`, `pct` — all used correctly in the view. `getSnapshotData()` returns `?array` with keys `rank`, `total`, `bingo_count`, `average`, `last5` — all used correctly. `ActivityFeedController::getFeed()` returns array of `['icon','username','text','ago']` — all used correctly.
- **Stub files in Task 3 Step 1:** Created before they are `@include`d, preventing Blade errors during incremental rollout.
- **Progress bar duplication:** Task 2 includes the partial from main.blade.php; Task 3 moves it inside the `@auth` block — implementer must remove any duplicate include after Task 3.
