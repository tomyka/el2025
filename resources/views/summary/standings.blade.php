@extends('layouts.master')
@section('containerClass', 'sb-container--fluid')
@section('content')

@php
    $players = collect($predictionStandings)
        ->pluck('username')->unique()->values()->toArray();

    $psMap = [];
    foreach ($predictionStandings as $ps) {
        $psMap[$ps->team_id][$ps->username] = $ps;
    }

    $teamGroups = $teams->groupBy('group_name')->sortKeys();

    if (!function_exists('sstPopover')) {
        function sstPopover(object $ps): string {
            $stageDefs = [
                ['label' => 'Grupių etapas',    'pts' => 'group_position_points', 'odds' => 'group_position_odds'],
                ['label' => 'Šešioliktfinalis', 'pts' => 'last32_points',         'odds' => 'last32_odds'],
                ['label' => 'Aštuntfinalis',    'pts' => 'last16_points',         'odds' => 'last16_odds'],
                ['label' => 'Ketvirtfinalis',   'pts' => 'quarterfinal_points',   'odds' => 'quarterfinal_odds'],
                ['label' => 'Pusfinalis',       'pts' => 'semifinal_points',      'odds' => 'semifinal_odds'],
                ['label' => 'Finalas',          'pts' => 'final_points',          'odds' => 'final_odds'],
            ];
            $rows = '';
            foreach ($stageDefs as $s) {
                $ptVal  = (float)($ps->{$s['pts']} ?? 0);
                $odVal  = isset($ps->{$s['odds']}) ? (float)$ps->{$s['odds']} : null;
                if ($ptVal <= 0) continue;
                $rows .= "<div class='sr-pop-row'><span>{$s['label']}</span><strong>" . number_format($ptVal, 1) . "</strong></div>";
                if ($odVal !== null) {
                    $base = round($ptVal / (1 + $odVal), 1);
                    $mult = round(1 + $odVal, 2);
                    $rows .= "<div class='sr-pop-row sr-pop-sub'><span>Spėjimo taškai</span><strong>" . number_format($base, 1) . "</strong></div>"
                           . "<div class='sr-pop-row sr-pop-sub'><span>Koeficientas</span><strong>×" . number_format($mult, 2) . "</strong></div>";
                }
            }
            return $rows ? "<div class='sr-pop sr-pop-sm'>{$rows}</div>" : '';
        }
    }
@endphp

    // Returns a CSS class based on predicted vs actual value (nullable = pending)
    $cmpClass = function($predicted, $actual) {
        if ($predicted === null || $predicted == 0) return 'sst-none';
        if ($actual   === null)                     return 'sst-pending';
        return $predicted == $actual ? 'sst-hit' : 'sst-miss';
    };

    $advClass = function($predicted, $actual) {
        if (!$predicted) return 'sst-none';
        if ($actual === null) return 'sst-pending';
        return ($predicted == 1 && $actual == 1) ? 'sst-hit' : 'sst-miss';
    };
@endphp

<div class="sb-card p-0">

    {{-- Legend --}}
    <div class="sst-legend">
        <div class="sst-legend-colors">
            <span class="sst-badge sst-hit">2</span> Pataikyta
            <span class="sst-badge sst-miss">2</span> Praleista
            <span class="sst-badge sst-pending">2</span> Laukiama
            <span class="sst-badge sst-none">2</span> Nespėta
        </div>
        <div class="sst-legend-stages">
            <span class="sst-stage-lbl">G</span>Grupė
            <span class="sst-stage-lbl">32</span>1/16
            <span class="sst-stage-lbl">16</span>1/8
            <span class="sst-stage-lbl">QF</span>1/4
            <span class="sst-stage-lbl">SF</span>1/2
            <span class="sst-stage-lbl">F</span>Finalas
        </div>
    </div>

    <div class="table-responsive">
        <table class="sst-table">
            <thead>
                <tr class="sst-head-row">
                    <th class="sst-team-hdr">Komanda</th>
                    @foreach($players as $player)
                    <th class="sst-player-hdr">{{ $player }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($teamGroups as $groupName => $groupTeams)
                @if($groupName)
                <tr class="sst-group-row">
                    <td colspan="{{ count($players) + 1 }}">Grupė {{ $groupName }}</td>
                </tr>
                @endif
                @foreach($groupTeams as $team)
                <tr class="sst-team-row">
                    <td class="sst-team-cell">
                        <img src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($team->team)) . '.svg') }}"
                             class="sst-flag" alt="{{ $team->team }}">
                        <span class="sst-team-name">{{ $team->team }}</span>
                    </td>
                    @foreach($players as $player)
                    @php
                        $ps = $psMap[$team->id][$player] ?? null;
                        $totalPts = $ps
                            ? (($ps->group_position_points ?? 0) + ($ps->last32_points ?? 0) + ($ps->last16_points ?? 0)
                               + ($ps->quarterfinal_points ?? 0) + ($ps->semifinal_points ?? 0) + ($ps->final_points ?? 0))
                            : 0;
                        $pop = $ps && $totalPts > 0 ? sstPopover($ps) : '';
                    @endphp
                    <td class="sst-pred-cell">
                        @if($ps)
                        <div class="sst-badges">
                            {{-- Group position --}}
                            <span class="sst-badge sst-pos {{ $cmpClass($ps->group_position, $team->group_position) }}"
                                  title="Grupės vieta: spėta {{ $ps->group_position ?? '—' }}">{{ $ps->group_position ?? '—' }}</span>
                            {{-- Knockout rounds --}}
                            <span class="sst-badge {{ $advClass($ps->last32, $team->last32) }}"
                                  title="1/16">{{ $ps->last32 ? '✓' : '—' }}</span>
                            <span class="sst-badge {{ $advClass($ps->last16, $team->last16) }}"
                                  title="1/8">{{ $ps->last16 ? '✓' : '—' }}</span>
                            <span class="sst-badge {{ $advClass($ps->quarterfinal, $team->quarterfinal) }}"
                                  title="1/4">{{ $ps->quarterfinal ? '✓' : '—' }}</span>
                            <span class="sst-badge {{ $advClass($ps->semifinal, $team->semifinal) }}"
                                  title="1/2">{{ $ps->semifinal ? '✓' : '—' }}</span>
                            {{-- Final position --}}
                            <span class="sst-badge sst-fin {{ $cmpClass($ps->final, $team->final) }}"
                                  title="Finalas: spėta {{ $ps->final ?? '—' }}">{{ $ps->final ?? '—' }}</span>
                        </div>
                        @if($totalPts > 0)
                        <div class="sst-pts {{ $pop ? 'pst-hoverable' : '' }}"
                             @if($pop) data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-placement="top" data-bs-content="{{ $pop }}" @endif
                        >{{ number_format($totalPts, 1) }}</div>
                        @endif
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.pst-hoverable[data-bs-toggle="popover"]').forEach(function (el) {
        new bootstrap.Popover(el, { container: 'body', html: true });
    });
});
</script>

@endsection
