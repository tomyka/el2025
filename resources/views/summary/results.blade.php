@extends('layouts.master')
@section('containerClass', 'sb-container--fluid')
@section('content')

@php
$users   = collect($predictionResults)->pluck('username')->unique()->values();
$byGame  = collect($predictionResults)->groupBy('game_id')->map(fn($g) => $g->keyBy('username'));
$grouped = collect($games)->groupBy('event_name');

// Precompute per-round totals per user
$roundTotals = $grouped->map(function ($roundGames) use ($byGame, $users) {
    $totals = [];
    foreach ($users as $username) {
        $sum = 0;
        foreach ($roundGames as $game) {
            $pred = $byGame->get($game->id, collect([]))->get($username);
            if ($pred) $sum += (float) $pred->full_points;
        }
        $totals[$username] = $sum;
    }
    return $totals;
});

// Precompute grand totals per user across all rounds
$grandTotals = [];
foreach ($users as $username) {
    $sum = 0;
    foreach ($roundTotals as $totals) { $sum += $totals[$username] ?? 0; }
    $grandTotals[$username] = $sum;
}

// Sort users left-to-right by grand total descending
$users = $users->sortByDesc(fn($u) => $grandTotals[$u] ?? 0)->values();

// Logged-in user for column highlight
$myUsername = auth()->user()?->username ?? null;

// Initialise all rounds as open
$openInit = $grouped->keys()->mapWithKeys(fn($r) => [$r => true])->toJson();
@endphp

<div x-data="{ open: {{ $openInit }} }">
    <div class="sr-scroll-wrap">
        <table class="sr-table">
            <thead>
                <tr>
                    <th class="sr-sticky-col sr-hdr-game">Rungtynės</th>
                    @foreach($users as $username)
                    <th class="sr-hdr-user {{ $username === $myUsername ? 'sr-my-col' : '' }}" title="{{ $username }}">
                        <div class="sr-user-avatar {{ $username === $myUsername ? 'sr-my-avatar' : '' }}">{{ strtoupper(substr($username, 0, 1)) }}</div>
                        <span class="sr-username">{{ $username }}</span>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($grouped as $roundName => $roundGames)
                @php $totals = $roundTotals[$roundName]; @endphp

                {{-- Round group header --}}
                <tr class="sr-round-header" @click="open[{{ json_encode($roundName) }}] = !open[{{ json_encode($roundName) }}]">
                    <td class="sr-sticky-col sr-round-name-td" title="{{ $roundName }}">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi flex-shrink-0"
                               :class="open[{{ json_encode($roundName) }}] ? 'bi-chevron-down' : 'bi-chevron-right'"></i>
                            <span class="sr-round-label">{{ $roundName }}</span>
                            <span class="sr-round-count">({{ $roundGames->count() }})</span>
                        </div>
                    </td>
                    @foreach($users as $username)
                    <td class="sr-round-total {{ $username === $myUsername ? 'sr-my-col' : '' }}">
                        @if(($totals[$username] ?? 0) > 0)
                        {{ number_format($totals[$username], 1) }}
                        @endif
                    </td>
                    @endforeach
                </tr>

                {{-- Game rows --}}
                @foreach($roundGames as $game)
                @php $preds = $byGame->get($game->id, collect([])); @endphp
                <tr x-show="open[{{ json_encode($roundName) }}]">
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
                    <td class="sr-pred-td {{ $username === $myUsername ? 'sr-my-col' : '' }}">
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

                @endforeach
            </tbody>
            <tfoot>
                <tr class="sr-grand-total">
                    <td class="sr-sticky-col">Viso</td>
                    @foreach($users as $username)
                    <td class="{{ $username === $myUsername ? 'sr-my-col' : '' }}">{{ ($grandTotals[$username] ?? 0) > 0 ? number_format($grandTotals[$username], 1) : '' }}</td>
                    @endforeach
                </tr>
            </tfoot>
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
