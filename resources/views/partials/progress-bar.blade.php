@if(!empty($tournamentProgress))
<div class="sb-progress-strip">
    <span class="sb-progress-label">{{ $tournamentProgress['event_name'] }}</span>
    <div class="sb-progress-bar-wrap">
        <div class="sb-progress-bar-fill" style="width:{{ $tournamentProgress['pct'] }}%"></div>
    </div>
    <span class="sb-progress-count">{{ $tournamentProgress['scored_games'] }} / {{ $tournamentProgress['total_games'] }}</span>
    @if($tournamentProgress['today_games'] > 0)
    <span class="sb-progress-today">⏱ {{ $tournamentProgress['today_games'] }} šiandien</span>
    @endif
</div>
@endif
