<div class="sb-card">
    <div class="sb-card-title">
        <i class="bi bi-bar-chart-steps sb-card-icon"></i> Eigos taškai
    </div>

    @php
        $pts = collect($predictionStandingsPoints);

        if (!function_exists('pstPopover')) {
            function pstPopover(float $pts, ?float $odds): string {
                if ($pts <= 0 || $odds === null || $odds <= 0) return '';
                $base = round($pts / (1 + $odds), 1);
                $mult = round(1 + $odds, 2);
                return "<div class='sr-pop sr-pop-sm'>"
                    . "<div class='sr-pop-row'><span>Spėjimo taškai</span><strong>" . number_format($base, 1) . "</strong></div>"
                    . "<div class='sr-pop-row'><span>Koeficientas</span><strong>×" . number_format($mult, 2) . "</strong></div>"
                    . "</div>";
            }
        }

        $stageDefs = [
            ['key' => 'group_position', 'label' => 'Grupių etapas', 'pts' => 'group_position_points', 'odds' => 'group_position_odds', 'always' => true],
            ['key' => 'last32',         'label' => '1/16',           'pts' => 'last32_points',         'odds' => 'last32_odds'],
            ['key' => 'last16',         'label' => '1/8',            'pts' => 'last16_points',         'odds' => 'last16_odds'],
            ['key' => 'quarterfinal',   'label' => '1/4',            'pts' => 'quarterfinal_points',   'odds' => 'quarterfinal_odds'],
            ['key' => 'semifinal',      'label' => 'Pusfinalis',     'pts' => 'semifinal_points',      'odds' => 'semifinal_odds'],
            ['key' => 'final',          'label' => 'Finalas',        'pts' => 'final_points',          'odds' => 'final_odds'],
        ];

        $activeStages = array_values(array_filter($stageDefs, function ($s) use ($pts) {
            return !empty($s['always']) ? $pts->isNotEmpty() : $pts->sum($s['pts']) > 0;
        }));
    @endphp

    @if($pts->isEmpty())
        <p class="text-muted" style="font-size:.83rem;margin:0">Taškai dar neskaičiuoti.</p>
    @else
        @foreach($activeStages as $si => $stage)
        @php
            $ptCol   = $stage['pts'];
            $odCol   = $stage['odds'];
            $stTotal = $pts->sum($ptCol);
            $isGroupStage = !empty($stage['always']);
            $withPts = $isGroupStage
                ? $pts->sortBy('team')->values()
                : $pts->filter(fn($r) => (float)$r->$ptCol > 0)->sortByDesc($ptCol)->values();
            $zeroPts = $isGroupStage
                ? collect()
                : $pts->filter(fn($r) => (float)$r->$ptCol <= 0)->sortBy('team')->values();
            $groupedByName = $isGroupStage
                ? $withPts->groupBy('group_name')->sortKeys()
                : null;
            $colId   = 'pst-stage-' . $si;
            $zeroId  = 'pst-zero-' . $si;
        @endphp
        <div class="pst-stage">
            <div class="pst-stage-header" data-bs-toggle="collapse" data-bs-target="#{{ $colId }}" aria-expanded="true">
                <span class="pst-stage-label">{{ $stage['label'] }}</span>
                @if($stTotal > 0)
                <span class="pst-stage-total">{{ number_format((float)$stTotal, 1) }} pt</span>
                @endif
                <i class="bi bi-chevron-down pst-stage-chevron"></i>
            </div>
            <div id="{{ $colId }}" class="collapse show">
                @if($groupedByName)
                    @foreach($groupedByName as $groupName => $groupTeams)
                    <div class="pst-group-label">{{ $groupName ? 'Grupė ' . $groupName : '' }}</div>
                    @foreach($groupTeams as $r)
                    @php $pop = pstPopover((float)$r->$ptCol, isset($r->$odCol) ? (float)$r->$odCol : null); @endphp
                    <div class="pst-stage-row">
                        <span class="pst-stage-team">{{ $r->team }}</span>
                        <span class="{{ (float)$r->$ptCol > 0 ? 'pst-pts' : 'pst-zero' }} {{ $pop ? 'pst-hoverable' : '' }}"
                            @if($pop) data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-placement="left" data-bs-content="{{ $pop }}" @endif
                        >{{ number_format((float)$r->$ptCol, 1) }}</span>
                    </div>
                    @endforeach
                    @endforeach
                @else
                @foreach($withPts as $r)
                @php $pop = pstPopover((float)$r->$ptCol, isset($r->$odCol) ? (float)$r->$odCol : null); @endphp
                <div class="pst-stage-row">
                    <span class="pst-stage-team">{{ $r->team }}</span>
                    <span class="pst-pts {{ $pop ? 'pst-hoverable' : '' }}"
                        @if($pop) data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-placement="left" data-bs-content="{{ $pop }}" @endif
                    >{{ number_format((float)$r->$ptCol, 1) }}</span>
                </div>
                @endforeach
                @endif

                @if($zeroPts->isNotEmpty())
                <button class="pst-zero-toggle collapsed" data-bs-toggle="collapse" data-bs-target="#{{ $zeroId }}" aria-expanded="false">
                    + {{ $zeroPts->count() }} {{ $zeroPts->count() === 1 ? 'komanda' : 'komandos' }} su 0 pt
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div id="{{ $zeroId }}" class="collapse">
                    @foreach($zeroPts as $r)
                    <div class="pst-stage-row pst-stage-row-zero">
                        <span class="pst-stage-team">{{ $r->team }}</span>
                        <span class="pst-zero">0.0</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @endforeach

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.pst-hoverable[data-bs-toggle="popover"]').forEach(function (el) {
                new bootstrap.Popover(el, { container: 'body', html: true });
            });
        });
        </script>
    @endif
</div>
