<div class="sb-card">
    <div class="sb-card-title"><i class="bi bi-trophy-fill sb-card-icon"></i> Taškų lentelė</div>
    @php
        $feeRequired = isset($groupDetails) && $groupDetails->fee > 0;
    @endphp
    @foreach($points as $point)
    @php
        $total     = $point['userGamePoints'] + $point['standingPoints']->total_points + $point['survivalPoints'];
        $rank      = $loop->iteration;
        $isMe      = session('userID') == $point['userID'];
        $fullName  = trim($point['name'] . ' ' . $point['surname']);
        $feeHtml   = '';
        if ($feeRequired) {
            $feeHtml = $point['userFee'] > 0
                ? '<div class="text-success" style="font-size:.78rem">&#10003; Mokestis sumokėtas</div>'
                : '<div class="text-danger" style="font-size:.78rem">&#10007; Mokestis nesumokėtas</div>';
        }
        $breakdown = 'R: ' . $point['userGamePoints'] . ' &nbsp;·&nbsp; E: ' . $point['standingPoints']->total_points;
        if (session('survivalGame') == 1) {
            $breakdown .= ' &nbsp;·&nbsp; I: ' . $point['survivalPoints'];
        }
        $breakdown .= ' &nbsp;·&nbsp; Avg: ' . $point['averagePoints'];
        if ($point['userGameBingo'] > 0) {
            $breakdown .= ' &nbsp;·&nbsp; &#127919; ' . $point['userGameBingo'];
        }
        $popoverContent = '<div style="font-size:.8rem">'
            . ($fullName ? '<strong>' . e($fullName) . '</strong>' : '')
            . $feeHtml
            . '<div class="text-muted mt-1">' . $breakdown . '</div>'
            . '</div>';
    @endphp
    <div class="lb-row {{ $isMe ? 'lb-me-row' : '' }}">
        <div class="lb-rank {{ $rank <= 3 ? 'lb-rank-' . $rank : 'lb-rank-n' }}">{{ $rank }}</div>
        <div class="lb-name {{ $isMe ? 'lb-me-name' : '' }}">
            <span class="lb-name-btn"
                  tabindex="0"
                  data-bs-toggle="popover"
                  data-bs-trigger="click"
                  data-bs-html="true"
                  data-bs-title="{{ $fullName ?: $point['username'] }}"
                  data-bs-content="{{ $popoverContent }}">{{ $point['username'] }}</span>
        </div>
        <div class="lb-total {{ $isMe ? 'lb-me-total' : '' }}">{{ $total }}</div>
    </div>
    @endforeach
</div>
