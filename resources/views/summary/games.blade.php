@extends('layouts.master_blank')
@section('content')

    <table class="table table-sm table-bordered table-hover">
        <tr class="align-middle">
            <th class="table-primary">Rungtynės</th>
            @foreach($predictionResults as $predictionResult)
                @if($predictionResult->gameID==1)
                    <th class="table-primary text-center" style="white-space: nowrap;"><strong>{{$predictionResult->username}}</strong></th>
                @endif
            @endforeach
        </tr>

            @foreach($games as $game)
            <tr class="align-middle">
                <td class="text-center align-middle" style="white-space: nowrap;">
                    <div>
                        <img src="{{URL::to('img/Teams/'.$game->homeTeam.'.png')}}" alt="HomeTeam" height=24> - <img src="{{URL::to('img/Teams/'.$game->awayTeam.'.png')}}" alt="AwayTeam" height=24>
                    </div>
                    <div>
                        <strong>
                            {{$game->homeScore}}:{{$game->awayScore}}
                        </strong>
                    </div>
                </td>
                @foreach($predictionResults as $predictionResult)
                    @if($predictionResult->gameID==$game->gameID)
                         <td @if ($predictionResult->fullPoints==0) class="table-dark text-center" @elseif ($predictionResult->winnerPoints<50) class="table-danger text-center" @else class="table-primary text-center" @endif>
                            <div >
                               <a href="#" title="" data-container="body" data-toggle="popover" data-bs-html="true" data-bs-trigger="hover" data-placement="right" data-bs-content="
                           <div class='row'>
                                <div class='col col-8 col-md-9'>
                                    <div>Už nugalėtoją:</div>
                                    <div>Už skirtumą:</div>
                                    <div>Už tikslų skirtumą:</div>
                                    <div>Koeficientas:</div>
                                    <div>Už koeficientą:</div>
                                </div>
                                <div class='col col-4 col-md-3'>
                                    <div align='right'><strong>{{number_format($predictionResult->winnerPoints,2)}}</strong></div>
                                    <div align='right'><strong>{{number_format($predictionResult->differencePoints,2)}}</strong></div>
                                    <div align='right'><strong>{{number_format($predictionResult->bingoPoints,2)}}</strong></div>
                                    <div align='right'><strong>{{number_format($predictionResult->odds,2)}}</strong></div>
                                    <div align='right'><strong>{{number_format($predictionResult->oddsPoints,2)}}</strong></div>
                                  </div>
                           </div>" data-bs-original-title="Rungtynių taškai">{{$predictionResult->fullPoints}}</a>
                            </div>
                            <div>
                                {{$predictionResult->homeScore}}:{{$predictionResult->awayScore}}
                            </div>
                        </td>
                    @endif
                @endforeach
            </tr>
            <tr>
        @endforeach



    </table>



@endsection