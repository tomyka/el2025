<nav class="navbar sb-navbar">

    {{-- Desktop: 3-column grid — brand | links | profile  (lg+) --}}
    <div class="sb-desktop-nav d-none d-lg-grid">

        {{-- Left: brand --}}
        <div class="sb-nav-start">
            <a class="sb-brand" href="{{ route('admin.index') }}">
                <span class="material-icons sb-brand-dot" style="font-size:1.2rem;">admin_panel_settings</span>
                Admin
            </a>
        </div>

        {{-- Center: admin nav links --}}
        <div class="sb-nav-center sb-admin-center">
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
            <a class="sb-nav-link {{ request()->routeIs('admin.resultsAll') ? 'active' : '' }}"
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

        {{-- Right: profile dropdown --}}
        <div class="sb-nav-end">
            <div class="dropdown">
                <a class="sb-nav-link dropdown-toggle"
                   href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"
                   style="font-size:1.3rem; padding:4px 8px;">
                    <i class="bi bi-person-circle"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('userProfile') }}">
                        <i class="bi bi-person-fill me-1"></i>Profilis</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('main') }}">
                        <i class="bi bi-arrow-left-circle me-1"></i>Grįžti į svetainę</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item"
                           href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('admin-logout-form').submit();">
                        <i class="bi bi-box-arrow-right me-1"></i>Atsijungti</a></li>
                </ul>
            </div>
            <form id="admin-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
        </div>

    </div>

    {{-- Mobile: brand + hamburger --}}
    <div class="sb-mobile-bar d-lg-none">
        <a class="sb-brand" href="{{ route('admin.index') }}">
            <span class="material-icons sb-brand-dot" style="font-size:1.2rem;">admin_panel_settings</span>
            Admin
        </a>
        <button class="sb-toggler ms-auto" type="button"
                data-bs-toggle="collapse" data-bs-target="#adminNav"
                aria-controls="adminNav" aria-expanded="false">
            <i class="bi bi-list"></i>
        </button>
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
            <a class="sb-nav-link {{ request()->routeIs('admin.resultsAll') ? 'active' : '' }}"
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
            <a class="sb-nav-link"
               href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('admin-logout-form-m').submit();">
                <i class="bi bi-box-arrow-right"></i> Atsijungti
            </a>
            <form id="admin-logout-form-m" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
        </div>
    </div>

</nav>
