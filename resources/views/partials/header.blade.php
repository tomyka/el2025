
<nav class="sb-navbar">

  {{-- ============================================================
       White top bar — brand + user (all screen sizes)
       ============================================================ --}}
  <div class="sb-top-bar">
    <div class="sb-top-bar-inner">

      {{-- Brand --}}
      <a class="sb-brand" @auth href="{{ route('main') }}" @else href="{{ route('/') }}" @endauth>
        <img src="{{ asset('img/logo.png') }}" alt="SportBet" style="height:32px;">
        <span>Sport<span class="sb-brand-dot">Bet</span></span>
      </a>

      @auth
      {{-- Desktop: username + avatar dropdown --}}
      <div class="sb-top-user d-none d-lg-flex">
        <span class="sb-top-username">{{ Auth::user()->username ?? '' }}</span>
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

      {{-- Mobile: hamburger --}}
      <button class="sb-toggler d-lg-none" type="button"
              data-bs-toggle="collapse" data-bs-target="#sbNavMobile"
              aria-controls="sbNavMobile" aria-expanded="false" aria-label="Atidaryti meniu">
        <i class="bi bi-list"></i>
      </button>

      <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
      @else
      <a class="sb-nav-pill" href="{{ route('login') }}">Prisijungti</a>
      @endauth

    </div>
  </div>

  {{-- ============================================================
       Blue nav bar — all nav links (desktop, auth only)
       ============================================================ --}}
  @auth
  <div class="sb-blue-bar d-none d-lg-block">
    <div class="sb-blue-bar-inner">

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
           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"
           title="Informacija">
          <i class="bi bi-info-circle"></i>
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

      {{-- League switcher (right) --}}
      @if(session('userID'))
      <div class="ms-auto">
        @include('partials.league-switcher')
      </div>
      @endif

    </div>
  </div>

  {{-- ============================================================
       Mobile collapse — blue background
       ============================================================ --}}
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
      @include('partials.league-switcher')
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
