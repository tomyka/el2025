<div class="container-fluid">
    <p>Taškų lentelė:</p>

        <table class="table-sm table-bordered">
            <thead>
                <tr class="table-primary">
                        <td align="center"><strong>#</strong></td>
                        <td align="center"><strong>Komanda</strong></td>
                </tr>
            </thead>
            <tbody>
                @foreach($points as $point)

                <tr  @if (session('userID')==$point['userID']) {{"style=color:#DF4400;font-weight:bold;"}} @endif>
                    <td align="center">{{$loop->iteration}}</td>
                    <td align="left">{{$point['username']}}</td>
                    <td align="center">{{$point['userGamePoints']}}</td>
                    <td scope="col" class="d-none d-sm-table-cell" align="center">{{$point['averagePoints']}}</td>
                    <td scope="col" class="d-none d-sm-table-cell" align="center">{{$point['userGameBingo']}}</td>
                @if (session('survivalGame')==1)
                    <td scope="col" class="d-none d-sm-table-cell" align="center">{{$point['survivalPoints']}}</td>
                @endif
                    <td align="center"><a href="#" title="" data-container="body" data-toggle="popover" data-html="true" data-trigger="hover" data-placement="bottom" data-content="
                           <div class='row'>
                                <div class='col col-9 col-md-9'>
                                    <div>Pirmos grupės vietos:</div>
                                    <div>Patekimas į antrą grupę:</div>
                                    <div>Antros grupės vietos:</div>
                                    <div>Patekimas į ketvirtfinalį:</div>
                                    <div>Finalas:</div>
                                </div>
                                <div class='col col-3 col-md-3'>
                                    <div align='right'><strong>{{$point['tablePoints']->firstRoundGroupPoints}}</strong></div>
                                    <div align='right'><strong>{{$point['tablePoints']->firstRoundQualifierPoints}}</strong></div>
                                    <div align='right'><strong>{{$point['tablePoints']->secondRoundGroupPoints}}</strong></div>
                                    <div align='right'><strong>{{$point['tablePoints']->quarterfinalPoints}}</strong></div>
                                    <div align='right'><strong>{{$point['tablePoints']->finalPoints}}</strong></div>
                                </div>
                           </div>" data-original-title="Eigos taškai">{{$point['tablePoints']->totalPoints}}</a></td>
                    <td align="center">{{$point['userGamePoints']+$point['tablePoints']->totalPoints}}</td>
                </tr>

                @endforeach
            </tbody>
        </table>




</div>
