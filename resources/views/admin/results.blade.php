@extends('admin.layouts.master')
@section('content')

@if(count($errors->all()))
<div class="alert alert-danger mb-3">
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $error)
            <li style="font-size:.83rem">{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

@if(Session::has('info'))
<div class="alert alert-primary mb-3">{{ Session::get('info') }}</div>
@endif

@php
$grouped = collect($games)
    ->groupBy(fn($g) => $g->event->event_day)
    ->map(fn($day) => $day->groupBy(fn($g) => $g->home_team->group_name));
@endphp

<div class="pred-page">
    @foreach($grouped as $eventDay => $groupGroups)
    @php $eventName = $groupGroups->first()->first()->event->event; @endphp
    <div class="pred-event">
        <div class="pred-event-header">{{ $eventName }}</div>
        <div class="pred-event-groups">
            @foreach($groupGroups as $groupName => $groupGames)
            @php $firstGame = $groupGames->first(); @endphp
            <div class="pred-day-card">
                <div class="pred-day-header">
                    <span>{{ $groupName ? 'Grupė ' . $groupName : 'Rungtynės' }}</span>
                </div>
                @foreach($groupGames as $game)
                @php
                    $hasResult  = $game->home_team_score !== null;
                    $isFuture   = \Carbon\Carbon::parse($game->game_date, 'UTC')->gt($now);
                    $isKnockout = $game->event->is_knockout ?? false;
                    $isDraw     = $hasResult && $game->home_team_score == $game->away_team_score;
                @endphp
                <div class="pred-game">
                    <div class="pred-team-home">
                        <span class="pred-team-name">{{ $game->home_team->team }}</span>
                        <img src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($game->home_team->team)) . '.svg') }}"
                             class="pred-flag" alt="{{ $game->home_team->team }}">
                    </div>
                    <div class="pred-scores">
                        <span class="pred-time">{{ ucfirst(\Carbon\Carbon::parse($game->game_date, 'UTC')->setTimezone('Europe/Vilnius')->locale('lt')->isoFormat('MMMM D')) }} · {{ \Carbon\Carbon::parse($game->game_date, 'UTC')->setTimezone('Europe/Vilnius')->format('H:i') }}</span>
                        <div class="pred-scores-inputs">
                            <input type="text"
                                   class="form-control pred-score"
                                   id="homeTeamScore{{ $game->id }}"
                                   onchange="saveResult({{ $game->id }})"
                                   value="{{ $game->home_team_score }}"
                                   maxlength="2"
                                   style="{{ $hasResult ? 'border-color:#22c55e' : '' }}"
                                   {{ $isFuture ? 'disabled' : '' }}
                                   autocomplete="off">
                            <span class="pred-sep">:</span>
                            <input type="text"
                                   class="form-control pred-score"
                                   id="awayTeamScore{{ $game->id }}"
                                   onchange="saveResult({{ $game->id }})"
                                   value="{{ $game->away_team_score }}"
                                   maxlength="2"
                                   style="{{ $hasResult ? 'border-color:#22c55e' : '' }}"
                                   {{ $isFuture ? 'disabled' : '' }}
                                   autocomplete="off">
                        </div>
                        @if($isKnockout)
                        <input type="hidden" id="penaltyWinner{{ $game->id }}" value="{{ $game->game_winner_id ?? '' }}">
                        <div class="pred-penalty" id="pred-penalty-{{ $game->id }}"
                             style="{{ $isDraw ? '' : 'display:none' }}">
                            <div class="pred-penalty-label">Baudų serija</div>
                            <div class="pred-penalty-picker">
                                <button type="button" class="pred-penalty-btn {{ ($isDraw && $game->game_winner_id == $game->home_team_id) ? 'active' : '' }}"
                                        data-team-id="{{ $game->home_team_id }}"
                                        onclick="setAdminPenaltyWinner({{ $game->id }}, {{ $game->home_team_id }}, this)">
                                    <img src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($game->home_team->team)) . '.svg') }}">
                                    {{ $game->home_team->team }}
                                </button>
                                <button type="button" class="pred-penalty-btn {{ ($isDraw && $game->game_winner_id == $game->away_team_id) ? 'active' : '' }}"
                                        data-team-id="{{ $game->away_team_id }}"
                                        onclick="setAdminPenaltyWinner({{ $game->id }}, {{ $game->away_team_id }}, this)">
                                    <img src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($game->away_team->team)) . '.svg') }}">
                                    {{ $game->away_team->team }}
                                </button>
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="pred-team-away">
                        <img src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($game->away_team->team)) . '.svg') }}"
                             class="pred-flag" alt="{{ $game->away_team->team }}">
                        <span class="pred-team-name">{{ $game->away_team->team }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>

@endsection

<script>
function saveResult(gameID) {
    var homeScore  = document.getElementById('homeTeamScore' + gameID);
    var awayScore  = document.getElementById('awayTeamScore' + gameID);
    var penDiv     = document.getElementById('pred-penalty-' + gameID);
    var penWinner  = document.getElementById('penaltyWinner' + gameID);
    var homeVal    = homeScore.value.trim();
    var awayVal    = awayScore.value.trim();
    var bothFilled = homeVal !== '' && awayVal !== '';
    var bothEmpty  = homeVal === '' && awayVal === '';
    var isDraw     = bothFilled && homeVal === awayVal;

    if (!bothFilled && !bothEmpty) {
        homeScore.style.borderColor = '#fbbf24';
        awayScore.style.borderColor = '#fbbf24';
        return;
    }

    if (penDiv) {
        penDiv.style.display = isDraw ? '' : 'none';
        if (!isDraw && penWinner) penWinner.value = '';
    }

    if (isDraw && penDiv && penWinner && !penWinner.value) return; // wait for penalty winner

    $.ajax({
        type: 'POST',
        url: '{{ route("admin.updateResult") }}',
        data: { gameID: gameID, homeTeamScore: homeVal, awayTeamScore: awayVal, gameWinnerID: penWinner ? penWinner.value : '' },
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    }).done(function() {
        var color = bothFilled ? '#22c55e' : '#cbd5e1';
        homeScore.style.borderColor = color;
        awayScore.style.borderColor = color;
    }).fail(function() {
        homeScore.style.borderColor = '#ef4444';
        awayScore.style.borderColor = '#ef4444';
    });
}

function setAdminPenaltyWinner(gameID, teamID, btn) {
    var penWinner = document.getElementById('penaltyWinner' + gameID);
    penWinner.value = teamID;
    document.querySelectorAll('#pred-penalty-' + gameID + ' .pred-penalty-btn').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
    saveResult(gameID);
}
</script>
