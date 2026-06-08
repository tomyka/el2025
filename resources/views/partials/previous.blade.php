<div class="sb-card">
    <div class="sb-card-title"><i class="bi bi-clock-history sb-card-icon"></i> Praėjusio turo lyderiai</div>
    @php
        $maxRound = collect($previousRoundPoints)->max(fn($p) => $p['pointResult']->full_points + $p['pointSurvival']) ?: 1;
    @endphp
    @foreach($previousRoundPoints as $p)
    @php
        $roundTotal = $p['pointResult']->full_points + $p['pointSurvival'];
        $barW = round($roundTotal / $maxRound * 100);
        $rank = $loop->iteration;
    @endphp
    <div class="lb-row">
        <div class="lb-rank {{ $rank <= 3 ? 'lb-rank-' . $rank : 'lb-rank-n' }}">{{ $rank }}</div>
        <div class="lb-name">{{ $p['username'] }}</div>
        <div class="lb-bar-wrap">
            <div class="lb-bar" style="width:{{ $barW }}%"></div>
        </div>
        <a href="#" class="lb-points"
           data-bs-toggle="popover" data-bs-html="true" data-bs-trigger="hover focus"
           data-bs-content="<div style='font-size:.8rem;min-width:160px'><div class='d-flex justify-content-between gap-3'><span>Turo taškai:</span><strong>{{ number_format($p['pointResult']->full_points, 1) }}</strong></div>@if(session('survivalGame') == 1)<div class='d-flex justify-content-between gap-3'><span>Išlikimas:</span><strong>{{ $p['pointSurvival'] }}</strong></div>@endif<div class='d-flex justify-content-between gap-3'><span>Vidurkis:</span><strong>{{ $p['pointResult']->avg_points }}</strong></div><div class='d-flex justify-content-between gap-3'><span>Atspėta:</span><strong>{{ $p['pointResult']->correct_guess }}</strong></div></div>"
           data-bs-original-title="{{ $p['username'] }}">{{ number_format($roundTotal, 1) }}</a>
    </div>
    @endforeach
</div>
