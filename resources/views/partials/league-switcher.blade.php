@php
    $userId = session('userID');
    $activeLeagueId = session('leagueID');
    $myLeagues = \App\Models\LeagueMember::where('user_id', $userId)->with('league')->get();
    $activeLeague = $myLeagues->firstWhere('league_id', $activeLeagueId);
    $pendingInviteCount = \App\Models\LeagueInvite::where('invited_user_id', $userId)->where('status', 'pending')->count();
@endphp

<div class="dropdown d-inline-block me-2">
  <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button"
          data-bs-toggle="dropdown" aria-expanded="false"
          style="font-size:.8rem; padding:3px 10px; border-radius:20px;">
    {{ $activeLeague?->league->name ?? 'Liga' }}
    @if($pendingInviteCount > 0)
      <span class="badge bg-danger ms-1" style="font-size:.6rem;">{{ $pendingInviteCount }}</span>
    @endif
  </button>
  <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:180px;">
    @foreach($myLeagues as $membership)
    <li>
      @if($membership->league_id === $activeLeagueId)
        <span class="dropdown-item active small">{{ $membership->league->name }}</span>
      @else
        <form method="POST" action="{{ route('leagues.switch') }}" class="d-inline">
          @csrf
          <input type="hidden" name="leagueID" value="{{ $membership->league_id }}">
          <button type="submit" class="dropdown-item small">{{ $membership->league->name }}</button>
        </form>
      @endif
    </li>
    @endforeach
    <li><hr class="dropdown-divider"></li>
    <li>
      <a class="dropdown-item small text-muted" href="{{ route('leagues.index') }}">
        Visos lygos
        @if($pendingInviteCount > 0)
          <span class="badge bg-danger ms-1">{{ $pendingInviteCount }}</span>
        @endif
      </a>
    </li>
  </ul>
</div>
