@if(!empty($snapshot))
<div class="sb-card sn-card">
    <div class="sn-grid">
        <div class="sn-stat">
            <div class="sn-val">#{{ $snapshot['rank'] }}</div>
            <div class="sn-lbl">vieta</div>
        </div>
        <div class="sn-stat">
            <div class="sn-val">{{ number_format($snapshot['total'], 1) }}</div>
            <div class="sn-lbl">taškai</div>
        </div>
        <div class="sn-stat">
            <div class="sn-val">{{ $snapshot['bingo_count'] > 0 ? '★ '.$snapshot['bingo_count'] : '—' }}</div>
            <div class="sn-lbl">bingo</div>
        </div>
        <div class="sn-stat">
            <div class="sn-val">{{ number_format($snapshot['average'], 1) }}</div>
            <div class="sn-lbl">vid. / žaidimas</div>
        </div>
    </div>
    @if(count($snapshot['last5']) > 0)
    <div class="sn-dots">
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
@endif
