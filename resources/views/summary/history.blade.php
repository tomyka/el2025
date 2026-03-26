@extends('layouts.master')
@section('content')

        @guest
        @else
            <div class="container-fluid">
                <p>Įvykę varžybos:</p>

                <div class="container-fluid">
                    <table class="table-sm table-bordered">

                        <tr class="table-primary">
                            <td class="d-none d-sm-table-cell table-primary text-center text-body"><strong>Nr.</strong></td>
                            <td class="text-center text-body"><strong>Šeimininkai</strong></td>
                            <td class="text-center text-body"><strong>Svečiai</strong></td>
                            <td class="text-center text-body"><strong>Rezultatas</strong></td>
                            <td class="text-center text-body"><strong>Spėjimas</strong></td>
                            <td class="text-center text-body"><strong>Taškai</strong></td>
                        </tr>

                        <tbody>
                        @foreach($predictionResults as $predictionResult)
                            <tr class="table-primary">
                                <td class="d-none d-sm-table-cell text-center text-dark">{{$predictionResult->game_id}}</td>
                                <td class="text-left text-dark">{{$predictionResult->home_team}}</td>
                                <td class="text-left text-dark">{{$predictionResult->away_team}}</td>
                                <td class="text-center text-dark">
                                    {{$predictionResult->home_team_score}}
                                    @if ($predictionResult->game_winner_id==$predictionResult->home_team_id)
                                        *
                                    @endif
                                    &nbsp;:&nbsp;
                                    {{$predictionResult->away_team_score}}
                                    @if ($predictionResult->game_winner_id==$predictionResult->away_team_id)
                                        *
                                    @endif
                                </td>
                                <td class="text-center text-dark">
                                    {{$predictionResult->home_team_score_prediction}}
                                    @if ($predictionResult->game_winner_id_prediction==$predictionResult->home_team_id)
                                        *
                                    @endif
                                    &nbsp;:&nbsp;
                                    {{$predictionResult->away_team_score_prediction}}
                                    @if ($predictionResult->game_winner_id_prediction==$predictionResult->away_team_id)
                                        *
                                    @endif</td>
                                <td class="text-center text-dark"><a href="#" title="" data-container="body" data-toggle="popover" data-bs-html="true" data-bs-trigger="hover" data-placement="right" data-bs-content="
                           <div class='row'>
                                <div class='col col-8 col-md-8'>
                                     <div>Už nugalėtoją:</div>
                                    <div>Už skirtumą:</div>
                                    <div>Už tikslų skirtumą:</div>
                                    <div>Koeficientas:</div>
                                    <div>Už koeficientą:</div>
                                </div>
                                <div class='col col-4 col-md-4'>
                                    <div><strong>{{number_format($predictionResult->winner_points,2)}}</strong></div>
                                    <div><strong>{{number_format($predictionResult->difference_points,2)}}</strong></div>
                                    <div><strong>{{number_format($predictionResult->bingo_points,2)}}</strong></div>
                                    <div><strong>{{number_format($predictionResult->odds_points,2)}}</strong></div>
                                    <div><strong>{{number_format($predictionResult->odds,2)}}</strong></div>
                                 </div>
                           </div>" data-bs-original-title="Rungtynių taškai">{{$predictionResult->full_points}}</a></td>




                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                </div>

            </div>
         @endguest

@endsection
