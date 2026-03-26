<div class="row">
    <div class="col col-xl-12 col-12" >
        <div>Praėjusio turo lyderiai:</div>
        <table class="table table-sm table-bordered table-hover">
            <thead>
                <tr class="table-primary">
                    <th class="text-center"><strong>Dalyvis</strong></th>
                    <th class="text-center"><strong>Turo taškai</strong></th>
                    @if (session('survivalGame')==1)
                        <td class="text-center"><strong>Išlikimas</strong></td>
                    @endif
                    <th class="d-none d-sm-table-cell text-center"><strong>Taškai</strong></th>
                    <th class="d-none d-sm-table-cell text-center"><strong>Vidurkis</strong></th>
                    <th class="text-center"><strong>Atspėta</strong></th>
                </tr>
            </thead>
            <tbody>
                @foreach($previousRoundPoints as $previousRoundPoint)
                <tr class="table-default">
                    <td class="text-left text-body">{{$previousRoundPoint['username']}}</td>
                    <td class="text-center text-body">{{$previousRoundPoint['pointResult']->full_points+$previousRoundPoint['pointSurvival']}}</td>
                    @if (session('survivalGame')==1)
                        <td class="text-center text-body">{{$previousRoundPoint['pointSurvival']}}</td>
                    @endif
                    <td class="d-none d-sm-table-cell text-center text-body">{{$previousRoundPoint['pointResult']->full_points}}</td>
                    <td class="d-none d-sm-table-cell text-center text-body">{{$previousRoundPoint['pointResult']->avg_points}}</td>
                    <td class="text-center text-body">{{$previousRoundPoint['pointResult']->correct_guess}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        </div>
    </div>
