@if(!empty($activityFeed))
<div class="sb-card">
    <div class="sb-card-title">
        <i class="bi bi-lightning-fill sb-card-icon"></i> Aktyvumas
    </div>
    <div class="af-list">
        @foreach($activityFeed as $item)
        <div class="af-item">
            <span class="af-icon">{{ $item['icon'] }}</span>
            <div class="af-body">
                <span class="af-user">{{ $item['username'] }}</span>
                <span class="af-text"> {{ $item['text'] }}</span>
                <div class="af-ago">{{ $item['ago'] }}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
