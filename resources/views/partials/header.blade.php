
<nav class="navbar sb-navbar">

    {{-- ============================================================
         DESKTOP  —  3-column grid, lg and above
         ============================================================ --}}
    <div class="sb-desktop-nav d-none d-lg-grid">

        {{-- Left: brand --}}
        <div class="sb-nav-start">
            <a class="sb-brand" @auth href="{{ route('main') }}" @else href="{{ route('/') }}" @endauth>
                <img src="{{ asset('img/logo.png') }}" alt="SportBet" style="height:36px;">
                <span>Sport<span class="sb-brand-dot">Bet</span></span>
            </a>
        </div>

        {{-- Center: ALL nav links together --}}
        <div class="sb-nav-center">
            @auth
            {{-- Spėjimai group --}}
            <div class="sb-nav-grp">
                <span class="sb-nav-grp-label">Spėjimai</span>
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
            </div>

            {{-- Suvestinė group (tournament started) --}}
            @if(session('disabled') != '')
            <span class="sb-nav-grp-sep"></span>
            <div class="sb-nav-grp">
                <span class="sb-nav-grp-label">Suvestinė</span>
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

            {{-- Informacija group --}}
            <span class="sb-nav-grp-sep"></span>
            <div class="sb-nav-grp">
                <span class="sb-nav-grp-label">Informacija</span>
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
            </div>
            @endauth
        </div>

        {{-- Right: profile dropdown (authenticated) or login pill (guest) --}}
        <div class="sb-nav-end">
            @auth
            <div class="sb-nav-grp">
                <span class="sb-nav-grp-label">Paskyra</span>
            <div class="dropdown">
                <a class="sb-nav-link dropdown-toggle {{ request()->routeIs('userProfile', 'admin*') ? 'active' : '' }}"
                   href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item {{ request()->routeIs('userProfile') ? 'active' : '' }}"
                           href="{{ route('userProfile') }}">
                        <i class="bi bi-person-fill me-1"></i>Profilis</a></li>
                    @if(session('admin') >= 1)
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item {{ request()->routeIs('admin*') ? 'active' : '' }}"
                           href="{{ route('admin') }}">
                        <i class="bi bi-database-gear me-1"></i>Admin</a></li>
                    @endif
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item"
                           href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="bi bi-box-arrow-right me-1"></i>Atsijungti</a></li>
                </ul>
            </div>
            </div>{{-- /sb-nav-grp --}}
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
            <img src="{{ asset('img/logo.png') }}" alt="SportBet" style="height:36px;">
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
            <a class="sb-nav-link" href="{{ route('summary.prediction.results') }}">
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
            @if(session('admin') >= 1)
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

