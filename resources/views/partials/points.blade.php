<div class="sb-card">
    <div class="sb-card-title"><i class="bi bi-trophy-fill sb-card-icon"></i> Taškų lentelė</div>
    @php
        $feeRequired = isset($groupDetails) && $groupDetails->fee > 0;
    @endphp
    <div class="lb-header">
        <span class="lb-header-rank">#</span>
        <span class="lb-header-name">Žaidėjas</span>
        <span class="lb-header-sub">R</span>
        <span class="lb-header-sub">E</span>
        @if(session('survivalGame') == 1)
        <span class="lb-header-sub">I</span>
        @endif
        <span class="lb-header-sub">Avg</span>
        <span class="lb-header-sub lb-header-sub-bingo">★</span>
        <span class="lb-header-trend"></span>
        <span class="lb-header-total">Taškai</span>
        <span class="lb-header-chevron"></span>
    </div>
    @foreach($points as $point)
    @php
        $total     = $point['userGamePoints'] + $point['standingPoints']->total_points + $point['survivalPoints'];
        $rank      = $loop->iteration;
        $isMe      = session('userID') == $point['userID'];
        $fullName  = trim($point['name'] . ' ' . $point['surname']);
        $feeHtml   = '';
        if ($feeRequired) {
            $feeHtml = $point['userFee'] > 0
                ? '<div class="text-success" style="font-size:.78rem">&#10003; Mokestis sumokėtas</div>'
                : '<div class="text-danger" style="font-size:.78rem">&#10007; Mokestis nesumokėtas</div>';
        }
        $breakdown = 'R: ' . number_format($point['userGamePoints'], 1) . ' &nbsp;·&nbsp; E: ' . number_format($point['standingPoints']->total_points, 1);
        if (session('survivalGame') == 1) {
            $breakdown .= ' &nbsp;·&nbsp; I: ' . $point['survivalPoints'];
        }
        $breakdown .= ' &nbsp;·&nbsp; Avg: ' . number_format($point['averagePoints'], 1);
        if ($point['userGameBingo'] > 0) {
            $breakdown .= ' &nbsp;·&nbsp; &#127919; ' . $point['userGameBingo'];
        }
        $popoverContent = '<div style="font-size:.8rem">'
            . ($fullName ? '<strong>' . e($fullName) . '</strong>' : '')
            . $feeHtml
            . '<div class="text-muted mt-1">' . $breakdown . '</div>'
            . '</div>';

        $hasHistory = !empty($point['roundHistory']);
        $rankChange = $point['rankChange'] ?? null;
        if ($rankChange === null || !$hasHistory) {
            $badgeClass = 'lb-trend-neutral'; $badgeText = '—';
        } elseif ($rankChange > 0) {
            $badgeClass = 'lb-trend-up';   $badgeText = '▲ ' . $rankChange;
        } elseif ($rankChange < 0) {
            $badgeClass = 'lb-trend-down'; $badgeText = '▼ ' . abs($rankChange);
        } else {
            $badgeClass = 'lb-trend-neutral'; $badgeText = '—';
        }
    @endphp
    <div class="lb-entry" x-data="{ open: false }">
        <div class="lb-row {{ $isMe ? 'lb-me-row' : '' }} {{ $hasHistory ? 'lb-row-expandable' : '' }}"
             @if($hasHistory) x-on:click="open = !open" @endif>
            <div class="lb-rank {{ $rank <= 3 ? 'lb-rank-' . $rank : 'lb-rank-n' }}">{{ $rank }}</div>
            <div class="lb-name {{ $isMe ? 'lb-me-name' : '' }}">
                <span class="lb-name-btn"
                      tabindex="0"
                      data-bs-toggle="popover"
                      data-bs-trigger="click"
                      data-bs-html="true"
                      data-bs-title="{{ $fullName ?: $point['username'] }}"
                      data-bs-content="{{ $popoverContent }}"
                      x-on:click.stop>{{ $point['username'] }}</span>
            </div>
            <div class="lb-sub-col">{{ number_format($point['userGamePoints'], 1) }}</div>
            <div class="lb-sub-col">{{ number_format($point['standingPoints']->total_points, 1) }}</div>
            @if(session('survivalGame') == 1)
            <div class="lb-sub-col">{{ $point['survivalPoints'] }}</div>
            @endif
            <div class="lb-sub-col">{{ number_format($point['averagePoints'], 1) }}</div>
            <div class="lb-sub-col {{ $point['userGameBingo'] > 0 ? 'lb-sub-bingo' : '' }}">{{ $point['userGameBingo'] > 0 ? '★'.$point['userGameBingo'] : '' }}</div>
            <span class="lb-trend-badge {{ $badgeClass }}">{{ $badgeText }}</span>
            <div class="lb-total {{ $isMe ? 'lb-me-total' : '' }}">{{ number_format($total, 1) }}</div>
            @if($hasHistory)
                <span class="lb-trend-chevron" x-text="open ? '▾' : '▸'"></span>
            @endif
        </div>

        @if($hasHistory)
        <div x-show="open" x-transition.duration.150ms class="lb-trend-panel" style="display:none">
            @php
                $rounds  = $point['roundHistory'];
                $n       = count($rounds);
                $svgW    = max(120, ($n - 1) * 60);
                $maxCum  = max(max(array_column($rounds, 'cumulative_points')), 1);
                $maxRank = max(max(array_column($rounds, 'rank')), 1);

                $ptsPoly = '';
                $rnkPoly = '';
                $dots    = [];
                $lbls    = [];

                foreach ($rounds as $i => $r) {
                    $x        = $n > 1 ? round(($i / ($n - 1)) * $svgW, 1) : $svgW / 2;
                    $yPts     = round(10 + (1 - $r['cumulative_points'] / $maxCum) * 60, 1);
                    $yRnk     = $maxRank > 1 ? round(10 + (($r['rank'] - 1) / ($maxRank - 1)) * 60, 1) : 10.0;
                    $ptsPoly .= "{$x},{$yPts} ";
                    $rnkPoly .= "{$x},{$yRnk} ";
                    $dots[]   = ['x' => $x, 'y' => $yPts, 'last' => $i === $n - 1];
                    $lbls[]   = ['x' => $x, 'label' => 'R' . $r['event_day']];
                }
            @endphp
            <div class="lb-trend-panel-inner">
                <div class="lb-trend-chart">
                    <div class="lb-trend-chart-label">Taškai ir vieta per turus</div>
                    <svg viewBox="0 0 {{ $svgW }} 90" style="width:100%;height:90px">
                        <line x1="0" y1="80" x2="{{ $svgW }}" y2="80" stroke="#e2e8f0" stroke-width="0.5"/>
                        <line x1="0" y1="55" x2="{{ $svgW }}" y2="55" stroke="#e2e8f0" stroke-width="0.5" stroke-dasharray="3,3"/>
                        <line x1="0" y1="30" x2="{{ $svgW }}" y2="30" stroke="#e2e8f0" stroke-width="0.5" stroke-dasharray="3,3"/>
                        @if($n > 1)
                        <polyline points="{{ trim($ptsPoly) }}" fill="none" stroke="#2563eb" stroke-width="2" stroke-linejoin="round"/>
                        <polyline points="{{ trim($rnkPoly) }}" fill="none" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="4,2" stroke-linejoin="round"/>
                        @endif
                        @foreach($dots as $dot)
                        <circle cx="{{ $dot['x'] }}" cy="{{ $dot['y'] }}"
                                r="{{ $dot['last'] ? 4 : 3 }}" fill="#2563eb"
                                @if($dot['last']) stroke="#fff" stroke-width="1.5" @endif/>
                        @endforeach
                        @foreach($lbls as $lbl)
                        <text x="{{ $lbl['x'] }}" y="89" font-size="8" fill="#94a3b8" text-anchor="middle">{{ $lbl['label'] }}</text>
                        @endforeach
                    </svg>
                    <div class="lb-trend-legend">
                        <span class="lb-trend-legend-pts">— taškai</span>
                        <span class="lb-trend-legend-rank">-- vieta</span>
                    </div>
                </div>
                <div class="lb-trend-table">
                    <div class="lb-trend-table-header">
                        <span>Turas</span><span>+Tšk</span><span>Vieta</span>
                    </div>
                    @foreach($rounds as $idx => $r)
                    @php
                        $prev   = $idx > 0 ? $rounds[$idx - 1]['rank'] : null;
                        $rDir   = $prev !== null ? $prev - $r['rank'] : 0;
                        $rCls   = $rDir > 0 ? 'lb-trend-rank-up' : ($rDir < 0 ? 'lb-trend-rank-down' : '');
                        $rArrow = $rDir > 0 ? ' ▲' : ($rDir < 0 ? ' ▼' : '');
                    @endphp
                    <div class="lb-trend-row">
                        <span class="lb-trend-rnd">R{{ $r['event_day'] }}</span>
                        <span class="lb-trend-rpts">+{{ number_format($r['round_points'], 1) }}</span>
                        <span class="lb-trend-rank {{ $rCls }}">#{{ $r['rank'] }}{{ $rArrow }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
    @endforeach
</div>
