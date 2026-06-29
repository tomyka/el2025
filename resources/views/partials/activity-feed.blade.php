@if(!empty($activityFeed))
<div class="sb-card">
    <div class="sb-card-title">
        <i class="bi bi-lightning-fill sb-card-icon"></i> Aktyvumas
    </div>
    <div class="af-list">
        @foreach($activityFeed as $item)
        <div class="af-item">
            <span class="af-icon">{{ $item['type'] === 'bingo' ? '🎯' : '🔥' }}</span>
            <div class="af-body">
                @if($item['type'] === 'bingo')
                    <div class="af-text">{{ $item['game'] }}</div>
                    <div class="af-players">{{ $item['players'] }}</div>
                @else
                    <span class="af-user">{{ $item['username'] }}</span>
                    <span class="af-text"> serija ×{{ $item['length'] }}</span>
                @endif
                <div class="af-ago">{{ $item['ago'] }}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
