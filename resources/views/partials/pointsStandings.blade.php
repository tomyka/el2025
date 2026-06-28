<div class="sb-card">
    <div class="sb-card-title">
        <i class="bi bi-bar-chart-steps sb-card-icon"></i> Eigos taškai
    </div>

    @php
        $pts = collect($predictionStandingsPoints);
        $hasLast32 = $pts->sum('last32_points') > 0;
        $hasLast16 = $pts->sum('last16_points') > 0;
        $hasQF     = $pts->sum('quarterfinal_points') > 0;
        $hasSF     = $pts->sum('semifinal_points') > 0;
        $hasFinal  = $pts->sum('final_points') > 0;
        $rows = $pts;

        function pstPopover(float $pts, ?float $odds): string {
            if ($pts <= 0 || $odds === null || $odds <= 0) return '';
            $base = round($pts / (1 + $odds), 1);
            $mult = round(1 + $odds, 2);
            return "<div class='sr-pop sr-pop-sm'>"
                . "<div class='sr-pop-row'><span>Spėjimo taškai</span><strong>" . number_format($base, 1) . "</strong></div>"
                . "<div class='sr-pop-row'><span>Koeficientas</span><strong>×" . number_format($mult, 2) . "</strong></div>"
                . "</div>";
        }
    @endphp

    @if($rows->isEmpty())
        <p class="text-muted" style="font-size:.83rem;margin:0">Taškai dar neskaičiuoti.</p>
    @else
    <div class="table-responsive">
        <table class="pst-table">
            <thead>
                <tr>
                    <th>Komanda</th>
                    <th title="Grupės vieta">G</th>
                    @if($hasLast32)<th title="1/16 etapas">1/16</th>@endif
                    @if($hasLast16)<th title="1/8 etapas">1/8</th>@endif
                    @if($hasQF)<th title="1/4 etapas">1/4</th>@endif
                    @if($hasSF)<th title="Pusfinalis">PF</th>@endif
                    @if($hasFinal)<th title="Finalas">F</th>@endif
                    <th>Viso</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                @php
                    $total = $r->group_position_points + $r->last32_points + $r->last16_points
                           + $r->quarterfinal_points + $r->semifinal_points + $r->final_points;

                    $gpPop  = pstPopover((float)$r->group_position_points, isset($r->group_position_odds) ? (float)$r->group_position_odds : null);
                    $l32Pop = pstPopover((float)$r->last32_points,         isset($r->last32_odds)         ? (float)$r->last32_odds         : null);
                    $l16Pop = pstPopover((float)$r->last16_points,         isset($r->last16_odds)         ? (float)$r->last16_odds         : null);
                    $qfPop  = pstPopover((float)$r->quarterfinal_points,   isset($r->quarterfinal_odds)   ? (float)$r->quarterfinal_odds   : null);
                    $sfPop  = pstPopover((float)$r->semifinal_points,      isset($r->semifinal_odds)      ? (float)$r->semifinal_odds      : null);
                    $finPop = pstPopover((float)$r->final_points,          isset($r->final_odds)          ? (float)$r->final_odds          : null);
                @endphp
                <tr>
                    <td class="pst-team">{{ $r->team }}</td>
                    <td class="{{ $r->group_position_points > 0 ? 'pst-pts' : 'pst-zero' }} {{ $gpPop ? 'pst-hoverable' : '' }}"
                        @if($gpPop) data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-placement="left" data-bs-content="{{ $gpPop }}" @endif
                    >{{ $r->group_position_points ? number_format($r->group_position_points, 1) : '—' }}</td>
                    @if($hasLast32)
                    <td class="{{ $r->last32_points > 0 ? 'pst-pts' : 'pst-zero' }} {{ $l32Pop ? 'pst-hoverable' : '' }}"
                        @if($l32Pop) data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-placement="left" data-bs-content="{{ $l32Pop }}" @endif
                    >{{ $r->last32_points ? number_format($r->last32_points, 1) : '—' }}</td>
                    @endif
                    @if($hasLast16)
                    <td class="{{ $r->last16_points > 0 ? 'pst-pts' : 'pst-zero' }} {{ $l16Pop ? 'pst-hoverable' : '' }}"
                        @if($l16Pop) data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-placement="left" data-bs-content="{{ $l16Pop }}" @endif
                    >{{ $r->last16_points ? number_format($r->last16_points, 1) : '—' }}</td>
                    @endif
                    @if($hasQF)
                    <td class="{{ $r->quarterfinal_points > 0 ? 'pst-pts' : 'pst-zero' }} {{ $qfPop ? 'pst-hoverable' : '' }}"
                        @if($qfPop) data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-placement="left" data-bs-content="{{ $qfPop }}" @endif
                    >{{ $r->quarterfinal_points ? number_format($r->quarterfinal_points, 1) : '—' }}</td>
                    @endif
                    @if($hasSF)
                    <td class="{{ $r->semifinal_points > 0 ? 'pst-pts' : 'pst-zero' }} {{ $sfPop ? 'pst-hoverable' : '' }}"
                        @if($sfPop) data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-placement="left" data-bs-content="{{ $sfPop }}" @endif
                    >{{ $r->semifinal_points ? number_format($r->semifinal_points, 1) : '—' }}</td>
                    @endif
                    @if($hasFinal)
                    <td class="{{ $r->final_points > 0 ? 'pst-pts' : 'pst-zero' }} {{ $finPop ? 'pst-hoverable' : '' }}"
                        @if($finPop) data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-placement="left" data-bs-content="{{ $finPop }}" @endif
                    >{{ $r->final_points ? number_format($r->final_points, 1) : '—' }}</td>
                    @endif
                    <td class="pst-total">{{ number_format($total, 1) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.pst-hoverable[data-bs-toggle="popover"]').forEach(function (el) {
            new bootstrap.Popover(el, { container: 'body', html: true });
        });
    });
    </script>
    @endif
</div>
