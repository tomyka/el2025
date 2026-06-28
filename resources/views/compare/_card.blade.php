@php
    $myTotal    = $myData['userGamePoints'] + ($myData['userStreakPoints'] ?? 0)
                + $myData['standingPoints']->total_points + $myData['survivalPoints'];
    $theirTotal = $theirData['userGamePoints'] + ($theirData['userStreakPoints'] ?? 0)
                + $theirData['standingPoints']->total_points + $theirData['survivalPoints'];

    $myWins    = 0;
    $theirWins = 0;
    $draws     = 0;
    foreach ($rounds as $r) {
        if ($r['my'] > $r['their'])       $myWins++;
        elseif ($r['their'] > $r['my'])   $theirWins++;
        else                              $draws++;
    }
@endphp

<div class="cmp-header">
    <div class="cmp-player cmp-player-me">
        <div class="cmp-rank">{{ $myRank }}</div>
        <div class="cmp-username">{{ $myData['username'] }}</div>
        <div class="cmp-total {{ $myTotal > $theirTotal ? 'cmp-winner' : ($myTotal < $theirTotal ? 'cmp-loser' : '') }}">
            {{ number_format($myTotal, 1) }}
        </div>
    </div>

    <div class="cmp-vs">
        <span class="cmp-vs-label">vs</span>
        @if(count($rounds) > 0)
        <div class="cmp-record">
            <span class="cmp-record-w">{{ $myWins }}W</span>
            <span class="cmp-record-d">{{ $draws }}D</span>
            <span class="cmp-record-l">{{ $theirWins }}L</span>
        </div>
        @endif
    </div>

    <div class="cmp-player cmp-player-them">
        <div class="cmp-rank">{{ $theirRank }}</div>
        <div class="cmp-username">{{ $theirData['username'] }}</div>
        <div class="cmp-total {{ $theirTotal > $myTotal ? 'cmp-winner' : ($theirTotal < $myTotal ? 'cmp-loser' : '') }}">
            {{ number_format($theirTotal, 1) }}
        </div>
    </div>
</div>

<div class="cmp-section-title">Bendri statistikos</div>
<div class="cmp-stats-table">
    <div class="cmp-stats-row cmp-stats-header">
        <span class="cmp-stats-me">{{ $myData['username'] }}</span>
        <span class="cmp-stats-label"></span>
        <span class="cmp-stats-them">{{ $theirData['username'] }}</span>
    </div>

    @php
        $statRows = [
            ['label' => 'Rungtynių taškai',       'my' => $myData['userGamePoints'],              'their' => $theirData['userGamePoints'],              'fmt' => '1'],
            ['label' => 'Eigos taškai',            'my' => $myData['standingPoints']->total_points,'their' => $theirData['standingPoints']->total_points,'fmt' => '1'],
            ['label' => 'Išlikimo taškai',         'my' => $myData['survivalPoints'],               'their' => $theirData['survivalPoints'],               'fmt' => '0', 'hide_if_zero' => true],
            ['label' => 'Serija',                  'my' => $myData['userStreakPoints'] ?? 0,         'their' => $theirData['userStreakPoints'] ?? 0,         'fmt' => '1'],
            ['label' => 'Bingo',                   'my' => $myData['userGameBingo'],                'their' => $theirData['userGameBingo'],                'fmt' => '0'],
            ['label' => 'Vid. taškai / žaidimas',  'my' => $myData['averagePoints'],               'their' => $theirData['averagePoints'],               'fmt' => '1'],
        ];
    @endphp

    @foreach($statRows as $s)
    @if(!isset($s['hide_if_zero']) || ($s['my'] != 0 || $s['their'] != 0))
    @php $mVal = (float) $s['my']; $tVal = (float) $s['their']; @endphp
    <div class="cmp-stats-row">
        <span class="cmp-stats-me {{ $mVal > $tVal ? 'cmp-stat-win' : ($mVal < $tVal ? 'cmp-stat-lose' : '') }}">
            {{ $s['fmt'] == '1' ? number_format($mVal, 1) : number_format($mVal, 0) }}
        </span>
        <span class="cmp-stats-label">{{ $s['label'] }}</span>
        <span class="cmp-stats-them {{ $tVal > $mVal ? 'cmp-stat-win' : ($tVal < $mVal ? 'cmp-stat-lose' : '') }}">
            {{ $s['fmt'] == '1' ? number_format($tVal, 1) : number_format($tVal, 0) }}
        </span>
    </div>
    @endif
    @endforeach
</div>

@if(count($rounds) > 0)
<div class="cmp-section-title">Etapai</div>
<div class="cmp-rounds-table">
    <div class="cmp-round-row cmp-round-header">
        <span class="cmp-round-me">{{ $myData['username'] }}</span>
        <span class="cmp-round-name">Etapas</span>
        <span class="cmp-round-them">{{ $theirData['username'] }}</span>
    </div>
    @foreach($rounds as $r)
    @php $myWon = $r['my'] > $r['their']; $theirWon = $r['their'] > $r['my']; @endphp
    <div class="cmp-round-row">
        <span class="cmp-round-me {{ $myWon ? 'cmp-stat-win' : ($theirWon ? 'cmp-stat-lose' : '') }}">
            {{ number_format($r['my'], 1) }}
        </span>
        <span class="cmp-round-name">{{ $r['name'] }}</span>
        <span class="cmp-round-them {{ $theirWon ? 'cmp-stat-win' : ($myWon ? 'cmp-stat-lose' : '') }}">
            {{ number_format($r['their'], 1) }}
        </span>
    </div>
    @endforeach
</div>
@endif
