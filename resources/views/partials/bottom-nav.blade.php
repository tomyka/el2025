@auth
<nav class="sb-bottom-nav d-lg-none">
    <a class="sb-tab {{ request()->routeIs('prediction.results') ? 'active' : '' }}"
       href="{{ route('prediction.results') }}">
        <span class="material-icons" style="font-size:1.4rem;">sports_soccer</span>
        <span class="sb-tab-label">Spėjimai</span>
    </a>
    <a class="sb-tab {{ request()->routeIs('prediction.standings') ? 'active' : '' }}"
       href="{{ route('prediction.standings') }}">
        <i class="bi bi-table" style="font-size:1.2rem;"></i>
        <span class="sb-tab-label">Eiga</span>
    </a>
    @if(session('eventSurvival') == 1 && session('survivalGame') == 1)
    <a class="sb-tab {{ request()->routeIs('prediction.survival') ? 'active' : '' }}"
       href="{{ route('prediction.survival') }}">
        <i class="bi bi-bullseye" style="font-size:1.2rem;"></i>
        <span class="sb-tab-label">Išlikimas</span>
    </a>
    @endif
    <a class="sb-tab {{ request()->routeIs('summary.*') ? 'active' : '' }}"
       href="{{ route('summary.prediction.results') }}">
        <i class="bi bi-file-earmark-bar-graph-fill" style="font-size:1.2rem;"></i>
        <span class="sb-tab-label">Suvestinė</span>
    </a>
</nav>
@endauth
