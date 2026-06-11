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
    @php
        $activeLeagueId = session('leagueID');
        $myLeagues = \App\Models\LeagueMember::where('user_id', session('userID'))->with('league')->get();
        $activeLeague = $myLeagues->firstWhere('league_id', $activeLeagueId);
    @endphp
    <div class="dropup">
        <button class="sb-tab dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-trophy" style="font-size:1.2rem;"></i>
            <span class="sb-tab-label">{{ Str::limit($activeLeague?->league->name ?? 'Lyga', 10) }}</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end mb-1">
            @foreach($myLeagues as $membership)
            <li>
                @if($membership->league_id === $activeLeagueId)
                    <span class="dropdown-item active">
                        <i class="bi bi-check2 me-1"></i>{{ $membership->league->name }}
                    </span>
                @else
                    <form method="POST" action="{{ route('leagues.switch') }}">
                        @csrf
                        <input type="hidden" name="leagueID" value="{{ $membership->league_id }}">
                        <button type="submit" class="dropdown-item">{{ $membership->league->name }}</button>
                    </form>
                @endif
            </li>
            @endforeach
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="{{ route('leagues.index') }}">
                <i class="bi bi-gear me-1"></i>Tvarkyti lygą</a></li>
        </ul>
    </div>
</nav>
@endauth
