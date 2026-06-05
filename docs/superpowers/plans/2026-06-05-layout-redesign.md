# Layout Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rework the user-facing layout to a dark navbar + stats hero bar + light card content + mobile bottom tab bar design.

**Architecture:** Pure view/CSS changes — no backend or route modifications. New CSS custom properties drive all colours. A new `partials/bottom-nav.blade.php` adds the mobile tab bar. All content sections gain `.sb-card` wrappers. Stats hero bar is computed via `@php` in the header partial.

**Tech Stack:** Laravel 11 Blade, Bootstrap 5.0.1, Bootstrap Icons 1.11.1, Google Material Icons, custom CSS custom properties.

---

### Task 1: CSS — Design tokens + sb-* utility classes

**Files:**
- Modify: `public/css/custom.css`

- [ ] **Step 1: Add design tokens and all sb-* classes to the top of custom.css**

Open `public/css/custom.css` and **prepend** the following block before all existing rules:

```css
/* ============================================================
   SportBet design tokens
   ============================================================ */
:root {
  --sb-nav:     #1e293b;
  --sb-hero:    #0f172a;
  --sb-accent:  #38bdf8;
  --sb-gold:    #f59e0b;
  --sb-green:   #34d399;
  --sb-purple:  #a78bfa;
  --sb-surface: #f1f5f9;
  --sb-card:    #ffffff;
  --sb-border:  #e2e8f0;
  --sb-text:    #1e293b;
  --sb-muted:   #64748b;
}

/* ============================================================
   Layout
   ============================================================ */
.sb-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background: var(--sb-surface);
}

.sb-main {
  flex: 1;
  padding: 16px;
  padding-bottom: 76px;
}

@media (min-width: 992px) {
  .sb-main { padding-bottom: 16px; }
}

/* ============================================================
   Navbar
   ============================================================ */
.sb-navbar {
  background: var(--sb-nav) !important;
  border-bottom: 1px solid #334155;
  padding: 8px 16px;
}

.sb-brand {
  color: var(--sb-accent) !important;
  font-weight: 700;
  letter-spacing: .5px;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 6px;
}

.sb-nav-link {
  color: rgba(255,255,255,.7);
  text-decoration: none;
  font-size: .9rem;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 2px;
  border-bottom: 2px solid transparent;
  transition: color .15s, border-color .15s;
}

.sb-nav-link:hover { color: #fff; }

.sb-nav-link.active {
  color: #fff;
  border-bottom-color: var(--sb-accent);
}

/* ============================================================
   Stats hero bar
   ============================================================ */
.sb-hero {
  background: linear-gradient(135deg, var(--sb-nav) 0%, var(--sb-hero) 100%);
  display: flex;
  align-items: center;
  padding: 10px 20px;
  border-bottom: 1px solid #334155;
}

.sb-hero-stat {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
}

.sb-hero-value {
  font-size: 1.25rem;
  font-weight: 700;
  line-height: 1;
}

.sb-hero-label {
  font-size: .65rem;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: var(--sb-muted);
}

.sb-hero-divider {
  width: 1px;
  height: 28px;
  background: #334155;
  margin: 0 8px;
}

/* ============================================================
   Content cards
   ============================================================ */
.sb-card {
  background: var(--sb-card);
  border: 1px solid var(--sb-border);
  border-radius: 10px;
  padding: 16px;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
  margin-bottom: 16px;
}

.sb-card-title {
  font-size: .7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: var(--sb-text);
  margin-bottom: 12px;
}

/* ============================================================
   Bottom tab bar (mobile)
   ============================================================ */
.sb-bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 1030;
  background: var(--sb-nav);
  border-top: 1px solid #334155;
  display: flex;
  padding: 6px 0 env(safe-area-inset-bottom, 6px);
}

.sb-tab {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  color: var(--sb-muted);
  text-decoration: none;
  padding: 4px 0;
  transition: color .15s;
}

.sb-tab:hover,
.sb-tab.active { color: var(--sb-accent); }

.sb-tab-label { font-size: .65rem; }
```

- [ ] **Step 2: Remove legacy rules that are superseded**

In the same `public/css/custom.css`, delete these blocks (they conflict with the new layout):
- The `body { background-position... display: flex... }` block (lines ~1-6)
- `.body_side`, `.div-right`, `.div-left`, `.div-content` rules

- [ ] **Step 3: Verify no syntax errors**

Run:
```bash
docker compose exec php php artisan config:clear
```
Expected: no errors. (The CSS has no server-side processing; this just confirms the app still boots.)

- [ ] **Step 4: Commit**

```bash
git add public/css/custom.css
git commit -m "feat: add sb design tokens and utility classes to custom.css"
```

---

### Task 2: Master layout — new body structure

**Files:**
- Modify: `resources/views/layouts/master.blade.php`

- [ ] **Step 1: Replace the entire file**

```blade
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow"/>
    <title>Eurolygos 2025/26 totalizatorius</title>
    <link rel="shortcut icon" type="image/x-icon" href="https://www.thesportsdb.com/images/media/league/badge/7xjtuy1554397263.png" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="{{ URL::to('https://cdn.jsdelivr.net/npm/bootswatch@5.0.1/dist/cerulean/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('/css/custom.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-gtEjrD/SeCtmISkJkNUaaKMoLD0//ElJ19smozuHV6z3Iehds+3Ulb9Bn9Plx0x4" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="sb-layout">
<script>
    $(document).ready(function () {
        $('[data-toggle="popover"]').popover();
    });
</script>

@include('partials.header')

<main class="sb-main">
    @yield('content')
</main>

@include('partials.bottom-nav')

</body>
</html>
```

- [ ] **Step 2: Load the app in a browser and confirm it doesn't crash**

Open `http://localhost` — the page should render. It will look unstyled/broken until Tasks 3–6 are done, but no PHP/Blade error should appear.

- [ ] **Step 3: Commit**

```bash
git add resources/views/layouts/master.blade.php
git commit -m "feat: restructure master layout with sb-layout and sb-main"
```

---

### Task 3: Header partial — dark navbar + stats hero bar

**Files:**
- Modify: `resources/views/partials/header.blade.php`

- [ ] **Step 1: Replace the entire file**

```blade
@auth
@php
    use App\Models\PointResult;
    use App\Models\Game;
    use Illuminate\Support\Facades\DB;

    $sbGamePoints  = round((float) PointResult::where('user_id', session('userID'))->sum('full_points'), 1);
    $sbStandPoints = (float) (DB::table('point_standings')
        ->where('user_id', session('userID'))
        ->selectRaw('COALESCE(SUM(group_position_points),0)
                   + COALESCE(SUM(last16_points),0)
                   + COALESCE(SUM(quarterfinal_points),0)
                   + COALESCE(SUM(semifinal_points),0)
                   + COALESCE(SUM(final_points),0) as total')
        ->value('total') ?? 0);
    $sbTotalPoints = round($sbGamePoints + $sbStandPoints, 1);

    $sbRank = DB::table('users')
        ->join('user_groups', 'users.id', '=', 'user_groups.user_id')
        ->leftJoin('point_results', 'users.id', '=', 'point_results.user_id')
        ->where('user_groups.group_id', session('groupID'))
        ->where('user_groups.guest', '<=', session('guest', 1))
        ->groupBy('users.id')
        ->havingRaw('ROUND(COALESCE(SUM(point_results.full_points), 0), 1) > ?', [$sbGamePoints])
        ->count() + 1;

    $sbUpcoming = Game::whereNull('home_team_score')->whereNull('away_team_score')->count();
    $sbBingo    = PointResult::where('user_id', session('userID'))->where('bingo_points', '>', 0)->count();
@endphp
@endauth

<nav class="navbar navbar-expand-lg sb-navbar">
    <div class="container-fluid px-3">

        {{-- Brand --}}
        <a class="sb-brand" @auth href="{{ route('main') }}" @else href="{{ route('/') }}" @endauth>
            <span class="material-icons" style="font-size:1.2rem;">sports_soccer</span>
            SportBet
        </a>

        {{-- Desktop nav links --}}
        @auth
        <div class="d-none d-lg-flex align-items-center gap-3 ms-4">
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
            @if(session('disabled') != '')
            <div class="dropdown">
                <a class="sb-nav-link dropdown-toggle {{ request()->routeIs('summary.*') ? 'active' : '' }}"
                   href="#" data-bs-toggle="dropdown">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i> Suvestinė
                </a>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="{{ route('summary.history') }}">Įvykę varžybos</a>
                    <a class="dropdown-item" target="_blank" href="{{ route('summary.prediction.results') }}">Spėjimai</a>
                    <a class="dropdown-item" target="_blank" href="{{ route('summary.prediction.standings') }}">Eiga</a>
                    @if(session('survivalGame') != 0)
                    <a class="dropdown-item" href="{{ route('summary.prediction.survivals') }}">Išlikimas</a>
                    @endif
                    <a class="dropdown-item" href="{{ route('summary.chart') }}">Grafikas</a>
                </div>
            </div>
            @endif
        </div>
        @endauth

        {{-- Right-side controls --}}
        <div class="ms-auto d-flex align-items-center gap-2">
            @auth
            {{-- Info dropdown (desktop only) --}}
            <div class="d-none d-lg-block dropdown">
                <a class="sb-nav-link" href="#" data-bs-toggle="dropdown">
                    <i class="bi bi-info-circle h5 mb-0"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="{{ route('users') }}">Dalyviai</a>
                    <a class="dropdown-item" href="{{ route('rules') }}">Taisyklės</a>
                    <a class="dropdown-item" href="{{ route('help') }}">Pagalba</a>
                    <a class="dropdown-item" href="{{ route('charity') }}">Jaunimo linija</a>
                </div>
            </div>
            {{-- Profile dropdown --}}
            <div class="dropdown">
                <a class="sb-nav-link" href="#" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle h5 mb-0"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="{{ route('userProfile') }}"><i class="bi bi-person-fill"></i> Profilis</a>
                    <a class="dropdown-item" href="{{ route('userSettings') }}"><i class="bi bi-gear"></i> Nustatymai</a>
                    <a class="dropdown-item" href="{{ route('userGroup') }}"><i class="bi bi-person-lines-fill"></i> Grupės</a>
                    @if(session('admin') > 1)
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('admin') }}"><i class="bi bi-database-gear"></i> Admin</a>
                    @endif
                </div>
            </div>
            {{-- Logout (desktop only) --}}
            <a class="sb-nav-link d-none d-lg-inline-flex"
               href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="bi bi-box-arrow-right"></i> Atsijungti
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none">@csrf</form>
            @else
            <a class="sb-nav-link" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">
                Prisijungti
            </a>
            @endauth
        </div>

    </div>
</nav>

{{-- Stats hero bar --}}
@auth
<div class="sb-hero">
    <div class="sb-hero-stat">
        <span class="sb-hero-value" style="color:var(--sb-accent);">{{ number_format($sbTotalPoints, 0) }}</span>
        <span class="sb-hero-label">Taškai</span>
    </div>
    <div class="sb-hero-divider"></div>
    <div class="sb-hero-stat">
        <span class="sb-hero-value" style="color:var(--sb-gold);">#{{ $sbRank }}</span>
        <span class="sb-hero-label">Vieta</span>
    </div>
    <div class="sb-hero-divider"></div>
    <div class="sb-hero-stat">
        <span class="sb-hero-value" style="color:var(--sb-green);">{{ $sbUpcoming }}</span>
        <span class="sb-hero-label">Rungtynės</span>
    </div>
    <div class="sb-hero-divider"></div>
    <div class="sb-hero-stat">
        <span class="sb-hero-value" style="color:var(--sb-purple);">{{ $sbBingo }}</span>
        <span class="sb-hero-label">Bingo</span>
    </div>
</div>
@endauth

{{-- Login/register modal (guests only) --}}
@guest
    @include('modals.main')
@endguest
```

- [ ] **Step 2: Load the app and verify**

Open `http://localhost` logged in. Confirm:
- Dark navbar visible with brand in blue
- Stats bar visible below navbar with 4 stats
- Desktop nav links visible on wide viewport
- No PHP errors in the page

- [ ] **Step 3: Commit**

```bash
git add resources/views/partials/header.blade.php
git commit -m "feat: restyle header with dark navbar and stats hero bar"
```

---

### Task 4: Bottom tab bar — new partial

**Files:**
- Create: `resources/views/partials/bottom-nav.blade.php`

- [ ] **Step 1: Create the file**

```blade
@auth
<nav class="sb-bottom-nav d-lg-none">
    <a class="sb-tab {{ request()->routeIs('prediction.results') ? 'active' : '' }}"
       href="{{ route('prediction.results') }}">
        <span class="material-icons" style="font-size:1.4rem;">sports_soccer</span>
        <span class="sb-tab-label">Spėjimai</span>
    </a>
    <a class="sb-tab {{ request()->routeIs('prediction.standings') ? 'active' : '' }}"
       href="{{ route('prediction.standings') }}">
        <i class="bi bi-table" style="font-size:1.2rem;"></i>
        <span class="sb-tab-label">Eiga</span>
    </a>
    @if(session('eventSurvival') == 1 && session('survivalGame') == 1)
    <a class="sb-tab {{ request()->routeIs('prediction.survival') ? 'active' : '' }}"
       href="{{ route('prediction.survival') }}">
        <i class="bi bi-bullseye" style="font-size:1.2rem;"></i>
        <span class="sb-tab-label">Išlikimas</span>
    </a>
    @endif
    <a class="sb-tab {{ request()->routeIs('summary.*') ? 'active' : '' }}"
       href="{{ route('summary.history') }}">
        <i class="bi bi-file-earmark-bar-graph-fill" style="font-size:1.2rem;"></i>
        <span class="sb-tab-label">Suvestinė</span>
    </a>
</nav>
@endauth
```

- [ ] **Step 2: Verify on mobile viewport**

Open browser dev tools, set viewport to 375px wide. Confirm:
- Bottom bar is fixed at the bottom
- Correct tab is highlighted for the current page (e.g., on `/prediction/results`, "Spėjimai" tab is accent blue)
- Top navbar shows only brand and profile icon at this width (no nav links)

- [ ] **Step 3: Commit**

```bash
git add resources/views/partials/bottom-nav.blade.php
git commit -m "feat: add mobile bottom tab bar partial"
```

---

### Task 5: Wrap content partials in sb-card

**Files:**
- Modify: `resources/views/partials/games.blade.php`
- Modify: `resources/views/partials/points.blade.php`
- Modify: `resources/views/partials/standings.blade.php`
- Modify: `resources/views/partials/previous.blade.php`

- [ ] **Step 1: Replace `resources/views/partials/games.blade.php`**

```blade
<div class="sb-card">
    <div class="sb-card-title">🗓 Artimiausios rungtynės</div>
    <table class="table table-sm table-bordered mb-0">
        <thead>
            <tr class="table-primary">
                <th class="d-none d-sm-table-cell text-center"><strong>Nr.</strong></th>
                <th class="text-center"><strong>Šeimininkai</strong></th>
                <th class="text-center"><strong>Svečiai</strong></th>
                <th class="d-none d-sm-table-cell text-center"><strong>Rezultatas</strong></th>
                <th class="text-center"><strong>Spėjimas</strong></th>
                <th class="text-center"><strong>Taškai</strong></th>
            </tr>
        </thead>
        <tbody>
            @foreach($predictionGames as $predictionGame)
            <tr class="table-default">
                <td class="d-none d-sm-table-cell text-center text-body">{{ $predictionGame['gameDetails']->id }}</td>
                <td class="text-left">{{ $predictionGame['gameDetails']->home_team }}</td>
                <td class="text-left">{{ $predictionGame['gameDetails']->away_team }}</td>
                <td class="d-none d-sm-table-cell text-center">
                    {{ $predictionGame['gameDetails']->home_team_score }}&nbsp;:&nbsp;{{ $predictionGame['gameDetails']->away_team_score }}
                </td>
                <td class="text-center">
                    {{ $predictionGame['gameDetails']->p_home_team_score }}&nbsp;:&nbsp;{{ $predictionGame['gameDetails']->p_away_team_score }}
                </td>
                <td class="text-center">
                    <a href="#" data-container="body" data-toggle="popover" data-bs-html="true"
                       data-bs-trigger="hover" data-placement="right"
                       data-bs-content="<div class='row'><div class='col col-8 col-md-9'><div>Už nugalėtoją:</div><div>Už skirtumą:</div><div>Už tikslų skirtumą:</div><div>Už koeficientą:</div><div>Koeficientas:</div></div><div class='col col-4 col-md-3'><div><strong>{{ number_format($predictionGame['gameDetails']->winner_points,2) }}</strong></div><div><strong>{{ number_format($predictionGame['gameDetails']->difference_points,2) }}</strong></div><div><strong>{{ number_format($predictionGame['gameDetails']->bingo_points,2) }}</strong></div><div><strong>{{ number_format($predictionGame['gameDetails']->odds_points,2) }}</strong></div><div><strong>{{ number_format($predictionGame['gameDetails']->odds,2) }}</strong></div></div></div>"
                       data-bs-original-title="Rungtynių taškai">{{ $predictionGame['gameDetails']->full_points }}</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
```

- [ ] **Step 2: Replace `resources/views/partials/points.blade.php`**

```blade
<div class="sb-card">
    <div class="sb-card-title">🏆 Taškų lentelė</div>
    <table class="table table-sm table-bordered mb-0">
        <thead>
            <tr class="table-primary">
                <td class="text-center"><strong>#</strong></td>
                <td class="text-center"><strong>Dalyvis</strong></td>
                <td class="text-center"><strong>Taškai</strong></td>
                <td class="d-none d-sm-table-cell text-center"><strong>Vidurkis</strong></td>
                <td class="d-none d-sm-table-cell text-center"><abbr title="Atspėta tiksliai"><strong>Bingo</strong></abbr></td>
                @if(session('survivalGame') == 1)
                <td class="text-center"><strong>Išlikimas</strong></td>
                @endif
                <td class="text-center"><strong>Eiga</strong></td>
                <td class="text-center"><strong>Viso:</strong></td>
            </tr>
        </thead>
        <tbody>
            @foreach($points as $point)
            <tr class="table-default {{ session('userID') == $point['userID'] ? 'table-primary fw-bold' : '' }}">
                <td class="text-center">{{ $loop->iteration }}</td>
                <td class="text-left">{{ $point['username'] }}</td>
                <td class="text-center">{{ $point['userGamePoints'] }}</td>
                <td class="d-none d-sm-table-cell text-center">{{ $point['averagePoints'] }}</td>
                <td class="d-none d-sm-table-cell text-center">{{ $point['userGameBingo'] }}</td>
                @if(session('survivalGame') == 1)
                <td class="text-center">{{ $point['survivalPoints'] }}</td>
                @endif
                <td class="text-center">
                    <a href="#" data-container="body" data-toggle="popover" data-bs-html="true"
                       data-bs-trigger="hover" data-placement="bottom"
                       data-bs-content="<div class='row'><div class='col col-9 col-md-9'><div>Grupės vietos:</div><div>Patekimas į ketvirtfinalį:</div><div>Finalas:</div></div><div class='col col-3 col-md-3'><div align='right'><strong>{{ $point['standingPoints']->group_position_points }}</strong></div><div align='right'><strong>{{ $point['standingPoints']->quarterfinal_points }}</strong></div><div align='right'><strong>{{ $point['standingPoints']->final_points }}</strong></div></div></div>"
                       data-bs-original-title="Eigos taškai">{{ $point['standingPoints']->total_points }}</a>
                </td>
                <td class="text-center">{{ $point['userGamePoints'] + $point['standingPoints']->total_points + $point['survivalPoints'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
```

- [ ] **Step 3: Replace `resources/views/partials/standings.blade.php`**

```blade
<div class="sb-card">
    <div class="sb-card-title">📊 Finalų dalyvių prognozės</div>
    <table class="table table-sm table-bordered mb-0">
        <thead>
            <tr class="table-primary">
                <td class="text-center"><strong>Komanda</strong></td>
                <td class="text-center"><strong>1 vieta</strong></td>
                <td class="text-center"><strong>2 vieta</strong></td>
                <td class="text-center"><strong>3 vieta</strong></td>
                <td class="text-center"><strong>4 vieta</strong></td>
            </tr>
        </thead>
        <tbody>
            @foreach($standings as $standing)
            <tr class="table-default">
                <td class="text-left">{{ $standing->team }}</td>
                <td class="text-center">{{ $standing->firstPlacePrediction }}</td>
                <td class="text-center">{{ $standing->secondPlacePrediction }}</td>
                <td class="text-center">{{ $standing->thirdPlacePrediction }}</td>
                <td class="text-center">{{ $standing->fourthPlacePrediction }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
```

- [ ] **Step 4: Replace `resources/views/partials/previous.blade.php`**

```blade
<div class="sb-card">
    <div class="sb-card-title">⏮ Praėjusio turo lyderiai</div>
    <table class="table table-sm table-bordered table-hover mb-0">
        <thead>
            <tr class="table-primary">
                <th class="text-center"><strong>Dalyvis</strong></th>
                <th class="text-center"><strong>Turo taškai</strong></th>
                @if(session('survivalGame') == 1)
                <td class="text-center"><strong>Išlikimas</strong></td>
                @endif
                <th class="d-none d-sm-table-cell text-center"><strong>Taškai</strong></th>
                <th class="d-none d-sm-table-cell text-center"><strong>Vidurkis</strong></th>
                <th class="text-center"><strong>Atspėta</strong></th>
            </tr>
        </thead>
        <tbody>
            @foreach($previousRoundPoints as $previousRoundPoint)
            <tr class="table-default">
                <td class="text-left text-body">{{ $previousRoundPoint['username'] }}</td>
                <td class="text-center text-body">{{ $previousRoundPoint['pointResult']->full_points + $previousRoundPoint['pointSurvival'] }}</td>
                @if(session('survivalGame') == 1)
                <td class="text-center text-body">{{ $previousRoundPoint['pointSurvival'] }}</td>
                @endif
                <td class="d-none d-sm-table-cell text-center text-body">{{ $previousRoundPoint['pointResult']->full_points }}</td>
                <td class="d-none d-sm-table-cell text-center text-body">{{ $previousRoundPoint['pointResult']->avg_points }}</td>
                <td class="text-center text-body">{{ $previousRoundPoint['pointResult']->correct_guess }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
```

- [ ] **Step 5: Verify in browser**

Open `http://localhost` logged in. Confirm:
- Games, leaderboard, standings, and previous round sections each appear as white rounded cards
- Your row in the leaderboard is highlighted blue and bold
- Tables render correctly with correct data

- [ ] **Step 6: Commit**

```bash
git add resources/views/partials/games.blade.php \
        resources/views/partials/points.blade.php \
        resources/views/partials/standings.blade.php \
        resources/views/partials/previous.blade.php
git commit -m "feat: wrap content partials in sb-card with titles"
```

---

### Task 6: Clean up main.blade.php

**Files:**
- Modify: `resources/views/main.blade.php`

- [ ] **Step 1: Replace the file**

```blade
@extends('layouts.master')
@section('content')

<div class="container-fluid px-0">
    @auth
        @if(session('disabled') == '')
            <div class="sb-card">
                @include('partials.rules')
            </div>
        @else
            <div class="sb-card">
                @include('partials.fee')
                @include('partials.messages')
                @include('partials.warnings')
            </div>

            <div class="row g-3">
                @if(session('eventRate') == 2)
                    <div class="col-xl-5 col-lg-6 col-12">
                @else
                    <div class="col-xl-6 col-lg-6 col-12">
                @endif
                    @if(session('eventID') != 0)
                        @include('partials.games')
                        @include('partials.previous')
                        @include('partials.standings')
                    @else
                        @include('partials.points')
                    @endif
                </div>

                @if(session('eventRate') == 2)
                    <div class="col-xl-4 col-lg-6 col-12">
                @else
                    <div class="col-xl-6 col-lg-6 col-12">
                @endif
                    @if(session('eventID') != 0)
                        @include('partials.points')
                    @else
                        @include('partials.pointsStandings')
                    @endif
                </div>

                @if(session('eventRate') == 2)
                    <div class="col-xl-3 col-lg-6 col-12">
                        @include('partials.pointsStandings')
                    </div>
                @endif
            </div>
        @endif
    @else
        <div class="sb-card">
            @include('welcome')
        </div>
    @endauth
</div>

@endsection
```

- [ ] **Step 2: Verify final result in browser**

Open `http://localhost` logged in on a desktop-width viewport. Confirm:
- No `<BR>` tags causing extra whitespace
- Two-column grid is intact (games/leaderboard side by side)
- All cards have rounded corners and subtle shadow
- Page background is light grey (`#f1f5f9`), cards are white

Open in a 375px-wide mobile viewport. Confirm:
- Stats bar shows all 4 stats
- Content stacks to single column
- Bottom tab bar is fixed at the bottom
- Correct tab is highlighted for current page
- No content hidden behind the bottom bar (80px padding-bottom on `sb-main`)

- [ ] **Step 3: Commit**

```bash
git add resources/views/main.blade.php
git commit -m "feat: clean up main.blade.php, wrap sections in sb-card"
```

---

### Task 7: Wrap welcome page for guests

**Files:**
- Modify: `resources/views/welcome.blade.php`

- [ ] **Step 1: Remove the outer layout wrapper from welcome.blade.php**

The current `welcome.blade.php` starts with `@extends('layouts.master')` and `@section('content')`. Now that `main.blade.php` already wraps it in an `sb-card` via `@include('welcome')`, those wrapper directives must be removed.

Open `resources/views/welcome.blade.php`. If the file contains `@extends` / `@section` / `@endsection` directives, remove them and keep only the inner HTML content. If it's already just HTML content (no extends), leave it as-is.

- [ ] **Step 2: Final browser check — unauthenticated state**

Open `http://localhost` in a private/incognito window (not logged in). Confirm:
- Dark navbar with "Prisijungti" link visible
- No stats bar shown
- Welcome text is displayed inside a white sb-card
- Login modal opens when "Prisijungti" is clicked
- Registration tab appears in the modal

- [ ] **Step 3: Final commit**

```bash
git add resources/views/welcome.blade.php
git commit -m "feat: remove layout directives from welcome partial for sb-card wrapping"
```
