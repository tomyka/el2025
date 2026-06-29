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
    ->map(fn($dayGames) =>
        $dayGames->first()->event->is_knockout
            ? $dayGames->sortBy('game_date')
                       ->groupBy(fn($g) => \Carbon\Carbon::parse($g->game_date, 'UTC')->setTimezone('Europe/Vilnius')->format('Y-m-d'))
            : $dayGames->groupBy(fn($g) => $g->home_team->group_name)
    );
@endphp

<div class="pred-page">
    @foreach($grouped as $eventDay => $groupGroups)
    @php
        $eventName  = $groupGroups->first()->first()->event->event;
        $isKnockout = $groupGroups->first()->first()->event->is_knockout ?? false;
        $isFinished = $groupGroups->flatten()->every(fn($g) => $g->home_team_score !== null && $g->away_team_score !== null);
        $collapseId = 'evtcol-' . $loop->index;
    @endphp
    <div class="pred-event">
        <div class="pred-event-header {{ $isFinished ? 'collapsed' : '' }}"
             data-bs-toggle="collapse"
             data-bs-target="#{{ $collapseId }}"
             aria-expanded="{{ $isFinished ? 'false' : 'true' }}">
            <span>{{ $eventName }}</span>
            <i class="bi bi-chevron-down pred-event-chevron"></i>
        </div>
        <div class="collapse {{ $isFinished ? '' : 'show' }}" id="{{ $collapseId }}">
        <div class="pred-event-groups">
            @foreach($groupGroups as $groupName => $groupGames)
            @php $firstGame = $groupGames->first(); @endphp
            <div class="pred-day-card">
                <div class="pred-day-header">
                    <span>{{ $isKnockout
                        ? ucfirst(\Carbon\Carbon::parse($groupName)->locale('lt')->isoFormat('MMMM D'))
                        : ($groupName ? 'Grupė ' . $groupName : 'Rungtynės') }}</span>
                </div>
                @foreach($groupGames as $game)
                @php
                    $hasResult  = $game->home_team_score !== null;
                    $isFuture   = \Carbon\Carbon::parse($game->game_date, 'UTC')->gt($now);
                    $isKnockout = $game->event->is_knockout ?? false;
                    $isDraw     = $hasResult && $game->home_team_score == $game->away_team_score;
                @endphp
                <div class="admin-result-row">
                    <div class="pred-team-home">
                        <img src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($game->home_team->team)) . '.svg') }}"
                             class="pred-flag" alt="{{ $game->home_team->team }}">
                        <span class="pred-team-name">{{ $game->home_team->team }}</span>
                        @if($isKnockout && !$isFuture)
                        <button type="button"
                                class="pred-pw-check {{ ($isDraw && $game->game_winner_id == $game->home_team_id) ? 'active' : '' }}"
                                id="pred-pw-home-{{ $game->id }}"
                                style="{{ $isDraw ? '' : 'display:none' }}"
                                onclick="setAdminPenaltyWinner({{ $game->id }}, {{ $game->home_team_id }}, this)">
                            <i class="bi bi-check-circle-fill"></i>
                        </button>
                        @endif
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
                        @endif
                    </div>
                    <div class="pred-team-away">
                        @if($isKnockout && !$isFuture)
                        <button type="button"
                                class="pred-pw-check {{ ($isDraw && $game->game_winner_id == $game->away_team_id) ? 'active' : '' }}"
                                id="pred-pw-away-{{ $game->id }}"
                                style="{{ $isDraw ? '' : 'display:none' }}"
                                onclick="setAdminPenaltyWinner({{ $game->id }}, {{ $game->away_team_id }}, this)">
                            <i class="bi bi-check-circle-fill"></i>
                        </button>
                        @endif
                        <span class="pred-team-name">{{ $game->away_team->team }}</span>
                        <img src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($game->away_team->team)) . '.svg') }}"
                             class="pred-flag" alt="{{ $game->away_team->team }}">
                    </div>
                </div>
                @endforeach
            </div>
            @endforeach
        </div>
        </div>{{-- /collapse --}}
    </div>
    @endforeach
</div>

@endsection

<script>
function saveResult(gameID) {
    var homeScore  = document.getElementById('homeTeamScore' + gameID);
    var awayScore  = document.getElementById('awayTeamScore' + gameID);
    var penWinner  = document.getElementById('penaltyWinner' + gameID);
    var pwHome     = document.getElementById('pred-pw-home-' + gameID);
    var pwAway     = document.getElementById('pred-pw-away-' + gameID);
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

    if (pwHome) pwHome.style.display = isDraw ? '' : 'none';
    if (pwAway) pwAway.style.display = isDraw ? '' : 'none';
    if (!isDraw && penWinner) {
        penWinner.value = '';
        if (pwHome) pwHome.classList.remove('active');
        if (pwAway) pwAway.classList.remove('active');
    }

    if (pwHome && isDraw && penWinner && !penWinner.value) return;

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
    var pwHome = document.getElementById('pred-pw-home-' + gameID);
    var pwAway = document.getElementById('pred-pw-away-' + gameID);
    if (pwHome) pwHome.classList.remove('active');
    if (pwAway) pwAway.classList.remove('active');
    btn.classList.add('active');
    saveResult(gameID);
}
</script>
