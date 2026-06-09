@if(count($rankHistory) >= 2)
@php
    $n        = count($rankHistory);
    $curRank  = $rankHistory[$n - 1];
    $bestRank = min($rankHistory);
    $worstRank= max($rankHistory);
    $delta    = $n >= 2 ? $rankHistory[$n - 2] - $curRank : 0;

    $svgW = max(200, ($n - 1) * 4);
    $svgH = 90;

    $mapY = function(int $r) use ($worstRank): float {
        return 8 + (($r - 1) / max($worstRank - 1, 1)) * 64;
    };

    $pts = '';
    for ($i = 0; $i < $n; $i++) {
        $x = $n > 1 ? ($i / ($n - 1)) * $svgW : $svgW / 2;
        $y = $mapY($rankHistory[$i]);
        $pts .= round($x, 2) . ',' . round($y, 2) . ' ';
    }
    $pts = trim($pts);

    $lastX = $svgW;
    $lastY = round($mapY($curRank), 2);

    $midIdx = (int) floor(($n - 1) / 2);
    $midX   = $n > 1 ? round(($midIdx / ($n - 1)) * $svgW, 2) : $svgW / 2;

    $gridY1   = round($mapY(1), 2);
    $gridYMid = round($mapY((int) ceil(($worstRank + 1) / 2)), 2);
    $gridYMax = round($mapY($worstRank), 2);
@endphp

<div class="sb-card pt-card">
    {{-- Header --}}
    <div class="pt-header">
        <div class="pt-title-group">
            <span class="pt-icon">📈</span>
            <span class="pt-title">Mano vieta</span>
            <span class="pt-subtitle">po kiekvienų rungtynių</span>
        </div>
        <span class="pt-game-count">{{ $n }} rungt.</span>
    </div>

    {{-- Stats strip --}}
    <div class="pt-stats">
        <div class="pt-stat">
            <div class="pt-stat-val pt-accent">#{{ $curRank }}</div>
            <div class="pt-stat-lbl">Dabar</div>
        </div>
        <div class="pt-stat">
            <div class="pt-stat-val pt-up">#{{ $bestRank }}</div>
            <div class="pt-stat-lbl">Geriausia</div>
        </div>
        <div class="pt-stat">
            <div class="pt-stat-val pt-amber">#{{ $worstRank }}</div>
            <div class="pt-stat-lbl">Blogiausia</div>
        </div>
        <div class="pt-stat">
            @if($delta > 0)
                <div class="pt-stat-val pt-up">▲{{ $delta }}</div>
            @elseif($delta < 0)
                <div class="pt-stat-val pt-down">▼{{ abs($delta) }}</div>
            @else
                <div class="pt-stat-val pt-neutral">—</div>
            @endif
            <div class="pt-stat-lbl">Pokytis</div>
        </div>
    </div>

    {{-- Chart --}}
    <div class="pt-chart-wrap">
        <svg viewBox="0 0 {{ $svgW }} {{ $svgH }}" style="width:100%;height:90px;display:block;" preserveAspectRatio="none">
            <defs>
                <linearGradient id="ptGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#3b82f6" stop-opacity=".22"/>
                    <stop offset="100%" stop-color="#3b82f6" stop-opacity="0"/>
                </linearGradient>
            </defs>

            {{-- Grid lines --}}
            <line x1="0" y1="{{ $gridY1 }}"   x2="{{ $svgW }}" y2="{{ $gridY1 }}"   stroke="#f1f5f9" stroke-width="1"/>
            <line x1="0" y1="{{ $gridYMid }}" x2="{{ $svgW }}" y2="{{ $gridYMid }}" stroke="#f1f5f9" stroke-width="1"/>
            <line x1="0" y1="{{ $gridYMax }}" x2="{{ $svgW }}" y2="{{ $gridYMax }}" stroke="#f1f5f9" stroke-width="1"/>

            {{-- Y axis rank labels --}}
            <text x="3" y="{{ $gridY1 + 4 }}"   font-size="7" fill="#cbd5e1">#1</text>
            <text x="3" y="{{ $gridYMid + 4 }}" font-size="7" fill="#cbd5e1">#{{ (int) ceil(($worstRank + 1) / 2) }}</text>
            <text x="3" y="{{ $gridYMax + 4 }}" font-size="7" fill="#cbd5e1">#{{ $worstRank }}</text>

            {{-- Area fill --}}
            <polygon points="{{ $pts }} {{ $svgW }},{{ $svgH }} 0,{{ $svgH }}" fill="url(#ptGrad)"/>

            {{-- Rank line --}}
            <polyline points="{{ $pts }}" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>

            {{-- Endpoint dot --}}
            <circle cx="{{ $lastX }}" cy="{{ $lastY }}" r="5" fill="#3b82f6" stroke="#fff" stroke-width="2.5"/>
            <text x="{{ $lastX }}" y="{{ $lastY - 8 }}" font-size="8" fill="#3b82f6" text-anchor="middle" font-weight="bold">#{{ $curRank }}</text>

            {{-- X axis game labels --}}
            <text x="0"       y="{{ $svgH - 3 }}" font-size="7" fill="#cbd5e1" text-anchor="start">1</text>
            <text x="{{ $midX }}" y="{{ $svgH - 3 }}" font-size="7" fill="#cbd5e1" text-anchor="middle">{{ $midIdx + 1 }}</text>
            <text x="{{ $svgW }}" y="{{ $svgH - 3 }}" font-size="7" fill="#3b82f6" text-anchor="end" font-weight="bold">{{ $n }}</text>
        </svg>
        <div class="pt-chart-hint">
            <span>⬆ geriau</span>
            <span class="pt-chart-axis-lbl">Rungtynių nr.</span>
            <span>⬇ blogiau</span>
        </div>
    </div>
</div>
@endif
