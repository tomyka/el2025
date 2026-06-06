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
            <a class="sb-nav-link" href="{{ route('login') }}">
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

