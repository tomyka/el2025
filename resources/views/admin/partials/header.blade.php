<nav class="sb-navbar">

  {{-- White top bar --}}
  <div class="sb-top-bar">
    <div class="sb-top-bar-inner">

      <a class="sb-brand" href="{{ route('admin.index') }}">
        <span class="material-icons sb-brand-dot" style="font-size:1.2rem;">admin_panel_settings</span>
        Admin
      </a>

      {{-- Desktop: avatar dropdown --}}
      <div class="sb-top-user d-none d-lg-flex">
        <span class="sb-top-username">{{ Auth::user()->username ?? '' }}</span>
        <div class="dropdown">
          <button class="sb-top-avatar dropdown-toggle" type="button"
                  data-bs-toggle="dropdown" aria-expanded="false">
            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}{{ strtoupper(substr(Auth::user()->surname ?? '', 0, 1)) }}
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="{{ route('userProfile') }}">
                <i class="bi bi-person-fill"></i>Profilis</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="{{ route('main') }}">
                <i class="bi bi-arrow-left-circle"></i>Grįžti į svetainę</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('admin-logout-form').submit();">
                <i class="bi bi-box-arrow-right"></i>Atsijungti</a></li>
          </ul>
        </div>
      </div>

      {{-- Mobile: hamburger --}}
      <button class="sb-toggler d-lg-none" type="button"
              data-bs-toggle="collapse" data-bs-target="#adminNav"
              aria-controls="adminNav" aria-expanded="false">
        <i class="bi bi-list"></i>
      </button>

      <form id="admin-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
    </div>
  </div>

  {{-- Blue nav bar (desktop) --}}
  <div class="sb-blue-bar d-none d-lg-block">
    <div class="sb-blue-bar-inner">

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
        <i class="bi bi-trophy"></i> Įvykiai
      </a>
      @if(session('admin') >= 9)
      <a class="sb-nav-link" href="{{ route('admin.updateStandingPoints') }}">
        <i class="bi bi-calculator"></i> Taškai už eigą
      </a>
      @endif
      @endif

    </div>
  </div>

  {{-- Mobile collapse --}}
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
        <i class="bi bi-trophy"></i> Įvykiai
      </a>
      @if(session('admin') >= 9)
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
