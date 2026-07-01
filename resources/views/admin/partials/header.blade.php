<nav class="sb-navbar">

  <div class="sb-topnav">

    {{-- Brand --}}
    <a class="sb-brand" href="{{ route('admin.index') }}">
      <i class="bi bi-database-gear" style="font-size:1.25rem;flex-shrink:0;"></i>
      <span>Admin</span>
    </a>

    {{-- Desktop: admin nav links --}}
    <div class="sb-topnav-links d-none d-lg-flex">
      @if(session('admin') >= 5)
      <a class="sb-nav-link {{ request()->routeIs('admin.teams') ? 'active' : '' }}"
         href="{{ route('admin.teams') }}">
        <i class="bi bi-flag"></i> Komandos
      </a>
      @endif
      <a class="sb-nav-link {{ request()->routeIs('admin.games') ? 'active' : '' }}"
         href="{{ route('admin.games') }}">
        <i class="bi bi-calendar3"></i> Rungtynės
      </a>
      <a class="sb-nav-link {{ request()->routeIs('admin.results*') ? 'active' : '' }}"
         href="{{ route('admin.resultsAll') }}">
        <i class="bi bi-check2-square"></i> Rezultatai
      </a>
      <a class="sb-nav-link {{ request()->routeIs('admin.leagues*') ? 'active' : '' }}"
         href="{{ route('admin.leagues') }}">
        <i class="bi bi-trophy-fill"></i> Lygos
      </a>
      @if(session('admin') >= 5)
      <a class="sb-nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}"
         href="{{ route('admin.users') }}">
        <i class="bi bi-people"></i> Dalyviai
      </a>
      <a class="sb-nav-link {{ request()->routeIs('admin.messages') ? 'active' : '' }}"
         href="{{ route('admin.messages') }}">
        <i class="bi bi-chat-left-text"></i> Pranešimai
      </a>
      <a class="sb-nav-link {{ request()->routeIs('admin.events') ? 'active' : '' }}"
         href="{{ route('admin.events') }}">
        <i class="bi bi-trophy"></i> Turai
      </a>
      @if(session('admin') >= 9)
      <a class="sb-nav-link {{ request()->routeIs('admin.tournaments*') ? 'active' : '' }}"
         href="{{ route('admin.tournaments') }}">
        <i class="bi bi-globe2"></i> Turnyrai
      </a>
      <a class="sb-nav-link" href="{{ route('admin.updateStandingPoints') }}">
        <i class="bi bi-calculator"></i> Taškai už eigą
      </a>
      @endif
      @endif
    </div>

    {{-- Desktop: right side --}}
    <div class="sb-topnav-right d-none d-lg-flex">
      <button class="sb-theme-btn" onclick="sbToggleTheme()" title="Keisti temą" aria-label="Keisti temą">
        <i class="bi bi-sun-fill sb-theme-sun"></i>
        <i class="bi bi-moon-fill sb-theme-moon"></i>
      </button>
      <div class="dropdown">
        <button class="sb-top-avatar dropdown-toggle" type="button"
                data-bs-toggle="dropdown" aria-expanded="false">
          {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}{{ strtoupper(substr(Auth::user()->surname ?? '', 0, 1)) }}
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="{{ route('userProfile') }}">
              <i class="bi bi-person-fill"></i> Profilis</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="{{ route('main') }}">
              <i class="bi bi-arrow-left-circle"></i> Grįžti į svetainę</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="{{ route('logout') }}"
                 onclick="event.preventDefault(); document.getElementById('admin-logout-form').submit();">
              <i class="bi bi-box-arrow-right"></i> Atsijungti</a></li>
        </ul>
      </div>
      <form id="admin-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
    </div>

    {{-- Mobile: theme + hamburger --}}
    <div class="d-flex d-lg-none align-items-center ms-auto gap-1">
      <button class="sb-theme-btn" onclick="sbToggleTheme()" title="Keisti temą" aria-label="Keisti temą">
        <i class="bi bi-sun-fill sb-theme-sun"></i>
        <i class="bi bi-moon-fill sb-theme-moon"></i>
      </button>
      <button class="sb-toggler" type="button"
              data-bs-toggle="collapse" data-bs-target="#adminNav"
              aria-controls="adminNav" aria-expanded="false" aria-label="Atidaryti meniu">
        <i class="bi bi-list"></i>
      </button>
    </div>

  </div>

  {{-- Mobile collapse panel --}}
  <div class="collapse sb-mobile-collapse d-lg-none w-100" id="adminNav">
    <div class="sb-mobile-group">
      <div class="sb-mobile-label">Admin</div>
      @if(session('admin') >= 5)
      <a class="sb-nav-link {{ request()->routeIs('admin.teams') ? 'active' : '' }}"
         href="{{ route('admin.teams') }}">
        <i class="bi bi-flag"></i> Komandos
      </a>
      @endif
      <a class="sb-nav-link {{ request()->routeIs('admin.games') ? 'active' : '' }}"
         href="{{ route('admin.games') }}">
        <i class="bi bi-calendar3"></i> Rungtynės
      </a>
      <a class="sb-nav-link {{ request()->routeIs('admin.results*') ? 'active' : '' }}"
         href="{{ route('admin.resultsAll') }}">
        <i class="bi bi-check2-square"></i> Rezultatai
      </a>
      <a class="sb-nav-link {{ request()->routeIs('admin.leagues*') ? 'active' : '' }}"
         href="{{ route('admin.leagues') }}">
        <i class="bi bi-trophy-fill"></i> Lygos
      </a>
      @if(session('admin') >= 5)
      <a class="sb-nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}"
         href="{{ route('admin.users') }}">
        <i class="bi bi-people"></i> Dalyviai
      </a>
      <a class="sb-nav-link {{ request()->routeIs('admin.messages') ? 'active' : '' }}"
         href="{{ route('admin.messages') }}">
        <i class="bi bi-chat-left-text"></i> Pranešimai
      </a>
      <a class="sb-nav-link {{ request()->routeIs('admin.events') ? 'active' : '' }}"
         href="{{ route('admin.events') }}">
        <i class="bi bi-trophy"></i> Turai
      </a>
      @if(session('admin') >= 9)
      <a class="sb-nav-link {{ request()->routeIs('admin.tournaments*') ? 'active' : '' }}"
         href="{{ route('admin.tournaments') }}">
        <i class="bi bi-globe2"></i> Turnyrai
      </a>
      <a class="sb-nav-link" href="{{ route('admin.updateStandingPoints') }}">
        <i class="bi bi-calculator"></i> Taškai už eigą
      </a>
      @endif
      @endif
    </div>
    <div class="sb-mobile-group">
      <div class="sb-mobile-label">Paskyra</div>
      <a class="sb-nav-link" href="{{ route('userProfile') }}">
        <i class="bi bi-person-fill"></i> Profilis
      </a>
      <a class="sb-nav-link" href="{{ route('main') }}">
        <i class="bi bi-arrow-left-circle"></i> Grįžti į svetainę
      </a>
      <a class="sb-nav-link" href="{{ route('logout') }}"
         onclick="event.preventDefault(); document.getElementById('admin-logout-form-m').submit();">
        <i class="bi bi-box-arrow-right"></i> Atsijungti
      </a>
      <form id="admin-logout-form-m" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
    </div>
  </div>

</nav>
