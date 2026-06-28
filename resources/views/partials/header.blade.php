
<nav class="sb-navbar">

  {{-- ============================================================
       Single dark topnav bar (all screen sizes)
       ============================================================ --}}
  <div class="sb-topnav">

    {{-- Brand --}}
    <a class="sb-brand" @auth href="{{ route('main') }}" @else href="{{ route('/') }}" @endauth>
      <img src="{{ asset('img/logo.png') }}" alt="SportBet" style="height:32px;">
      <span>Sport<span class="sb-brand-dot">Bet</span></span>
    </a>

    @auth
    {{-- Desktop: nav links ── d-none d-lg-flex --}}
    <div class="sb-topnav-links d-none d-lg-flex">

      <a class="sb-nav-link {{ request()->routeIs('main') ? 'active' : '' }}"
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

    {{-- Desktop: right side ── d-none d-lg-flex --}}
    <div class="sb-topnav-right d-none d-lg-flex">

      {{-- League switcher --}}
      @if(session('userID'))
      <div class="d-flex align-items-center gap-1">
        <span class="sb-nav-label">Lyga</span>
        @include('partials.league-switcher')
      </div>
      @endif

      {{-- Theme toggle --}}
      <button class="sb-theme-btn" onclick="sbToggleTheme()" title="Keisti temą" aria-label="Keisti temą">
        <i class="bi bi-sun-fill sb-theme-sun"></i>
        <i class="bi bi-moon-fill sb-theme-moon"></i>
      </button>

      {{-- Avatar dropdown --}}
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

      <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>

    </div>{{-- /.sb-topnav-right --}}

    {{-- Mobile: hamburger + theme; collapse panel is @auth-only below --}}
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

    @else
    {{-- Guest --}}
    <div class="ms-auto d-flex align-items-center gap-1">
      <button class="sb-theme-btn" onclick="sbToggleTheme()" title="Keisti temą" aria-label="Keisti temą">
        <i class="bi bi-sun-fill sb-theme-sun"></i>
        <i class="bi bi-moon-fill sb-theme-moon"></i>
      </button>
      <a class="sb-nav-pill sb-nav-pill--ghost" style="margin-left:0" href="{{ route('charity') }}"><i class="bi bi-heart-fill" style="font-size:.75rem;"></i> Jaunimo linija</a>
      <a class="sb-nav-pill ms-0" href="{{ route('login') }}">Prisijungti</a>
    </div>
    @endauth

  </div>{{-- /.sb-topnav --}}

  {{-- ============================================================
       Mobile collapse panel
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
