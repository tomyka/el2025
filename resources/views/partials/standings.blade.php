<div class="sb-card">
    <div class="sb-card-title"><i class="bi bi-graph-up-arrow sb-card-icon"></i> Finalų dalyvių prognozės</div>
    <div class="standings-grid">
        @foreach($standings as $standing)
        <div class="standing-card">
            <div class="standing-card-header">
                <img class="standing-flag"
                     src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($standing->team)) . '.svg') }}"
                     alt="{{ $standing->team }}">
                <span class="standing-team">{{ $standing->team }}</span>
            </div>
            <div class="standing-positions">
                <div class="standing-pos">
                    <span class="standing-pos-badge pos-1">1</span>
                    <span class="standing-pos-count {{ $standing->firstPlacePrediction == 0 ? 'text-muted' : '' }}">{{ $standing->firstPlacePrediction }}</span>
                </div>
                <div class="standing-pos">
                    <span class="standing-pos-badge pos-2">2</span>
                    <span class="standing-pos-count {{ $standing->secondPlacePrediction == 0 ? 'text-muted' : '' }}">{{ $standing->secondPlacePrediction }}</span>
                </div>
                <div class="standing-pos">
                    <span class="standing-pos-badge pos-3">3</span>
                    <span class="standing-pos-count {{ $standing->thirdPlacePrediction == 0 ? 'text-muted' : '' }}">{{ $standing->thirdPlacePrediction }}</span>
                </div>
                <div class="standing-pos">
                    <span class="standing-pos-badge pos-4">4</span>
                    <span class="standing-pos-count {{ $standing->fourthPlacePrediction == 0 ? 'text-muted' : '' }}">{{ $standing->fourthPlacePrediction }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
