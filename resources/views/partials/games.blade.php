<div class="sb-card">
    <div class="sb-card-title d-flex justify-content-between align-items-center">
        <span><i class="bi bi-calendar3"></i> Artimiausios rungtynės</span>
        <a href="{{ route('prediction.results') }}" class="upcoming-all-link">Visi spėjimai <i class="bi bi-arrow-right-short"></i></a>
    </div>
    <div class="upcoming-list">
        @foreach($predictionGames as $predictionGame)
        @php
            $g       = $predictionGame['gameDetails'];
            $played  = $g->home_team_score !== null;
        @endphp
        <a href="{{ route('prediction.results') }}" class="upcoming-row">
            <span class="upcoming-date">
                <span>{{ \Carbon\Carbon::parse($g->game_date)->format('d.m') }}</span>
                <span class="upcoming-time">{{ \Carbon\Carbon::parse($g->game_date)->format('H:i') }}</span>
            </span>

            <span class="upcoming-team upcoming-home">
                <img src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($g->home_team)) . '.svg') }}"
                     class="upcoming-flag" alt="{{ $g->home_team }}">
                <span class="upcoming-name d-none d-md-inline">{{ $g->home_team }}</span>
            </span>

            <span class="upcoming-scores">
                @if($played)
                    <span class="usc-actual">{{ $g->home_team_score }}:{{ $g->away_team_score }}</span>
                    <span class="usc-sep">/</span>
                @endif
                <span class="usc-pred {{ $played ? '' : 'usc-pred-only' }}">{{ $g->p_home_team_score ?? '?' }}:{{ $g->p_away_team_score ?? '?' }}</span>
            </span>

            <span class="upcoming-team upcoming-away">
                <span class="upcoming-name d-none d-md-inline">{{ $g->away_team }}</span>
                <img src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($g->away_team)) . '.svg') }}"
                     class="upcoming-flag" alt="{{ $g->away_team }}">
            </span>

            <span class="upcoming-pts {{ $played && $g->full_points > 0 ? 'upt-scored' : 'upt-empty' }}">
                {{ $played ? number_format($g->full_points, 1) : '' }}
            </span>
        </a>
        @endforeach
    </div>
</div>
