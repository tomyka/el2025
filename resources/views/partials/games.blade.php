<div class="row">
    <div class="col col-xl-12 col-12" >
        Artimiausios rungtynės:
        <table class="table table-sm table-bordered">
            <thead>
                <tr class="table-primary">
                    <th class="d-none d-sm-table-cell text-center"><strong>Nr.</strong></th>
                    <th class="text-center"><strong>Šeimininkai</strong></th>
                    <th class="text-center"><strong>Svečiai</strong></th>
                    <th class="d-none d-sm-table-cell text-center" ><strong>Rezultatas</strong></th>
                    <th class="text-center"><strong>Spėjimas</strong></th>
                    <th class="text-center"><strong>Taškai</strong></th>
                </tr>
            </thead>
            <tbody>

                @foreach($predictionGames as $predictionGame)
                <tr class="table-default">
                    <td class="d-none d-sm-table-cell text-center text-body" >{{$predictionGame['gameDetails']->id}}</td>
                    <td class="text-left">
                        {{$predictionGame['gameDetails']->home_team}}
                    </td>
                    <td class="text-left">
                        {{$predictionGame['gameDetails']->away_team}}
                    </td>
                    <td class="d-none d-sm-table-cell text-center">
                        {{$predictionGame['gameDetails']->home_team_score}}
                        &nbsp;:&nbsp;
                        {{$predictionGame['gameDetails']->away_team_score}}
                    </td>
                    <td class="text-center">
                        {{$predictionGame['gameDetails']->p_home_team_score}}
                        &nbsp;:&nbsp;
                        {{$predictionGame['gameDetails']->p_away_team_score}}</td>
                    <td class="text-center">
                    <div>
                        <a href="#" title="" data-container="body" data-toggle="popover" data-bs-html="true" data-bs-trigger="hover" data-placement="right" data-bs-content="
                                    <div class='row'>
                                        <div class='col col-8 col-md-9'>
                                            <div>Už nugalėtoją:</div>
                                            <div>Už skirtumą:</div>
                                            <div>Už tikslų skirtumą:</div>
                                            <div>Už koeficientą:</div>
                                            <div>Koeficientas:</div>
                                        </div>
                                        <div class='col col-4 col-md-3'>
                                            <div><strong>{{number_format($predictionGame['gameDetails']->winner_points,2)}}</strong></div>
                                            <div><strong>{{number_format($predictionGame['gameDetails']->difference_points,2)}}</strong></div>
                                            <div><strong>{{number_format($predictionGame['gameDetails']->bingo_points,2)}}</strong></div>
                                            <div><strong>{{number_format($predictionGame['gameDetails']->odds_points,2)}}</strong></div>
                                            <div><strong>{{number_format($predictionGame['gameDetails']->odds,2)}}</strong></div>
                                         </div>
                                    </div>
                                " data-bs-original-title="Rungtynių taškai">{{$predictionGame['gameDetails']->full_points}}</a>
                    </div>
                    </td>

                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
