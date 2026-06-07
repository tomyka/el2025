<nav class="navbar navbar-expand-lg sb-navbar">
    <div class="container-fluid px-3">

        {{-- Brand --}}
        <a class="sb-brand" href="{{ route('admin.index') }}">
            <span class="material-icons" style="font-size:1.2rem;">admin_panel_settings</span>
            Admin
        </a>

        {{-- Mobile toggler --}}
        <button class="navbar-toggler border-0 p-1" type="button"
                data-bs-toggle="collapse" data-bs-target="#adminNav"
                aria-controls="adminNav" aria-expanded="false">
            <i class="bi bi-list text-white" style="font-size:1.4rem;"></i>
        </button>

        <div class="collapse navbar-collapse" id="adminNav">

            {{-- Nav links --}}
            <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center gap-2 gap-lg-3 ms-lg-4 py-2 py-lg-0">
                @if(session('admin') > 5)
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
                @if(session('admin') > 5)
                <a class="sb-nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}"
                   href="{{ route('admin.users') }}">
                    <i class="bi bi-people"></i> Dalyviai
                </a>
                <a class="sb-nav-link {{ request()->routeIs('admin.groups') ? 'active' : '' }}"
                   href="{{ route('admin.groups') }}">
                    <i class="bi bi-diagram-3"></i> Grupės
                </a>
                <a class="sb-nav-link {{ request()->routeIs('admin.userGroups') ? 'active' : '' }}"
                   href="{{ route('admin.userGroups') }}">
                    <i class="bi bi-person-lines-fill"></i> Dalyvių grupės
                </a>
                <a class="sb-nav-link {{ request()->routeIs('admin.messages') ? 'active' : '' }}"
                   href="{{ route('admin.messages') }}">
                    <i class="bi bi-chat-left-text"></i> Pranešimai
                </a>
                <a class="sb-nav-link {{ request()->routeIs('admin.events') ? 'active' : '' }}"
                   href="{{ route('admin.events') }}">
                    <i class="bi bi-trophy"></i> Įvykiai
                </a>
                <a class="sb-nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}"
                   href="{{ route('admin.settings') }}">
                    <i class="bi bi-gear"></i> Nustatymai
                </a>
                <a class="sb-nav-link" href="{{ route('admin.updateStandingPoints') }}">
                    <i class="bi bi-calculator"></i> Taškai už eigą
                </a>
                @endif
            </div>

            {{-- Right side --}}
            <div class="ms-auto d-flex align-items-center gap-3 mt-2 mt-lg-0">
                <a class="sb-nav-link" href="{{ route('main') }}" title="Pagrindinis">
                    <i class="bi bi-house h5 mb-0"></i>
                </a>
                <a class="sb-nav-link" href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('admin-logout-form').submit();">
                    <i class="bi bi-box-arrow-right"></i> Atsijungti
                </a>
                <form id="admin-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
            </div>

        </div>
    </div>
</nav>
