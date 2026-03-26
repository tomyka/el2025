<div class="row">
    <div class="col col-12" >
        Taškų lentelė:
        <table class="table table-sm table-bordered">
            <thead>
                <tr class="table-primary">
                        <td class="text-center"><strong>#</strong></td>
                        <td class="text-center"><strong>Dalyvis</strong></td>
                        <td class="text-center"><strong>Taškai</strong></td>
                        <td class="d-none d-sm-table-cell text-center"><strong>Vidurkis</strong></td>
                        <td class="d-none d-sm-table-cell text-center"><abbr title="Atspėta tiksliai"><strong>Bingo</strong></abbr></td>
                        @if (session('survivalGame')==1)
                            <td class="text-center"><strong>Išlikimas</strong></td>
                        @endif
                        <td class="text-center"><strong>Eiga</strong></td>
                        <td class="text-center"><strong>Viso:</strong></td>
                </tr>
            </thead>
            <tbody>

                @foreach($points as $point)
                    <tr class="table-default @if (session('userID')==$point['userID']) {{"text-primary"}} @endif" >
                        <td class="text-center">{{$loop->iteration}}</td>
                        <td class="text-left">{{$point['username']}}</td>
                        <td class="text-center">{{$point['userGamePoints']}}</td>
                        <td class="d-none d-sm-table-cell text-center">{{$point['averagePoints']}}</td>
                        <td class="d-none d-sm-table-cell text-center">{{$point['userGameBingo']}}</td>
                        @if (session('survivalGame')==1)
                            <td class="text-center">{{$point['survivalPoints']}}</td>
                        @endif
                        <td class="text-center">
                            <a href="#" title="" data-container="body" data-toggle="popover" data-bs-html="true" data-bs-trigger="hover" data-placement="bottom" data-bs-content="
                               <div class='row'>
                                    <div class='col col-9 col-md-9'>
                                        <div>Grupės vietos:</div>
    <!--                                    <div>Patekimas į aštuntfinalį:</div> -->
                                        <div>Patekimas į ketvirtfinalį:</div>
                                        <div>Finalas:</div>
                                    </div>
                                    <div class='col col-3 col-md-3'>
                                        <div align='right'><strong>{{$point['standingPoints']->group_position_points}}</strong></div>
    <!--                                    <div align='right'><strong>{{$point['standingPoints']->last16_points}}</strong></div> -->
                                        <div align='right'><strong>{{$point['standingPoints']->quarterfinal_points}}</strong></div>
                                        <div align='right'><strong>{{$point['standingPoints']->final_points}}</strong></div>
                                    </div>
                               </div>" data-bs-original-title="Eigos taškai">{{$point['standingPoints']->total_points}}</a></td>
                        <td class="text-center">{{$point['userGamePoints']+$point['standingPoints']->total_points+$point['survivalPoints']}}</td>
                    </tr>

                    @endforeach

            </tbody>
        </table>
    </div>
</div>




