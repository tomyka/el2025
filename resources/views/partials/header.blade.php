
<nav class="sb-navbar">

  {{-- ============================================================
       Single dark topnav bar (all screen sizes)
       ============================================================ --}}
  <div class="sb-topnav">

    {{-- Brand --}}
    <a class="sb-brand" @auth href="{{ route('main') }}" @else href="{{ route('tournaments.hub') }}" @endauth>
      <img src="{{ asset('img/logo.png') }}" alt="SportBet" style="height:32px;">
      <span>Sport<span class="sb-brand-dot">Bet</span></span>
    </a>

    @auth
    {{-- Desktop: nav links ── d-none d-lg-flex --}}
    <div class="sb-topnav-links d-none d-lg-flex">

      <a class="sb-nav-link {{ request()->routeIs('main') ? 'active' : '' }}"
         href="{{ route('main') }}">{{ __('Pradžia') }}</a>

      <a class="sb-nav-link {{ request()->routeIs('prediction.results') ? 'active' : '' }}"
         href="{{ route('prediction.results') }}">
        <span class="material-icons" style="font-size:1rem;vertical-align:middle;">sports_soccer</span> {{ __('Spėjimai') }}
      </a>

      <a class="sb-nav-link {{ request()->routeIs('prediction.standings') ? 'active' : '' }}"
         href="{{ route('prediction.standings') }}">
        <i class="bi bi-table"></i> {{ __('Eiga') }}
      </a>

      @if(session('eventSurvival') == 1 && session('survivalGame') == 1)
      <a class="sb-nav-link {{ request()->routeIs('prediction.survival') ? 'active' : '' }}"
         href="{{ route('prediction.survival') }}">
        <i class="bi bi-bullseye"></i> {{ __('Išlikimas') }}
      </a>
      @endif

      <span class="sb-nav-sep"></span>

      @if(session('disabled') != '')
      <div class="dropdown">
        <a class="sb-nav-link {{ request()->routeIs('summary.*') ? 'active' : '' }} dropdown-toggle"
           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-bar-chart-line"></i> {{ __('Suvestinė') }}
        </a>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item {{ request()->routeIs('summary.prediction.results') ? 'active' : '' }}"
                 href="{{ route('summary.prediction.results') }}">
              <i class="bi bi-list-check"></i> {{ __('Prognozės') }}</a></li>
          <li><a class="dropdown-item {{ request()->routeIs('summary.prediction.standings') ? 'active' : '' }}"
                 href="{{ route('summary.prediction.standings') }}">
              <i class="bi bi-table"></i> {{ __('Eiga') }}</a></li>
          @if(session('survivalGame') != 0)
          <li><a class="dropdown-item {{ request()->routeIs('summary.prediction.survivals') ? 'active' : '' }}"
                 href="{{ route('summary.prediction.survivals') }}">
              <i class="bi bi-shield-check"></i> {{ __('Išlikimas') }}</a></li>
          @endif
          <li><a class="dropdown-item {{ request()->routeIs('summary.chart') ? 'active' : '' }}"
                 href="{{ route('summary.chart') }}">
              <i class="bi bi-bar-chart-line"></i> {{ __('Grafikas') }}</a></li>
        </ul>
      </div>
      @endif

      <div class="dropdown">
        <a class="sb-nav-link {{ request()->routeIs('rules','help','charity','privacy') ? 'active' : '' }} dropdown-toggle"
           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-info-circle"></i> {{ __('Informacija') }}
        </a>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item {{ request()->routeIs('rules') ? 'active' : '' }}"
                 href="{{ route('rules') }}">
              <i class="bi bi-journal-text"></i> {{ __('Taisyklės') }}</a></li>
          <li><a class="dropdown-item {{ request()->routeIs('help') ? 'active' : '' }}"
                 href="{{ route('help') }}">
              <i class="bi bi-question-circle"></i> {{ __('Pagalba') }}</a></li>
          <li><a class="dropdown-item {{ request()->routeIs('charity') ? 'active' : '' }}"
                 href="{{ route('charity') }}">
              <i class="bi bi-heart"></i> {{ __('Jaunimo linija') }}</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item {{ request()->routeIs('privacy') ? 'active' : '' }}"
                 href="{{ route('privacy') }}">
              <i class="bi bi-shield-lock"></i> {{ __('Privatumas') }}</a></li>
        </ul>
      </div>

      <span class="sb-nav-sep"></span>

      <a class="sb-nav-link {{ request()->routeIs('leagues.*') ? 'active' : '' }}"
         href="{{ route('leagues.index') }}">
        <i class="bi bi-trophy"></i> {{ __('Lygos') }}
      </a>

    </div>{{-- /.sb-topnav-links --}}

    {{-- Desktop: right side ── d-none d-lg-flex --}}
    <div class="sb-topnav-right d-none d-lg-flex">

      @if(session('tournamentID'))
      <a href="{{ route('tournaments.exit') }}"
         class="sb-nav-pill sb-nav-pill--ghost"
         style="font-size:.8rem;margin-right:4px">
        {{ __('← Turnyrai') }}
      </a>
      @endif

      {{-- League switcher --}}
      @if(session('userID'))
      <div class="d-flex align-items-center gap-1">
        <span class="sb-nav-label">{{ __('Lyga') }}</span>
        @include('partials.league-switcher')
      </div>
      @endif

      {{-- Theme toggle --}}
      <button class="sb-theme-btn" onclick="sbToggleTheme()" title="{{ __('Keisti temą') }}" aria-label="{{ __('Keisti temą') }}">
        <i class="bi bi-sun-fill sb-theme-sun"></i>
        <i class="bi bi-moon-fill sb-theme-moon"></i>
      </button>

      {{-- Locale toggle --}}
      <div class="d-flex align-items-center" style="gap:2px">
          <form method="POST" action="{{ route('locale.update') }}" class="d-inline">
              @csrf
              <input type="hidden" name="locale" value="lt">
              <button type="submit" class="sb-locale-btn {{ app()->getLocale() === 'lt' ? 'active' : '' }}">LT</button>
          </form>
          <form method="POST" action="{{ route('locale.update') }}" class="d-inline">
              @csrf
              <input type="hidden" name="locale" value="en">
              <button type="submit" class="sb-locale-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</button>
          </form>
      </div>

      {{-- Avatar dropdown --}}
      <div class="dropdown">
        <button class="sb-top-avatar dropdown-toggle" type="button"
                data-bs-toggle="dropdown" aria-expanded="false">
          {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}{{ strtoupper(substr(Auth::user()->surname ?? '', 0, 1)) }}
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item {{ request()->routeIs('userProfile') ? 'active' : '' }}"
                 href="{{ route('userProfile') }}">
              <i class="bi bi-person-fill"></i>{{ __('Profilis') }}</a></li>
          @if(session('admin') >= 1)
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item {{ request()->routeIs('admin*') ? 'active' : '' }}"
                 href="{{ route('admin') }}">
              <i class="bi bi-database-gear"></i>{{ __('Admin') }}</a></li>
          @endif
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="{{ route('logout') }}"
                 onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
              <i class="bi bi-box-arrow-right"></i>{{ __('Atsijungti') }}</a></li>
        </ul>
      </div>

      <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>

    </div>{{-- /.sb-topnav-right --}}

    {{-- Mobile: hamburger + theme; collapse panel is @auth-only below --}}
    <div class="d-flex d-lg-none align-items-center ms-auto gap-1">
      <button class="sb-theme-btn" onclick="sbToggleTheme()" title="{{ __('Keisti temą') }}" aria-label="{{ __('Keisti temą') }}">
        <i class="bi bi-sun-fill sb-theme-sun"></i>
        <i class="bi bi-moon-fill sb-theme-moon"></i>
      </button>
      <button class="sb-toggler" type="button"
              data-bs-toggle="collapse" data-bs-target="#sbNavMobile"
              aria-controls="sbNavMobile" aria-expanded="false" aria-label="{{ __('Atidaryti meniu') }}">
        <i class="bi bi-list"></i>
      </button>
    </div>

    @else
    {{-- Guest --}}
    <div class="ms-auto d-flex align-items-center gap-1">
      <button class="sb-theme-btn" onclick="sbToggleTheme()" title="{{ __('Keisti temą') }}" aria-label="{{ __('Keisti temą') }}">
        <i class="bi bi-sun-fill sb-theme-sun"></i>
        <i class="bi bi-moon-fill sb-theme-moon"></i>
      </button>
      {{-- Locale toggle --}}
      <div class="d-flex align-items-center" style="gap:2px">
          <form method="POST" action="{{ route('locale.update') }}" class="d-inline">
              @csrf
              <input type="hidden" name="locale" value="lt">
              <button type="submit" class="sb-locale-btn {{ app()->getLocale() === 'lt' ? 'active' : '' }}">LT</button>
          </form>
          <form method="POST" action="{{ route('locale.update') }}" class="d-inline">
              @csrf
              <input type="hidden" name="locale" value="en">
              <button type="submit" class="sb-locale-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</button>
          </form>
      </div>
      <a class="sb-nav-pill sb-nav-pill--ghost" style="margin-left:0" href="{{ route('leaderboard') }}"><i class="bi bi-trophy-fill" style="font-size:.75rem;"></i><span class="d-none d-sm-inline"> {{ __('Lyderiai') }}</span></a>
      <a class="sb-nav-pill sb-nav-pill--ghost" style="margin-left:0" href="{{ route('charity') }}"><i class="bi bi-heart-fill" style="font-size:.75rem;"></i><span class="d-none d-sm-inline"> {{ __('Jaunimo linija') }}</span></a>
      <a class="sb-nav-pill ms-0" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right" style="font-size:.75rem;"></i><span class="d-none d-sm-inline"> {{ __('Prisijungti') }}</span></a>
    </div>
    @endauth

  </div>{{-- /.sb-topnav --}}

  {{-- ============================================================
       Mobile collapse panel
       ============================================================ --}}
  @auth
  <div class="collapse sb-mobile-collapse d-lg-none w-100" id="sbNavMobile">

    @if(session('tournamentID'))
    <div class="sb-mobile-group">
      <a class="sb-nav-link" href="{{ route('tournaments.exit') }}">
        <i class="bi bi-arrow-left"></i> {{ __('← Turnyrai') }}
      </a>
    </div>
    @endif

    <div class="sb-mobile-group">
      <div class="sb-mobile-label">{{ __('Spėjimai') }}</div>
      <a class="sb-nav-link {{ request()->routeIs('prediction.results') ? 'active' : '' }}"
         href="{{ route('prediction.results') }}">
        <span class="material-icons" style="font-size:1rem;vertical-align:middle;">sports_soccer</span> {{ __('Rungtynių spėjimai') }}
      </a>
      <a class="sb-nav-link {{ request()->routeIs('prediction.standings') ? 'active' : '' }}"
         href="{{ route('prediction.standings') }}">
        <i class="bi bi-table"></i> {{ __('Turnyro eiga') }}
      </a>
      @if(session('eventSurvival') == 1 && session('survivalGame') == 1)
      <a class="sb-nav-link {{ request()->routeIs('prediction.survival') ? 'active' : '' }}"
         href="{{ route('prediction.survival') }}">
        <i class="bi bi-bullseye"></i> {{ __('Išlikimas') }}
      </a>
      @endif
    </div>

    @if(session('disabled') != '')
    <div class="sb-mobile-group">
      <div class="sb-mobile-label">{{ __('Suvestinė') }}</div>
      <a class="sb-nav-link {{ request()->routeIs('summary.prediction.results') ? 'active' : '' }}"
         href="{{ route('summary.prediction.results') }}">
        <i class="bi bi-list-check"></i> {{ __('Prognozės') }}
      </a>
      <a class="sb-nav-link {{ request()->routeIs('summary.prediction.standings') ? 'active' : '' }}"
         href="{{ route('summary.prediction.standings') }}">
        <i class="bi bi-table"></i> {{ __('Eiga') }}
      </a>
      @if(session('survivalGame') != 0)
      <a class="sb-nav-link {{ request()->routeIs('summary.prediction.survivals') ? 'active' : '' }}"
         href="{{ route('summary.prediction.survivals') }}">
        <i class="bi bi-shield-check"></i> {{ __('Išlikimas') }}
      </a>
      @endif
      <a class="sb-nav-link {{ request()->routeIs('summary.chart') ? 'active' : '' }}"
         href="{{ route('summary.chart') }}">
        <i class="bi bi-bar-chart-line"></i> {{ __('Grafikas') }}
      </a>
    </div>
    @endif

    <div class="sb-mobile-group">
      <div class="sb-mobile-label">{{ __('Informacija') }}</div>
      <a class="sb-nav-link {{ request()->routeIs('rules') ? 'active' : '' }}"
         href="{{ route('rules') }}">
        <i class="bi bi-journal-text"></i> {{ __('Taisyklės') }}
      </a>
      <a class="sb-nav-link {{ request()->routeIs('help') ? 'active' : '' }}"
         href="{{ route('help') }}">
        <i class="bi bi-question-circle"></i> {{ __('Pagalba') }}
      </a>
      <a class="sb-nav-link {{ request()->routeIs('charity') ? 'active' : '' }}"
         href="{{ route('charity') }}">
        <i class="bi bi-heart"></i> {{ __('Jaunimo linija') }}
      </a>
      <a class="sb-nav-link {{ request()->routeIs('privacy') ? 'active' : '' }}"
         href="{{ route('privacy') }}">
        <i class="bi bi-shield-lock"></i> {{ __('Privatumas') }}
      </a>
    </div>

    @if(session('userID'))
    <div class="sb-mobile-group">
      <div class="sb-mobile-label">{{ __('Lyga') }}</div>
      <a class="sb-nav-link {{ request()->routeIs('leagues.*') ? 'active' : '' }}"
         href="{{ route('leagues.index') }}">
        <i class="bi bi-gear"></i> {{ __('Tvarkyti lygą') }}
      </a>
    </div>
    @endif

    <div class="sb-mobile-group">
      <div class="sb-mobile-label">{{ __('Paskyra') }}</div>
      {{-- Locale toggle (mobile) --}}
      <div class="d-flex gap-2 px-2 py-2">
          <form method="POST" action="{{ route('locale.update') }}">
              @csrf
              <input type="hidden" name="locale" value="lt">
              <button type="submit" class="sb-locale-btn {{ app()->getLocale() === 'lt' ? 'active' : '' }}">LT</button>
          </form>
          <form method="POST" action="{{ route('locale.update') }}">
              @csrf
              <input type="hidden" name="locale" value="en">
              <button type="submit" class="sb-locale-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</button>
          </form>
      </div>
      <a class="sb-nav-link {{ request()->routeIs('userProfile') ? 'active' : '' }}"
         href="{{ route('userProfile') }}">
        <i class="bi bi-person-fill"></i> {{ __('Profilis') }}
      </a>
      @if(session('admin') >= 1)
      <a class="sb-nav-link {{ request()->routeIs('admin*') ? 'active' : '' }}"
         href="{{ route('admin') }}">
        <i class="bi bi-database-gear"></i> {{ __('Admin') }}
      </a>
      @endif
      <a class="sb-nav-link" href="{{ route('logout') }}"
         onclick="event.preventDefault(); document.getElementById('logout-form-m').submit();">
        <i class="bi bi-box-arrow-right"></i> {{ __('Atsijungti') }}
      </a>
      <form id="logout-form-m" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
    </div>

  </div>
  @endauth

</nav>
