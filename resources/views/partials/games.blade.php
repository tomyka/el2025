<div class="sb-card">
    <div class="sb-card-title">🗓 Artimiausios rungtynės</div>
    <div class="match-grid">
        @foreach($predictionGames as $predictionGame)
        @php $g = $predictionGame['gameDetails']; @endphp
        <div class="match-card">
            <div class="match-card-header">
                <span>#{{ $g->id }}</span>
                @if($g->full_points !== null)
                <a href="#" class="match-pts match-pts-popover"
                   data-bs-toggle="popover" data-bs-html="true" data-bs-trigger="hover focus"
                   data-bs-content="<div style='font-size:.8rem;min-width:160px'><div class='d-flex justify-content-between gap-3'><span>Nugalėtojas:</span><strong>{{ number_format($g->winner_points,2) }}</strong></div><div class='d-flex justify-content-between gap-3'><span>Skirtumas:</span><strong>{{ number_format($g->difference_points,2) }}</strong></div><div class='d-flex justify-content-between gap-3'><span>Bingo:</span><strong>{{ number_format($g->bingo_points,2) }}</strong></div><div class='d-flex justify-content-between gap-3'><span>Koeficientas:</span><strong>{{ number_format($g->odds_points,2) }}</strong></div></div>"
                   data-bs-original-title="Rungtynių taškai">{{ $g->full_points }} pts</a>
                @endif
            </div>
            <div class="match-card-body">
                <div class="match-team match-home">
                    <img class="match-flag"
                         src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($g->home_team)) . '.svg') }}"
                         alt="{{ $g->home_team }}">
                    <span class="match-team-name">{{ $g->home_team }}</span>
                </div>
                <div class="match-vs">
                    @if($g->home_team_score !== null)
                    <div class="match-score-actual">{{ $g->home_team_score }} : {{ $g->away_team_score }}</div>
                    @else
                    <div class="match-score-actual match-no-score">VS</div>
                    @endif
                    <div class="match-score-pred">
                        {{ $g->p_home_team_score ?? '?' }} : {{ $g->p_away_team_score ?? '?' }}
                    </div>
                </div>
                <div class="match-team match-away">
                    <span class="match-team-name">{{ $g->away_team }}</span>
                    <img class="match-flag"
                         src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($g->away_team)) . '.svg') }}"
                         alt="{{ $g->away_team }}">
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
