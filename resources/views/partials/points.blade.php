@php
    $lbLimit   = 10;
    $lbTotal   = count($points);
    $myRankIdx = collect($points)->search(fn($p) => session('userID') == $p['userID']);
    $myRank    = $myRankIdx !== false ? $myRankIdx + 1 : null;
    $userInTop = $myRank !== null && $myRank <= $lbLimit;
@endphp
<div class="sb-card" x-data="{ expanded: false }">
    <div class="sb-card-title"><i class="bi bi-trophy-fill sb-card-icon"></i> Taškų lentelė</div>
    <div class="lb-header">
        <span class="lb-header-rank">#</span>
        <span class="lb-header-name">Žaidėjas</span>
        <span class="lb-header-sub d-none d-md-block" title="Rungtynių taškai"><i class="bi bi-check2-all"></i></span>
        <span class="lb-header-sub d-none d-md-block" title="Eigos taškai"><i class="bi bi-bar-chart-steps"></i></span>
        @if(session('survivalGame') == 1)
        <span class="lb-header-sub d-none d-md-block" title="Išlikimo taškai"><i class="bi bi-shield-check"></i></span>
        @endif
        <span class="lb-header-sub d-none d-md-block" title="Serija (iš eilės pataikytų spėjimų premija)"><i class="bi bi-fire"></i></span>
        <span class="lb-header-sub lb-header-sub-bingo d-none d-md-block" title="Bingo"><i class="bi bi-bullseye"></i></span>
        <span class="lb-header-total">Taškai</span>
        <span class="lb-header-chevron"></span>
    </div>

    @foreach($points as $point)
    @php
        $total        = $point['userGamePoints'] + ($point['userStreakPoints'] ?? 0) + $point['standingPoints']->total_points + $point['survivalPoints'];
        $rank         = $loop->iteration;
        $isMe         = session('userID') == $point['userID'];
        $hidden       = $rank > $lbLimit && !$isMe;
        $pinned       = !$userInTop && $isMe && $rank > $lbLimit;
        $fullName     = trim($point['name'] . ' ' . $point['surname']);
        $tooltipTitle = $fullName ?: null;
        $hasHistory   = !empty($point['roundHistory']);
    @endphp

    @if($pinned)
    <div class="lb-separator" x-show="!expanded">···</div>
    @endif

    <div class="lb-entry" @if($hidden) x-show="expanded" @endif x-data="{ open: false }">
        <div class="lb-row {{ $isMe ? 'lb-me-row' : '' }} {{ $hasHistory ? 'lb-row-expandable' : '' }}"
             @if($hasHistory) x-on:click="open = !open" @endif>
            <div class="lb-rank {{ $rank <= 3 ? 'lb-rank-' . $rank : 'lb-rank-n' }}">{{ $rank }}</div>
            <div class="lb-name {{ $isMe ? 'lb-me-name' : '' }}">
                @if(!$isMe)
                <a class="lb-name-btn lb-name-link"
                   href="{{ route('compare.show', $point['userID']) }}"
                   @if($tooltipTitle) data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $tooltipTitle }}" @endif
                >{{ $point['username'] }}</a>
                @else
                <span class="lb-name-btn"
                      @if($tooltipTitle) data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $tooltipTitle }}" @endif
                >{{ $point['username'] }}</span>
                @endif
            </div>
            <div class="lb-sub-col d-none d-md-block">{{ number_format($point['userGamePoints'], 1) }}</div>
            @php
                $sp = $point['standingPoints'];
                $spStages = [
                    'Grupių etapas'    => (float)($sp->group_position_points ?? 0),
                    'Šešioliktfinalis' => (float)($sp->last32_points ?? 0),
                    'Aštuntfinalis'    => (float)($sp->last16_points ?? 0),
                    'Ketvirtfinalis'   => (float)($sp->quarterfinal_points ?? 0),
                    'Pusfinalis'       => (float)($sp->semifinal_points ?? 0),
                    'Finalas'          => (float)($sp->final_points ?? 0),
                ];
                $spPopRows = '';
                foreach ($spStages as $lbl => $val) {
                    if ($val > 0) $spPopRows .= "<div class='sr-pop-row'><span>{$lbl}</span><strong>" . number_format($val, 1) . "</strong></div>";
                }
                $spPop = $spPopRows ? "<div class='sr-pop sr-pop-sm'>{$spPopRows}</div>" : '';
            @endphp
            <div class="lb-sub-col d-none d-md-block {{ $spPop ? 'pst-hoverable' : '' }}"
                 @if($spPop) data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-placement="top" data-bs-content="{{ $spPop }}" @endif
            >{{ number_format($point['standingPoints']->total_points, 1) }}</div>
            @if(session('survivalGame') == 1)
            <div class="lb-sub-col d-none d-md-block">{{ $point['survivalPoints'] }}</div>
            @endif
            <div class="lb-sub-col d-none d-md-block {{ ($point['userStreakPoints'] ?? 0) > 0 ? 'lb-sub-streak' : '' }}">
                {{ ($point['userStreakPoints'] ?? 0) > 0 ? '+'.number_format($point['userStreakPoints'], 1) : '—' }}
            </div>
            <div class="lb-sub-col d-none d-md-block {{ $point['userGameBingo'] > 0 ? 'lb-sub-bingo' : '' }}">{{ $point['userGameBingo'] > 0 ? '★'.$point['userGameBingo'] : '' }}</div>
            <div class="lb-total {{ $isMe ? 'lb-me-total' : '' }}">{{ number_format($total, 1) }}</div>
            @if($hasHistory)
                <span class="lb-trend-chevron" x-text="open ? '▾' : '▸'"></span>
            @else
                <span class="lb-header-chevron"></span>
            @endif
        </div>

        @if($hasHistory)
        <div x-show="open" x-transition.duration.150ms class="lb-trend-panel" style="display:none">
            @php
                $rounds  = $point['roundHistory'];
                $n       = count($rounds);
                $svgW    = max(120, ($n - 1) * 60);
                $maxRank = max(max(array_column($rounds, 'rank')), 1);

                $rnkPoly = '';
                $dots    = [];
                $lbls    = [];

                foreach ($rounds as $i => $r) {
                    $x        = $n > 1 ? round(($i / ($n - 1)) * $svgW, 1) : $svgW / 2;
                    $yRnk     = $maxRank > 1 ? round(10 + (($r['rank'] - 1) / ($maxRank - 1)) * 60, 1) : 10.0;
                    $rnkPoly .= "{$x},{$yRnk} ";
                    $dots[]   = ['x' => $x, 'y' => $yRnk, 'last' => $i === $n - 1];
                    $lbls[]   = ['x' => $x, 'label' => $r['game_idx']];
                }
            @endphp
            <div class="lb-trend-panel-inner">
                <div class="lb-trend-chart">
                    <div class="lb-trend-chart-label">Paskutinės 10 rungtynių</div>
                    <svg viewBox="0 0 {{ $svgW }} 90" preserveAspectRatio="none">
                        <line x1="0" y1="80" x2="{{ $svgW }}" y2="80" stroke="#e2e8f0" stroke-width="0.5"/>
                        <line x1="0" y1="55" x2="{{ $svgW }}" y2="55" stroke="#e2e8f0" stroke-width="0.5" stroke-dasharray="3,3"/>
                        <line x1="0" y1="30" x2="{{ $svgW }}" y2="30" stroke="#e2e8f0" stroke-width="0.5" stroke-dasharray="3,3"/>
                        @if($n > 1)
                        <polyline points="{{ trim($rnkPoly) }}" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linejoin="round"/>
                        @endif
                        @foreach($dots as $dot)
                        <circle cx="{{ $dot['x'] }}" cy="{{ $dot['y'] }}"
                                r="{{ $dot['last'] ? 4 : 3 }}" fill="#f59e0b"
                                @if($dot['last']) stroke="#fff" stroke-width="1.5" @endif/>
                        @endforeach
                        @foreach($lbls as $lbl)
                        <text x="{{ $lbl['x'] }}" y="89" font-size="8" fill="#94a3b8" text-anchor="middle">{{ $lbl['label'] }}</text>
                        @endforeach
                    </svg>
                    <div class="lb-trend-legend">
                        <span class="lb-trend-legend-rank">-- vieta</span>
                    </div>
                </div>
                <div class="lb-trend-table">
                    <div class="lb-trend-table-header">
                        <span>#</span><span>+Tšk</span><span>Vieta</span>
                    </div>
                    @foreach($rounds as $idx => $r)
                    @php
                        $prev   = $idx > 0 ? $rounds[$idx - 1]['rank'] : null;
                        $rDir   = $prev !== null ? $prev - $r['rank'] : 0;
                        $rCls   = $rDir > 0 ? 'lb-trend-rank-up' : ($rDir < 0 ? 'lb-trend-rank-down' : '');
                        $rArrow = $rDir > 0 ? ' ▲' : ($rDir < 0 ? ' ▼' : '');
                    @endphp
                    <div class="lb-trend-row">
                        <span class="lb-trend-rnd">{{ $r['game_idx'] }}</span>
                        <span class="lb-trend-rpts">+{{ number_format($r['game_points'], 1) }}</span>
                        <span class="lb-trend-rank {{ $rCls }}">#{{ $r['rank'] }}{{ $rArrow }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
    @endforeach

    @if($lbTotal > $lbLimit)
    <button class="lb-show-more" @click="expanded = !expanded">
        <i class="bi" :class="expanded ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        <span x-text="expanded ? 'Rodyti mažiau' : 'Rodyti visus ({{ $lbTotal }})'"></span>
    </button>
    @endif
</div>
