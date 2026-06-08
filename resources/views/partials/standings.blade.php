<div class="sb-card">
    <div class="sb-card-title"><i class="bi bi-graph-up-arrow sb-card-icon"></i> Finalų dalyvių prognozės</div>
    <div class="stnl-list">
        @foreach($standings as $standing)
        <div class="stnl-row">
            <span class="stnl-team">
                <img class="standing-flag"
                     src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($standing->team)) . '.svg') }}"
                     alt="{{ $standing->team }}">
                <span class="stnl-name">{{ $standing->team }}</span>
            </span>
            <span class="stnl-preds">
                <span class="standing-pos-badge pos-1 {{ $standing->firstPlacePrediction  == 0 ? 'pos-zero' : '' }}">{{ $standing->firstPlacePrediction }}</span>
                <span class="standing-pos-badge pos-2 {{ $standing->secondPlacePrediction == 0 ? 'pos-zero' : '' }}">{{ $standing->secondPlacePrediction }}</span>
                <span class="standing-pos-badge pos-3 {{ $standing->thirdPlacePrediction  == 0 ? 'pos-zero' : '' }}">{{ $standing->thirdPlacePrediction }}</span>
                <span class="standing-pos-badge pos-4 {{ $standing->fourthPlacePrediction == 0 ? 'pos-zero' : '' }}">{{ $standing->fourthPlacePrediction }}</span>
            </span>
        </div>
        @endforeach
    </div>
</div>
