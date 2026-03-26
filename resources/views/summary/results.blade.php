@extends('layouts.master_blank')
@section('content')

    <table class="table table-sm table-bordered">
        <tr class="align-middle">
            <th class="table-dark">Rungtynės</th>
            @foreach($predictionResults as $predictionResult)
                @if($predictionResult->game_id==1)
                    <th class="table-dark text-center" style="white-space: nowrap;"><strong>{{$predictionResult->username}}</strong></th>
                @endif
            @endforeach
        </tr>

        @foreach($games as $game)
            <tr class="align-middle">
                <td class="text-center align-middle" style="white-space: nowrap;">
                    <div>
                        <img src="{{URL::to('img/Teams/'.$game->home_team.'.png')}}" alt="HomeTeam" height=24> - <img src="{{URL::to('img/Teams/'.$game->away_team.'.png')}}" alt="AwayTeam" height=24>
                    </div>
                    <div>
                        <strong>
                            {{$game->home_team_score}}:{{$game->away_team_score}}
                        </strong>
                    </div>
                </td>
                @foreach($predictionResults as $predictionResult)
                    @if($predictionResult->game_id==$game->id)
                        <td>

                            <div class="text-center">
                                <a class="text-primary" href="#" title="" data-container="body" data-toggle="popover" data-bs-html="true" data-bs-trigger="hover" data-placement="right" data-bs-content="
                               <div class='row'>
                                    <div class='col col-8 col-md-9'>
                                        <div>Už nugalėtoją:</div>
                                        <div>Už skirtumą:</div>
                                        <div>Už tikslų skirtumą:</div>
                                        <div>Koeficientas:</div>
                                        <div>Už koeficientą:</div>
                                    </div>
                                    <div class='col col-4 col-md-3'>
                                        <div align='right'><strong>{{number_format($predictionResult->winner_points,2)}}</strong></div>
                                        <div align='right'><strong>{{number_format($predictionResult->difference_points,2)}}</strong></div>
                                        <div align='right'><strong>{{number_format($predictionResult->bingo_points,2)}}</strong></div>
                                        <div align='right'><strong>{{number_format($predictionResult->odds,2)}}</strong></div>
                                        <div align='right'><strong>{{number_format($predictionResult->odds_points,2)}}</strong></div>
                                      </div>
                               </div>" data-bs-original-title="Rungtynių taškai">{{$predictionResult->full_points}}</a>
                            </div>

                            <div class="text-center">
                                <span @if (!isset($game->home_team_score)) class="badge bg-light" @elseif ($predictionResult->winner_points<5) class="badge bg-danger" @else class="badge bg-success" @endif>{{$predictionResult->home_team_score}}@if ($predictionResult->home_team_id==$predictionResult->game_winner_id)*@endif : {{$predictionResult->away_team_score}}@if ($predictionResult->away_team_id==$predictionResult->game_winner_id)*@endif</span>


                            </div>
                        </td>
                    @endif
                @endforeach
            </tr>
            <tr>
        @endforeach



    </table>



@endsection
