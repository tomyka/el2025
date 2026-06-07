@extends('layouts.master')
@section('content')

@auth
    @if($groupedResults->isEmpty())
        <p class="text-center text-muted py-4">Nėra artėjančių rungtynių.</p>
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
                        <span>{{ $groupName ? 'Grupė ' . $groupName : 'Rungtynės' }}</span>
                        <span class="pred-day-date">{{ \Carbon\Carbon::parse($games->first()->game_date)->format('d.m') }}</span>
                    </div>
                    @foreach($games as $game)
                    <div class="pred-game">
                        <input type="hidden" id="prediction_gameID{{$game->game_id}}" value="{{ $game->id }}">
                        <div class="pred-team-home">
                            <span class="pred-team-name">{{ $game->home_team }}</span>
                            <img src="{{ URL::to('img/teams/'.str_replace(' ','%20',strtolower($game->home_team)).'.svg') }}" class="pred-flag" alt="{{ $game->home_team }}">
                        </div>
                        <div class="pred-scores">
                            <input type="text" class="form-control pred-score" id="homeTeamScore{{$game->game_id}}" onkeyup="checkPrediction({{$game->game_id}})" value="{{ $game->home_team_score }}" maxlength="2" autocomplete="off">
                            <span class="pred-sep">:</span>
                            <input type="text" class="form-control pred-score" id="awayTeamScore{{$game->game_id}}" onkeyup="checkPrediction({{$game->game_id}})" value="{{ $game->away_team_score }}" maxlength="2" autocomplete="off">
                        </div>
                        <div class="pred-team-away">
                            <img src="{{ URL::to('img/teams/'.str_replace(' ','%20',strtolower($game->away_team)).'.svg') }}" class="pred-flag" alt="{{ $game->away_team }}">
                            <span class="pred-team-name">{{ $game->away_team }}</span>
                        </div>
                    </div>
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
function checkPrediction(gameID) {
    var homeScore = document.getElementById('homeTeamScore' + gameID);
    var awayScore = document.getElementById('awayTeamScore' + gameID);
    var predictionID = document.getElementById('prediction_gameID' + gameID);

    var homeVal = homeScore.value.trim();
    var awayVal = awayScore.value.trim();
    var bothFilled = homeVal !== '' && awayVal !== '';
    var bothEmpty  = homeVal === '' && awayVal === '';

    if (!bothFilled && !bothEmpty) return;

    $.ajax({
        type: 'POST',
        url: '{{ route("prediction.results") }}',
        data: {
            prediction_gameID: predictionID.value,
            gameID: gameID,
            homeTeamScore: homeVal,
            awayTeamScore: awayVal
        },
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    }).done(function(data) {
        var color = bothFilled ? '#22c55e' : '#cbd5e1';
        homeScore.style.borderColor = color;
        awayScore.style.borderColor = color;
    });
}
</script>
