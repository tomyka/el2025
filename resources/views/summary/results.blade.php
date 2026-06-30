@extends('layouts.master')
@section('containerClass', 'sb-container--fluid')
@section('content')

@php
$users   = collect($predictionResults)->pluck('username')->unique()->values();
$byGame  = collect($predictionResults)->groupBy('game_id')->map(fn($g) => $g->keyBy('username'));
$grouped = collect($games)->groupBy('event_name');

// Active rounds (have unscored games) first, finished rounds in descending order (most recent first)
$activeKeys   = $grouped->keys()->filter(fn($k) => $grouped[$k]->contains(fn($g) => is_null($g->home_team_score)));
$finishedKeys = $grouped->keys()->filter(fn($k) => !$grouped[$k]->contains(fn($g) => is_null($g->home_team_score)))->reverse()->values();
$grouped = $activeKeys->values()->concat($finishedKeys)->mapWithKeys(fn($k) => [$k => $grouped[$k]]);

// Precompute per-round totals per user
$roundTotals = $grouped->map(function ($roundGames) use ($byGame, $users) {
    $totals = [];
    foreach ($users as $username) {
        $sum = 0;
        foreach ($roundGames as $game) {
            $pred = $byGame->get($game->id, collect([]))->get($username);
            if ($pred) $sum += (float) $pred->full_points + (float) ($pred->streak_bonus ?? 0);
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

// Active event name (always keep expanded even when all games are scored)
$activeEventName = collect($games)->firstWhere('event_id', $activeEventID)?->event_name ?? null;

// Collapse finished rounds, but always keep the active event open
$openInit = $grouped->mapWithKeys(
    fn($roundGames, $r) => [
        $r => ($activeEventName && $r === $activeEventName)
              || $roundGames->contains(fn($g) => is_null($g->home_team_score))
    ]
)->toJson();
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
                                @if(is_null($game->home_team_score))—
                                @else
                                @php $actPenHome = $game->is_knockout && $game->home_team_score == $game->away_team_score && $game->game_winner_id == $game->home_team_id; $actPenAway = $game->is_knockout && $game->home_team_score == $game->away_team_score && $game->game_winner_id == $game->away_team_id; @endphp
                                @if($actPenHome)<span class="sr-pw-w">ⓦ</span>@endif{{ $game->home_team_score }}:{{ $game->away_team_score }}@if($actPenAway)<span class="sr-pw-w">ⓦ</span>@endif
                                @endif
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
                            $hasPred        = !is_null($pred->home_team_score) && $pred->home_team_score !== '';
                            $scored         = !is_null($game->home_team_score);
                            $predIsDraw     = (string)$pred->home_team_score === (string)$pred->away_team_score;
                            $actualIsDraw   = $scored && (string)$game->home_team_score === (string)$game->away_team_score;
                            $endingOk       = $predIsDraw === $actualIsDraw;
                            $correct        = $scored && $pred->winner_points >= 5 && (!$game->is_knockout || $endingOk);
                            $partial        = $scored && !$correct && $game->is_knockout && $pred->winner_points >= 5;
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
                             <div class='sr-pop-row'><span>Serija</span><strong>@if(($pred->streak_bonus??0)>0)+@endif{{ number_format($pred->streak_bonus??0,1) }}</strong></div>
                           </div>">{{ number_format($pred->full_points + ($pred->streak_bonus ?? 0), 1) }}</a>
                        @endif
                        @if($hasPred)
                        @php $hasPenWinner = $predIsDraw && $game->is_knockout && $pred->game_winner_id; @endphp
                        <div class="sr-pred-badge {{ $scored ? ($correct ? 'sr-pred-ok' : ($partial ? 'sr-pred-partial' : 'sr-pred-fail')) : 'sr-pred-pending' }}">
                            @if($hasPenWinner && $pred->game_winner_id == $game->home_team_id)<span class="sr-pw-w">ⓦ</span>@endif{{ $pred->home_team_score }}:{{ $pred->away_team_score }}@if($hasPenWinner && $pred->game_winner_id == $game->away_team_id)<span class="sr-pw-w">ⓦ</span>@endif
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
