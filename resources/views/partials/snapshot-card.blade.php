@if(!empty($snapshot))
<div class="sb-card sn-card sn-card--h mb-3">
    <div class="sn-hrow">
        <div class="sn-stat">
            <div class="sn-val">#{{ $snapshot['rank'] }}</div>
            <div class="sn-lbl">vieta</div>
        </div>
        <div class="sn-divider"></div>
        <div class="sn-stat">
            <div class="sn-val">{{ number_format($snapshot['total'], 1) }}</div>
            <div class="sn-lbl">taškai</div>
        </div>
        <div class="sn-divider"></div>
        <div class="sn-stat">
            <div class="sn-val">{{ $snapshot['bingo_count'] > 0 ? '★ '.$snapshot['bingo_count'] : '—' }}</div>
            <div class="sn-lbl">bingo</div>
        </div>
        <div class="sn-divider"></div>
        <div class="sn-stat">
            <div class="sn-val">{{ $snapshot['streak'] > 0 ? $snapshot['streak'] : '—' }}</div>
            <div class="sn-lbl">serija</div>
        </div>
        @if($snapshot['rank_change'] !== null)
        <div class="sn-divider"></div>
        <div class="sn-stat">
            @if($snapshot['rank_change'] > 0)
                <div class="sn-val" style="color:var(--sb-green,#22c55e)">↑{{ $snapshot['rank_change'] }}</div>
            @elseif($snapshot['rank_change'] < 0)
                <div class="sn-val" style="color:var(--sb-red,#ef4444)">↓{{ abs($snapshot['rank_change']) }}</div>
            @else
                <div class="sn-val" style="color:var(--sb-muted)">—</div>
            @endif
            <div class="sn-lbl">pozicija (5 žaid.)</div>
        </div>
        @endif
        @if(count($snapshot['last5']) > 0)
        <div class="sn-dots sn-dots--end">
            <span class="sn-dots-lbl">Paskutinės {{ count($snapshot['last5']) }}</span>
            <div class="sn-dots-row">
                @foreach($snapshot['last5'] as $r)
                <div class="sn-dot sn-dot--{{ $r['type'] }}"
                     title="{{ $r['type'] === 'bingo' ? 'Bingo!' : ($r['type'] === 'win' ? 'Nugalėtojas' : 'Praleista') }}">
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endif
