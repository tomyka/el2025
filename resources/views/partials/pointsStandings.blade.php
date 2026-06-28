<div class="sb-card">
    <div class="sb-card-title">
        <i class="bi bi-bar-chart-steps sb-card-icon"></i> Eigos taškai
    </div>

    @php
        $pts = collect($predictionStandingsPoints);

        if (!function_exists('pstPopover')) {
            function pstPopover(float $pts, ?float $odds): string {
                if ($pts <= 0) return '';
                $base = $odds !== null ? round($pts / (1 + $odds), 1) : $pts;
                $mult = $odds !== null ? round(1 + $odds, 2) : null;
                $html = "<div class='sr-pop sr-pop-sm'>"
                    . "<div class='sr-pop-row'><span>Spėjimo taškai</span><strong>" . number_format($base, 1) . "</strong></div>";
                if ($mult !== null) {
                    $html .= "<div class='sr-pop-row'><span>Koeficientas</span><strong>×" . number_format($mult, 2) . "</strong></div>";
                }
                return $html . "</div>";
            }
        }

        $stageDefs = [
            ['key' => 'group_position', 'label' => 'Grupių etapas', 'pts' => 'group_position_points', 'odds' => 'group_position_odds', 'always' => true],
            ['key' => 'last32',         'label' => 'Šešioliktfinalis', 'pts' => 'last32_points',         'odds' => 'last32_odds'],
            ['key' => 'last16',         'label' => 'Aštuntfinalis',   'pts' => 'last16_points',         'odds' => 'last16_odds'],
            ['key' => 'quarterfinal',   'label' => 'Ketvirtfinalis',  'pts' => 'quarterfinal_points',   'odds' => 'quarterfinal_odds'],
            ['key' => 'semifinal',      'label' => 'Pusfinalis',      'pts' => 'semifinal_points',      'odds' => 'semifinal_odds'],
            ['key' => 'final',          'label' => 'Finalas',         'pts' => 'final_points',          'odds' => 'final_odds'],
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
            $groupedByName = $isGroupStage
                ? $withPts->groupBy('group_name')->sortKeys()
                : null;
            $colId   = 'pst-stage-' . $si;
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
                            @if($pop) data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-placement="left" data-bs-custom-class="pop-narrow" data-bs-content="{{ $pop }}" @endif
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
                        @if($pop) data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-placement="left" data-bs-custom-class="pop-narrow" data-bs-content="{{ $pop }}" @endif
                    >{{ number_format((float)$r->$ptCol, 1) }}</span>
                </div>
                @endforeach
                @endif

            </div>
        </div>
        @endforeach

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.pst-hoverable[data-bs-toggle="popover"]').forEach(function (el) {
                new bootstrap.Popover(el, { container: 'body', html: true, customClass: el.dataset.bsCustomClass || '' });
            });
        });
        </script>
    @endif
</div>
