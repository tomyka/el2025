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
                    $hasResult = $game->home_team_score !== null;
                    $isFuture  = \Carbon\Carbon::parse($game->game_date, 'UTC')->gt($now);
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
    var homeScore = document.getElementById('homeTeamScore' + gameID);
    var awayScore = document.getElementById('awayTeamScore' + gameID);
    var homeVal   = homeScore.value.trim();
    var awayVal   = awayScore.value.trim();
    var bothFilled = homeVal !== '' && awayVal !== '';
    var bothEmpty  = homeVal === '' && awayVal === '';

    if (!bothFilled && !bothEmpty) {
        homeScore.style.borderColor = '#fbbf24';
        awayScore.style.borderColor = '#fbbf24';
        return;
    }

    $.ajax({
        type: 'POST',
        url: '{{ route("admin.updateResult") }}',
        data: { gameID: gameID, homeTeamScore: homeVal, awayTeamScore: awayVal },
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
</script>
