@auth
@php
    $sbGamePoints  = round((float) \App\Models\PointResult::where('user_id', session('userID'))->sum('full_points'), 1);
    $sbStandPoints = (float) (\Illuminate\Support\Facades\DB::table('point_standings')
        ->where('user_id', session('userID'))
        ->selectRaw('COALESCE(SUM(group_position_points),0)
                   + COALESCE(SUM(last16_points),0)
                   + COALESCE(SUM(quarterfinal_points),0)
                   + COALESCE(SUM(semifinal_points),0)
                   + COALESCE(SUM(final_points),0) as total')
        ->value('total') ?? 0);
    $sbTotalPoints = round($sbGamePoints + $sbStandPoints, 1);

    $sbRank = \Illuminate\Support\Facades\DB::table('users')
        ->select('users.id')
        ->join('user_groups', 'users.id', '=', 'user_groups.user_id')
        ->leftJoin('point_results', 'users.id', '=', 'point_results.user_id')
        ->where('user_groups.group_id', session('groupID'))
        ->where('user_groups.guest', '<=', session('guest', 1))
        ->groupBy('users.id')
        ->havingRaw('ROUND(COALESCE(SUM(point_results.full_points), 0), 1) > ?', [$sbGamePoints])
        ->count() + 1;

    $sbUpcoming = \App\Models\Game::whereNull('home_team_score')->whereNull('away_team_score')->count();
    $sbBingo    = \App\Models\PointResult::where('user_id', session('userID'))->where('bingo_points', '>', 0)->count();
@endphp
@endauth

<nav class="navbar sb-navbar">

    {{-- ============================================================
         DESKTOP  —  3-column grid, lg and above
         ============================================================ --}}
    <div class="sb-desktop-nav d-none d-lg-grid">

        {{-- Left: brand --}}
        <div class="sb-nav-start">
            <a class="sb-brand" @auth href="{{ route('main') }}" @else href="{{ route('/') }}" @endauth>
                <span class="material-icons sb-brand-dot" style="font-size:1.3rem;">sports_soccer</span>
                Sport<span class="sb-brand-dot">Bet</span>
            </a>
        </div>

        {{-- Center: ALL nav links together --}}
        <div class="sb-nav-center">
            @auth
            {{-- Predictions --}}
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

            {{-- Summary (tournament started) --}}
            @if(session('disabled') != '')
            <span class="sb-nav-sep"></span>
            <a class="sb-nav-link {{ request()->routeIs('summary.history') ? 'active' : '' }}"
               href="{{ route('summary.history') }}">
                <i class="bi bi-clock-history"></i> Varžybos
            </a>
            <a class="sb-nav-link {{ request()->routeIs('summary.prediction.results') ? 'active' : '' }}"
               href="{{ route('summary.prediction.results') }}" target="_blank">
                <i class="bi bi-list-check"></i> Prognozės
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
            @endif

            {{-- Info --}}
            <span class="sb-nav-sep"></span>
            <a class="sb-nav-link {{ request()->routeIs('users') ? 'active' : '' }}"
               href="{{ route('users') }}">
                <i class="bi bi-people"></i> Dalyviai
            </a>
            <a class="sb-nav-link {{ request()->routeIs('rules') ? 'active' : '' }}"
               href="{{ route('rules') }}">
                <i class="bi bi-journal-text"></i> Taisyklės
            </a>
            @endauth
        </div>

        {{-- Right: profile dropdown (authenticated) or login pill (guest) --}}
        <div class="sb-nav-end">
            @auth
            <div class="dropdown">
                <a class="sb-nav-link dropdown-toggle {{ request()->routeIs('userProfile', 'userSettings', 'admin*') ? 'active' : '' }}"
                   href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"
                   style="font-size:1.3rem; padding:4px 8px;">
                    <i class="bi bi-person-circle"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item {{ request()->routeIs('userProfile') ? 'active' : '' }}"
                           href="{{ route('userProfile') }}">
                        <i class="bi bi-person-fill me-1"></i>Profilis</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('userSettings') ? 'active' : '' }}"
                           href="{{ route('userSettings') }}">
                        <i class="bi bi-gear me-1"></i>Nustatymai</a></li>
                    @if(session('admin') > 1)
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item {{ request()->routeIs('admin*') ? 'active' : '' }}"
                           href="{{ route('admin') }}">
                        <i class="bi bi-database-gear me-1"></i>Admin pultas</a></li>
                    @endif
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item"
                           href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="bi bi-box-arrow-right me-1"></i>Atsijungti</a></li>
                </ul>
            </div>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
            @else
            <a class="sb-nav-pill" href="{{ route('login') }}">Prisijungti</a>
            @endauth
        </div>

    </div>

    {{-- ============================================================
         MOBILE  —  brand + hamburger row, below lg
         ============================================================ --}}
    <div class="sb-mobile-bar d-lg-none">
        <a class="sb-brand" @auth href="{{ route('main') }}" @else href="{{ route('/') }}" @endauth>
            <span class="material-icons sb-brand-dot" style="font-size:1.3rem;">sports_soccer</span>
            Sport<span class="sb-brand-dot">Bet</span>
        </a>

        @auth
        <button class="sb-toggler ms-auto" type="button"
                data-bs-toggle="collapse" data-bs-target="#sbNavMobile"
                aria-controls="sbNavMobile" aria-expanded="false" aria-label="Atidaryti meniu">
            <i class="bi bi-list"></i>
        </button>
        @else
        <a class="sb-nav-pill ms-auto" href="{{ route('login') }}">Prisijungti</a>
        @endauth
    </div>

    {{-- Mobile collapse --}}
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
            <a class="sb-nav-link {{ request()->routeIs('summary.history') ? 'active' : '' }}"
               href="{{ route('summary.history') }}">
                <i class="bi bi-clock-history"></i> Įvykę varžybos
            </a>
            <a class="sb-nav-link" href="{{ route('summary.prediction.results') }}" target="_blank">
                <i class="bi bi-list-check"></i> Prognozės
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
            <a class="sb-nav-link {{ request()->routeIs('users') ? 'active' : '' }}"
               href="{{ route('users') }}">
                <i class="bi bi-people"></i> Dalyviai
            </a>
            <a class="sb-nav-link {{ request()->routeIs('rules') ? 'active' : '' }}"
               href="{{ route('rules') }}">
                <i class="bi bi-journal-text"></i> Taisyklės
            </a>
            <a class="sb-nav-link" href="{{ route('help') }}">
                <i class="bi bi-question-circle"></i> Pagalba
            </a>
            <a class="sb-nav-link" href="{{ route('charity') }}">
                <i class="bi bi-heart"></i> Jaunimo linija
            </a>
        </div>

        <div class="sb-mobile-group">
            <div class="sb-mobile-label">Paskyra</div>
            <a class="sb-nav-link {{ request()->routeIs('userProfile') ? 'active' : '' }}"
               href="{{ route('userProfile') }}">
                <i class="bi bi-person-fill"></i> Profilis
            </a>
            <a class="sb-nav-link {{ request()->routeIs('userSettings') ? 'active' : '' }}"
               href="{{ route('userSettings') }}">
                <i class="bi bi-gear"></i> Nustatymai
            </a>
            @if(session('admin') > 1)
            <a class="sb-nav-link {{ request()->routeIs('admin*') ? 'active' : '' }}"
               href="{{ route('admin') }}">
                <i class="bi bi-database-gear"></i> Admin
            </a>
            @endif
            <a class="sb-nav-link"
               href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form-m').submit();">
                <i class="bi bi-box-arrow-right"></i> Atsijungti
            </a>
            <form id="logout-form-m" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
        </div>

    </div>
    @endauth

</nav>

{{-- Stats hero bar --}}
@auth
<div class="sb-hero">
    <div class="sb-hero-inner sb-container">
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
</div>
@endauth
