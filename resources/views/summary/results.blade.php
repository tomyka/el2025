@extends('layouts.master')
@section('content')

@php
$users  = collect($predictionResults)->pluck('username')->unique()->values();
$byGame = collect($predictionResults)->groupBy('game_id')->map(fn($g) => $g->keyBy('username'));
$rounds = collect($games)->pluck('event_name')->unique()->filter()->values();
@endphp

<div x-data="{ round: '' }">

    {{-- Round filter tabs --}}
    @if($rounds->count() > 1)
    <div class="sr-tabs">
        <button class="sr-tab" :class="{ 'sr-tab-active': round === '' }" @click="round = ''">Visi</button>
        @foreach($rounds as $r)
        <button class="sr-tab" :class="{ 'sr-tab-active': round === {{ json_encode($r) }} }" @click="round = {{ json_encode($r) }}">{{ $r }}</button>
        @endforeach
    </div>
    @endif

    <div class="sr-scroll-wrap">
        <table class="sr-table">
            <thead>
                <tr>
                    <th class="sr-sticky-col sr-hdr-game">Rungtynės</th>
                    @foreach($users as $username)
                    <th class="sr-hdr-user">{{ $username }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($games as $game)
                @php $preds = $byGame->get($game->id, collect([])); @endphp
                <tr x-show="round === '' || round === {{ json_encode($game->event_name) }}">
                    <td class="sr-sticky-col sr-game-td">
                        <div class="sr-game-flags">
                            <img src="{{ URL::to('img/teams/'.str_replace(' ','%20',strtolower($game->home_team)).'.svg') }}" class="sr-flag" alt="{{ $game->home_team }}">
                            <span class="sr-actual-score">
                                @if(!is_null($game->home_team_score)){{ $game->home_team_score }}:{{ $game->away_team_score }}@else—@endif
                            </span>
                            <img src="{{ URL::to('img/teams/'.str_replace(' ','%20',strtolower($game->away_team)).'.svg') }}" class="sr-flag" alt="{{ $game->away_team }}">
                        </div>
                        <div class="sr-game-names">{{ $game->home_team }} · {{ $game->away_team }}</div>
                    </td>
                    @foreach($users as $username)
                    @php $pred = $preds->get($username); @endphp
                    <td class="sr-pred-td">
                        @if($pred)
                        @php
                            $hasPred = !is_null($pred->home_team_score) && $pred->home_team_score !== '';
                            $scored  = !is_null($game->home_team_score);
                            $correct = $scored && $pred->winner_points >= 5;
                        @endphp
                        @if($scored)
                        <a href="#" class="sr-pts-link"
                           data-bs-toggle="popover"
                           data-bs-trigger="hover focus"
                           data-bs-html="true"
                           data-bs-placement="top"
                           title="{{ $username }}"
                           data-bs-content="<div class='sr-pop'>
                             <div class='sr-pop-row'><span>Nugalėtojas</span><strong>{{ number_format($pred->winner_points,1) }}</strong></div>
                             <div class='sr-pop-row'><span>Skirtumas</span><strong>{{ number_format($pred->difference_points,1) }}</strong></div>
                             <div class='sr-pop-row'><span>Tikslus</span><strong>{{ number_format($pred->bingo_points,1) }}</strong></div>
                             <div class='sr-pop-row'><span>Koef. taškai</span><strong>{{ number_format($pred->odds_points,1) }}</strong></div>
                           </div>">{{ number_format($pred->full_points,1) }}</a>
                        @endif
                        @if($hasPred)
                        <div class="sr-pred-badge {{ $scored ? ($correct ? 'sr-pred-ok' : 'sr-pred-fail') : 'sr-pred-pending' }}">
                            {{ $pred->home_team_score }}:{{ $pred->away_team_score }}
                        </div>
                        @endif
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
        new bootstrap.Popover(el, { container: 'body' });
    });
});
</script>

@endsection
