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

    if (!function_exists('sstBadgePopover')) {
        function sstBadgePopover(string $label, float $ptVal, ?float $odVal): string {
            $rows = "<div class='sr-pop-row'><span>{$label}</span><strong>" . number_format($ptVal, 1) . "</strong></div>";
            if ($odVal !== null && $odVal > 0 && $ptVal > 0) {
                $base = round($ptVal / (1 + $odVal), 1);
                $mult = round(1 + $odVal, 2);
                $rows .= "<div class='sr-pop-row sr-pop-sub'><span>" . __('Spėjimo taškai') . "</span><strong>" . number_format($base, 1) . "</strong></div>"
                       . "<div class='sr-pop-row sr-pop-sub'><span>" . __('Koeficientas') . "</span><strong>×" . number_format($mult, 2) . "</strong></div>";
            }
            return "<div class='sr-pop sr-pop-sm'>{$rows}</div>";
        }
    }

    if (!function_exists('sstPopover')) {
        function sstPopover(object $ps): string {
            $stageDefs = [
                ['label' => __('Grupių etapas'),    'pts' => 'group_position_points', 'odds' => 'group_position_odds'],
                ['label' => __('Šešioliktfinalis'), 'pts' => 'last32_points',         'odds' => 'last32_odds'],
                ['label' => __('Aštuntfinalis'),    'pts' => 'last16_points',         'odds' => 'last16_odds'],
                ['label' => __('Ketvirtfinalis'),   'pts' => 'quarterfinal_points',   'odds' => 'quarterfinal_odds'],
                ['label' => __('Pusfinalis'),       'pts' => 'semifinal_points',      'odds' => 'semifinal_odds'],
                ['label' => __('Finalas'),          'pts' => 'final_points',          'odds' => 'final_odds'],
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
                    $rows .= "<div class='sr-pop-row sr-pop-sub'><span>" . __('Spėjimo taškai') . "</span><strong>" . number_format($base, 1) . "</strong></div>"
                           . "<div class='sr-pop-row sr-pop-sub'><span>" . __('Koeficientas') . "</span><strong>×" . number_format($mult, 2) . "</strong></div>";
                }
            }
            return $rows ? "<div class='sr-pop sr-pop-sm'>{$rows}</div>" : '';
        }
    }

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

    // Final placement prediction, judged against whether the team even reached the semifinal.
    $finalClass = function($predictedFinal, $predictedSemifinal, $team) {
        if ($predictedFinal === null || $predictedFinal == 0) return 'sst-none';

        // Team was eliminated before the semifinal — this pick can never come true.
        if ($team->semifinal !== null && (int)$team->semifinal !== 1) return 'sst-miss';

        // Team's final placement isn't decided yet (semis or the final/3rd-place game still pending).
        if ($team->final === null) return 'sst-pending';

        if ((int)$predictedFinal === (int)$team->final) return 'sst-hit';

        // Wrong exact final slot, but correctly predicted this team would reach the semifinal.
        return $predictedSemifinal == 1 ? 'sst-partial' : 'sst-miss';
    };
@endphp

<div class="sb-card p-0">

    {{-- Legend --}}
    <div class="sst-legend">
        <div class="sst-legend-colors">
            <span class="sst-badge sst-hit">2</span> {{ __('Pataikyta') }}
            <span class="sst-badge sst-miss">2</span> {{ __('Praleista') }}
            <span class="sst-badge sst-partial">2</span> {{ __('Pusfinalis teisingas, finalo vieta ne') }}
            <span class="sst-badge sst-pending">2</span> {{ __('Laukiama') }}
            <span class="sst-badge sst-none">2</span> {{ __('Nespėta') }}
        </div>
        <div class="sst-legend-stages">
            <span class="sst-stage-lbl">G</span>{{ __('Grupė') }}
            <span class="sst-stage-lbl">32</span>1/16
            <span class="sst-stage-lbl">16</span>1/8
            <span class="sst-stage-lbl">QF</span>1/4
            <span class="sst-stage-lbl">SF</span>1/2
            <span class="sst-stage-lbl">F</span>{{ __('Finalas') }}
        </div>
    </div>

    <div class="table-responsive">
        <table class="sst-table">
            <thead>
                <tr class="sst-head-row">
                    <th class="sst-team-hdr">{{ __('Komanda') }}</th>
                    @foreach($players as $player)
                    <th class="sst-player-hdr">{{ $player }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($teamGroups as $groupName => $groupTeams)
                @if($groupName)
                <tr class="sst-group-row">
                    <td colspan="{{ count($players) + 1 }}">{{ __('Grupė') }} {{ $groupName }}</td>
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

                        if ($ps) {
                            $gpClass  = $cmpClass($ps->group_position, $team->group_position);
                            $l32Class = $advClass($ps->last32, $team->last32);
                            $l16Class = $advClass($ps->last16, $team->last16);
                            $qfClass  = $advClass($ps->quarterfinal, $team->quarterfinal);
                            $sfClass  = $advClass($ps->semifinal, $team->semifinal);
                            $finClass = $finalClass($ps->final, $ps->semifinal, $team);

                            $l32Symbol = $ps->last32 ? ($l32Class === 'sst-miss' ? '✗' : '✓') : '—';
                            $l16Symbol = $ps->last16 ? ($l16Class === 'sst-miss' ? '✗' : '✓') : '—';
                            $qfSymbol  = $ps->quarterfinal ? ($qfClass === 'sst-miss' ? '✗' : '✓') : '—';
                            $sfSymbol  = $ps->semifinal ? ($sfClass === 'sst-miss' ? '✗' : '✓') : '—';

                            $gpPop  = ($ps->group_position ?? null) !== null
                                ? sstBadgePopover(__('Grupės vieta'), (float)($ps->group_position_points ?? 0), isset($ps->group_position_odds) ? (float)$ps->group_position_odds : null)
                                : '';
                            $l32Pop = $ps->last32 ? sstBadgePopover(__('Šešioliktfinalis'), (float)($ps->last32_points ?? 0), isset($ps->last32_odds) ? (float)$ps->last32_odds : null) : '';
                            $l16Pop = $ps->last16 ? sstBadgePopover(__('Aštuntfinalis'),    (float)($ps->last16_points ?? 0), isset($ps->last16_odds) ? (float)$ps->last16_odds : null) : '';
                            $qfPop  = $ps->quarterfinal ? sstBadgePopover(__('Ketvirtfinalis'), (float)($ps->quarterfinal_points ?? 0), isset($ps->quarterfinal_odds) ? (float)$ps->quarterfinal_odds : null) : '';
                            $sfPop  = $ps->semifinal ? sstBadgePopover(__('Pusfinalis'), (float)($ps->semifinal_points ?? 0), isset($ps->semifinal_odds) ? (float)$ps->semifinal_odds : null) : '';
                            $finPop = ($ps->final ?? null) !== null
                                ? sstBadgePopover(__('Finalas'), (float)($ps->final_points ?? 0), isset($ps->final_odds) ? (float)$ps->final_odds : null)
                                : '';
                        }
                    @endphp
                    <td class="sst-pred-cell">
                        @if($ps)
                        <div class="sst-badges">
                            {{-- Group position --}}
                            <span class="sst-badge sst-pos {{ $gpClass }} {{ $gpPop ? 'sst-pop-badge' : '' }}"
                                  @if($gpPop) data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-placement="top" data-bs-content="{{ $gpPop }}" @else title="{{ __('Grupės vieta: spėta') }} {{ $ps->group_position ?? '—' }}" @endif
                            >{{ $ps->group_position ?? '—' }}</span>
                            {{-- Knockout rounds --}}
                            <span class="sst-badge {{ $l32Class }} {{ $l32Pop ? 'sst-pop-badge' : '' }}"
                                  @if($l32Pop) data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-placement="top" data-bs-content="{{ $l32Pop }}" @else title="1/16" @endif
                            >{{ $l32Symbol }}</span>
                            <span class="sst-badge {{ $l16Class }} {{ $l16Pop ? 'sst-pop-badge' : '' }}"
                                  @if($l16Pop) data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-placement="top" data-bs-content="{{ $l16Pop }}" @else title="1/8" @endif
                            >{{ $l16Symbol }}</span>
                            <span class="sst-badge {{ $qfClass }} {{ $qfPop ? 'sst-pop-badge' : '' }}"
                                  @if($qfPop) data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-placement="top" data-bs-content="{{ $qfPop }}" @else title="1/4" @endif
                            >{{ $qfSymbol }}</span>
                            <span class="sst-badge {{ $sfClass }} {{ $sfPop ? 'sst-pop-badge' : '' }}"
                                  @if($sfPop) data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-placement="top" data-bs-content="{{ $sfPop }}" @else title="1/2" @endif
                            >{{ $sfSymbol }}</span>
                            {{-- Final position --}}
                            <span class="sst-badge sst-fin {{ $finClass }} {{ $finPop ? 'sst-pop-badge' : '' }}"
                                  @if($finPop) data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-placement="top" data-bs-content="{{ $finPop }}" @else title="{{ __('Finalas: spėta') }} {{ $ps->final ?? '—' }}" @endif
                            >{{ $ps->final ?? '—' }}</span>
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
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
        new bootstrap.Popover(el, { container: 'body', html: true });
    });
});
</script>

@endsection
