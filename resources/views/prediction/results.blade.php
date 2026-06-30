@extends('layouts.master')
@section('content')

@auth
    @if($events->count() > 1)
    <div class="d-flex justify-content-end mb-2">
        <select class="form-select form-select-sm"
                style="width:auto;font-size:.78rem;border-radius:8px;border-color:var(--sb-border);"
                onchange="window.location='{{ route('prediction.results') }}' + (this.value ? '?event=' + this.value : '')">
            <option value="" {{ $selectedEvent == 0 ? 'selected' : '' }}>Visi etapai</option>
            @foreach($events as $id => $name)
            <option value="{{ $id }}" {{ $selectedEvent == $id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
    </div>
    @endif
    @if($groupedResults->isEmpty())
        <p class="text-center text-muted py-4">Nėra rungtynių.</p>
    @else
    <div class="pred-page">
        @foreach($groupedResults as $eventDay => $groupGroups)
        @php $eventName = $groupGroups->first()->first()->event_name; @endphp
        <div class="pred-event">
            <div class="pred-event-header">{{ $eventName }}</div>
            <div class="pred-event-groups">
                @foreach($groupGroups as $groupName => $games)
                <div class="pred-day-card">
                    <div class="pred-day-header">
                        <span>{{ ucfirst(\Carbon\Carbon::parse($groupName)->locale('lt')->isoFormat('MMMM D')) }}</span>
                    </div>
                    @foreach($games as $game)
                    @php
                        $hasResult     = $game->actual_home_score !== null;
                        $totalPts      = $hasResult ? (float)$game->full_points + (float)$game->streak_bonus : 0.0;
                        $streak        = $hasResult ? (float)$game->streak_bonus : 0.0;
                        $isPenaltyDraw = $game->is_knockout && !$game->locked
                            && $game->home_team_score !== null && $game->away_team_score !== null
                            && (string)$game->home_team_score === (string)$game->away_team_score;
                        $gameTime = \Carbon\Carbon::parse($game->game_date, 'UTC')->setTimezone('Europe/Vilnius')->format('H:i');
                        $popContent = '';
                        if ($hasResult && $totalPts > 0) {
                            $popContent = "<div class='sr-pop'>"
                                . "<div class='sr-pop-row'><span>Nugalėtojas</span><strong>" . number_format($game->winner_points, 1) . "</strong></div>"
                                . "<div class='sr-pop-row'><span>Skirtumas</span><strong>" . number_format($game->difference_points, 1) . "</strong></div>"
                                . "<div class='sr-pop-row'><span>Tikslus</span><strong>" . number_format($game->bingo_points, 1) . "</strong></div>"
                                . ($streak > 0 ? "<div class='sr-pop-row'><span>Serija</span><strong>+" . number_format($streak, 1) . "</strong></div>" : "")
                                . "</div>";
                        }
                    @endphp

                    @if($hasResult)
                    {{-- Finished game: same row style as Artimiausios rungtynės --}}
                    <div class="upcoming-row" style="padding-left:0;padding-right:0;">
                        <span class="upcoming-date">{{ $gameTime }}</span>
                        <span class="upcoming-team upcoming-home">
                            <img src="{{ asset('img/teams/'.str_replace(' ','%20',strtolower($game->home_team)).'.svg') }}" class="upcoming-flag" alt="{{ $game->home_team }}">
                            <span class="upcoming-name d-none d-md-inline">{{ $game->home_team }}</span>
                        </span>
                        <span class="upcoming-scores">
                            <span class="usc-actual">{{ $game->actual_home_score }}:{{ $game->actual_away_score }}</span>
                            <span class="usc-sep">/</span>
                            <span class="usc-pred">{{ $game->home_team_score ?? '?' }}:{{ $game->away_team_score ?? '?' }}</span>
                        </span>
                        <span class="upcoming-team upcoming-away">
                            <span class="upcoming-name d-none d-md-inline">{{ $game->away_team }}</span>
                            <img src="{{ asset('img/teams/'.str_replace(' ','%20',strtolower($game->away_team)).'.svg') }}" class="upcoming-flag" alt="{{ $game->away_team }}">
                        </span>
                        <span class="upcoming-pts {{ $totalPts > 0 ? 'upt-scored' : 'upt-empty' }}"
                            @if($totalPts > 0)
                                data-bs-toggle="popover"
                                data-bs-trigger="hover"
                                data-bs-html="true"
                                data-bs-placement="left"
                                data-bs-content="{{ $popContent }}"
                            @endif
                        >
                            {{ number_format($totalPts, 1) }}
                            @if($streak > 0)
                            <span class="upt-streak"><i class="bi bi-fire"></i>+{{ number_format($streak, 1) }}</span>
                            @endif
                        </span>
                    </div>

                    @else
                    {{-- Upcoming / started-no-result game: editable pred-game row --}}
                    <div class="pred-game {{ $game->locked ? 'pred-game-locked' : '' }}">
                        <input type="hidden" id="prediction_gameID{{$game->game_id}}" value="{{ $game->id }}">
                        <input type="hidden" id="penalty-winner-{{$game->game_id}}" value="{{ $game->game_winner_id ?? '' }}">
                        <span class="pred-time">{{ $gameTime }}</span>
                        <div class="pred-team-home">
                            <img src="{{ URL::to('img/teams/'.str_replace(' ','%20',strtolower($game->home_team)).'.svg') }}" class="pred-flag" alt="{{ $game->home_team }}">
                            <span class="pred-team-name">{{ $game->home_team }}</span>
                            @if($game->is_knockout && !$game->locked)
                            <button type="button"
                                    class="pred-pw-check {{ ($isPenaltyDraw && $game->game_winner_id == $game->home_team_id) ? 'active' : '' }}"
                                    id="pred-pw-home-{{$game->game_id}}"
                                    style="{{ $isPenaltyDraw ? '' : 'display:none' }}"
                                    onclick="setPenaltyWinner({{$game->game_id}}, {{$game->home_team_id}}, this)">
                                <i class="bi bi-check-circle-fill"></i>
                            </button>
                            @endif
                        </div>
                        <div class="pred-scores">
                            <div class="pred-scores-inputs">
                                <input type="text" class="form-control pred-score {{ $game->locked ? 'pred-score-locked' : '' }}"
                                    id="homeTeamScore{{$game->game_id}}"
                                    {{ $game->locked ? 'disabled' : 'oninput=checkPrediction('.$game->game_id.')' }}
                                    value="{{ $game->home_team_score }}" maxlength="2" autocomplete="off">
                                <span class="pred-sep">:</span>
                                <input type="text" class="form-control pred-score {{ $game->locked ? 'pred-score-locked' : '' }}"
                                    id="awayTeamScore{{$game->game_id}}"
                                    {{ $game->locked ? 'disabled' : 'oninput=checkPrediction('.$game->game_id.')' }}
                                    value="{{ $game->away_team_score }}" maxlength="2" autocomplete="off">
                            </div>
                        </div>
                        <div class="pred-team-away">
                            @if($game->is_knockout && !$game->locked)
                            <button type="button"
                                    class="pred-pw-check {{ ($isPenaltyDraw && $game->game_winner_id == $game->away_team_id) ? 'active' : '' }}"
                                    id="pred-pw-away-{{$game->game_id}}"
                                    style="{{ $isPenaltyDraw ? '' : 'display:none' }}"
                                    onclick="setPenaltyWinner({{$game->game_id}}, {{$game->away_team_id}}, this)">
                                <i class="bi bi-check-circle-fill"></i>
                            </button>
                            @endif
                            <span class="pred-team-name">{{ $game->away_team }}</span>
                            <img src="{{ URL::to('img/teams/'.str_replace(' ','%20',strtolower($game->away_team)).'.svg') }}" class="pred-flag" alt="{{ $game->away_team }}">
                        </div>
                        <span></span>
                    </div>
                    @php
                        $hasOdds    = ($game->home_odds > 0 || $game->draw_odds > 0 || $game->away_odds > 0);
                        $homeWinPts = round((1 + (float)$game->home_odds) * 5.0 * (float)$game->rate, 1);
                        $drawPts    = round((1 + (float)$game->draw_odds) * 5.0 * (float)$game->rate, 1);
                        $awayWinPts = round((1 + (float)$game->away_odds) * 5.0 * (float)$game->rate, 1);
                    @endphp
                    @if(!$game->locked && $hasOdds)
                    <div class="pred-odds-wrap">
                        <button class="pred-odds-toggle"
                                onclick="var p=this.nextElementSibling;p.classList.toggle('d-none');this.classList.toggle('active')">
                            <i class="bi bi-graph-up-arrow"></i> koef.
                        </button>
                        <div class="pred-odds-panel d-none">
                            <div class="pred-odds-col">
                                <span class="pred-odds-label">{{ $game->home_team }}</span>
                                <span class="pred-odds-pts">+{{ number_format($homeWinPts, 1) }} pt</span>
                            </div>
                            <div class="pred-odds-col">
                                <span class="pred-odds-label">Lygiosios</span>
                                <span class="pred-odds-pts">+{{ number_format($drawPts, 1) }} pt</span>
                            </div>
                            <div class="pred-odds-col">
                                <span class="pred-odds-label">{{ $game->away_team }}</span>
                                <span class="pred-odds-pts">+{{ number_format($awayWinPts, 1) }} pt</span>
                            </div>
                        </div>
                    </div>
                    @endif
                    @endif

                    @endforeach
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
    @endif
@else
    @include('welcome')
@endauth

@endsection

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
        new bootstrap.Popover(el, { html: true });
    });
});

function checkPrediction(gameID) {
    var homeScore    = document.getElementById('homeTeamScore' + gameID);
    var awayScore    = document.getElementById('awayTeamScore' + gameID);
    var predictionID = document.getElementById('prediction_gameID' + gameID);
    var penWinner    = document.getElementById('penalty-winner-' + gameID);
    var pwHome       = document.getElementById('pred-pw-home-' + gameID);
    var pwAway       = document.getElementById('pred-pw-away-' + gameID);

    var homeVal    = homeScore.value.trim();
    var awayVal    = awayScore.value.trim();
    var bothFilled = homeVal !== '' && awayVal !== '';
    var bothEmpty  = homeVal === '' && awayVal === '';
    var isDraw     = bothFilled && homeVal === awayVal;

    if (pwHome) pwHome.style.display = isDraw ? '' : 'none';
    if (pwAway) pwAway.style.display = isDraw ? '' : 'none';
    if (!isDraw && penWinner) {
        penWinner.value = '';
        if (pwHome) pwHome.classList.remove('active');
        if (pwAway) pwAway.classList.remove('active');
    }

    if (!bothFilled && !bothEmpty) return;
    if (pwHome && isDraw && penWinner && !penWinner.value) return;

    $.ajax({
        type: 'POST',
        url: '{{ route("prediction.results") }}',
        data: {
            prediction_gameID: predictionID.value,
            gameID: gameID,
            homeTeamScore: homeVal,
            awayTeamScore: awayVal,
            gameWinnerID: penWinner ? penWinner.value : ''
        },
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    }).done(function(data) {
        var color = bothFilled ? '#22c55e' : '#cbd5e1';
        homeScore.style.borderColor = color;
        awayScore.style.borderColor = color;
    });
}

function setPenaltyWinner(gameID, teamID, btn) {
    var penWinner = document.getElementById('penalty-winner-' + gameID);
    penWinner.value = teamID;
    var pwHome = document.getElementById('pred-pw-home-' + gameID);
    var pwAway = document.getElementById('pred-pw-away-' + gameID);
    if (pwHome) pwHome.classList.remove('active');
    if (pwAway) pwAway.classList.remove('active');
    btn.classList.add('active');
    checkPrediction(gameID);
}
</script>
