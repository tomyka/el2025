@php
    $userId = session('userID');
    $activeLeagueId = session('leagueID');
    $myLeagues = \App\Models\LeagueMember::where('user_id', $userId)->with('league')->get();
    $activeLeague = $myLeagues->firstWhere('league_id', $activeLeagueId);
    $pendingInviteCount = \App\Models\LeagueInvite::where('invited_user_id', $userId)->where('status', 'pending')->count();
@endphp

<div class="dropdown">
  <a class="sb-nav-link {{ request()->routeIs('leagues.*') ? 'active' : '' }} dropdown-toggle"
     href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="bi bi-trophy"></i> Lygos
    @if($pendingInviteCount > 0)
      <span class="badge bg-danger ms-1" style="font-size:.55rem;padding:2px 5px;">{{ $pendingInviteCount }}</span>
    @endif
  </a>
  <ul class="dropdown-menu" style="min-width:180px;">
    @foreach($myLeagues as $membership)
    <li>
      @if($membership->league_id === $activeLeagueId)
        <span class="dropdown-item active">
          <i class="bi bi-check2 me-1"></i>{{ $membership->league->name }}
        </span>
      @else
        <form method="POST" action="{{ route('leagues.switch') }}" class="d-inline">
          @csrf
          <input type="hidden" name="leagueID" value="{{ $membership->league_id }}">
          <button type="submit" class="dropdown-item">{{ $membership->league->name }}</button>
        </form>
      @endif
    </li>
    @endforeach
    <li><hr class="dropdown-divider"></li>
    <li>
      <a class="dropdown-item" href="{{ route('leagues.index') }}">
        <i class="bi bi-gear me-1"></i>Tvarkyti lygą
        @if($pendingInviteCount > 0)
          <span class="badge bg-danger ms-1">{{ $pendingInviteCount }}</span>
        @endif
      </a>
    </li>
  </ul>
</div>
