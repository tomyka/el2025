<div class="sb-card">
    <div class="sb-card-title">
        <i class="bi bi-bar-chart-steps sb-card-icon"></i> Eigos taškai
    </div>

    @php
        $hasLast16 = collect($predictionStandingsPoints)->sum('last16_points') > 0;
        $hasQF     = collect($predictionStandingsPoints)->sum('quarterfinal_points') > 0;
        $hasFinal  = collect($predictionStandingsPoints)->sum('final_points') > 0;
        $rows      = collect($predictionStandingsPoints)->filter(
            fn($r) => $r->group_position_points + $r->last16_points + $r->quarterfinal_points + $r->final_points > 0
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
                    @if($hasLast16)<th title="1/8 etapas">1/8</th>@endif
                    @if($hasQF)<th title="1/4 etapas">1/4</th>@endif
                    @if($hasFinal)<th title="Finalas">F</th>@endif
                    <th>Viso</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                @php
                    $total = $r->group_position_points + $r->last16_points + $r->quarterfinal_points + $r->final_points;
                @endphp
                <tr>
                    <td class="pst-team">{{ $r->team }}</td>
                    <td class="{{ $r->group_position_points > 0 ? 'pst-pts' : 'pst-zero' }}">{{ $r->group_position_points ?: '—' }}</td>
                    @if($hasLast16)<td class="{{ $r->last16_points > 0 ? 'pst-pts' : 'pst-zero' }}">{{ $r->last16_points ?: '—' }}</td>@endif
                    @if($hasQF)<td class="{{ $r->quarterfinal_points > 0 ? 'pst-pts' : 'pst-zero' }}">{{ $r->quarterfinal_points ?: '—' }}</td>@endif
                    @if($hasFinal)<td class="{{ $r->final_points > 0 ? 'pst-pts' : 'pst-zero' }}">{{ $r->final_points ?: '—' }}</td>@endif
                    <td class="pst-total">{{ $total }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
