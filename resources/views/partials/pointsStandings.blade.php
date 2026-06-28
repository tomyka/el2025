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
        $rows      = $pts->filter(fn($r) =>
            $r->group_position_points + $r->last32_points + $r->last16_points
            + $r->quarterfinal_points + $r->semifinal_points + $r->final_points > 0
        );
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
                @endphp
                <tr>
                    <td class="pst-team">{{ $r->team }}</td>
                    <td class="{{ $r->group_position_points > 0 ? 'pst-pts' : 'pst-zero' }}">{{ $r->group_position_points ? number_format($r->group_position_points, 1) : '—' }}</td>
                    @if($hasLast32)<td class="{{ $r->last32_points > 0 ? 'pst-pts' : 'pst-zero' }}">{{ $r->last32_points ? number_format($r->last32_points, 1) : '—' }}</td>@endif
                    @if($hasLast16)<td class="{{ $r->last16_points > 0 ? 'pst-pts' : 'pst-zero' }}">{{ $r->last16_points ? number_format($r->last16_points, 1) : '—' }}</td>@endif
                    @if($hasQF)<td class="{{ $r->quarterfinal_points > 0 ? 'pst-pts' : 'pst-zero' }}">{{ $r->quarterfinal_points ? number_format($r->quarterfinal_points, 1) : '—' }}</td>@endif
                    @if($hasSF)<td class="{{ $r->semifinal_points > 0 ? 'pst-pts' : 'pst-zero' }}">{{ $r->semifinal_points ? number_format($r->semifinal_points, 1) : '—' }}</td>@endif
                    @if($hasFinal)<td class="{{ $r->final_points > 0 ? 'pst-pts' : 'pst-zero' }}">{{ $r->final_points ? number_format($r->final_points, 1) : '—' }}</td>@endif
                    <td class="pst-total">{{ number_format($total, 1) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
