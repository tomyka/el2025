<div class="sb-card">
    <div class="sb-card-title">🏆 Taškų lentelė</div>
    @php
        $maxTotal = collect($points)->max(fn($p) => $p['userGamePoints'] + $p['standingPoints']->total_points + $p['survivalPoints']) ?: 1;
    @endphp
    @foreach($points as $point)
    @php
        $total = $point['userGamePoints'] + $point['standingPoints']->total_points + $point['survivalPoints'];
        $barW  = round($total / $maxTotal * 100);
        $rank  = $loop->iteration;
        $isMe  = session('userID') == $point['userID'];
    @endphp
    <div class="lb-row {{ $isMe ? 'lb-me-row' : '' }}">
        <div class="lb-rank {{ $rank <= 3 ? 'lb-rank-' . $rank : 'lb-rank-n' }}">{{ $rank }}</div>
        <div class="lb-name {{ $isMe ? 'lb-me-name' : '' }}">{{ $point['username'] }}</div>
        <div class="lb-bar-wrap">
            <div class="lb-bar" style="width:{{ $barW }}%"></div>
        </div>
        <a href="#" class="lb-points"
           data-bs-toggle="popover" data-bs-html="true" data-bs-trigger="hover focus"
           data-bs-content="<div style='font-size:.8rem;min-width:160px'><div class='d-flex justify-content-between gap-3'><span>Rungtynės:</span><strong>{{ $point['userGamePoints'] }}</strong></div><div class='d-flex justify-content-between gap-3'><span>Eiga:</span><strong>{{ $point['standingPoints']->total_points }}</strong></div>@if(session('survivalGame') == 1)<div class='d-flex justify-content-between gap-3'><span>Išlikimas:</span><strong>{{ $point['survivalPoints'] }}</strong></div>@endif<div class='d-flex justify-content-between gap-3'><span>Vidurkis:</span><strong>{{ $point['averagePoints'] }}</strong></div><div class='d-flex justify-content-between gap-3'><span>Bingo:</span><strong>{{ $point['userGameBingo'] }}</strong></div></div>"
           data-bs-original-title="{{ $point['username'] }}">{{ $total }}</a>
    </div>
    @endforeach
</div>
