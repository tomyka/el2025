<div class="sb-card">
    <div class="sb-card-title">📊 Finalų dalyvių prognozės</div>
    <table class="table table-sm table-bordered mb-0">
        <thead>
            <tr class="table-primary">
                <td class="text-center"><strong>Komanda</strong></td>
                <td class="text-center"><strong>1 vieta</strong></td>
                <td class="text-center"><strong>2 vieta</strong></td>
                <td class="text-center"><strong>3 vieta</strong></td>
                <td class="text-center"><strong>4 vieta</strong></td>
            </tr>
        </thead>
        <tbody>
            @foreach($standings as $standing)
            <tr class="table-default">
                <td class="text-left">{{ $standing->team }}</td>
                <td class="text-center">{{ $standing->firstPlacePrediction }}</td>
                <td class="text-center">{{ $standing->secondPlacePrediction }}</td>
                <td class="text-center">{{ $standing->thirdPlacePrediction }}</td>
                <td class="text-center">{{ $standing->fourthPlacePrediction }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
