@extends('layouts.master')
@section('content')
    <div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-xs-6">
            <table class="table table-nonfluid">
                <tr>
                    <th class="th-small">{{ __('Skirtumas') }}</th>
                    <th class="th-small">{{ __('Faktas') }}</th>
                    <th class="th-small">{{ __('Spėjimas') }}</th>
                </tr>

                @foreach($predictionStatistics as $predictionStatistic)
                    <tr>
                        <td align="center" class="td-points" >{{$predictionStatistic->gameScore}}</td>
                        <td align="center" class="td-points" >{{$predictionStatistic->gameResult}}</td>
                        <td align="center" class="td-points" >{{$predictionStatistic->predictionResult}}</td>
                    </tr>


                @endforeach
            </table>
        </div>
        <div class="col-md-9 col-xs-6">
            {{ __('Paaiškinimas:') }}
            <BR>
            {{ __('Skirtumas - rungtynių baigties skirtumas') }}
            <BR>
            {{ __('Faktas - kiek rungtynių realiai baigėsi tokiu skirtumu') }}
            <BR>
            {{ __('Spėjimas - kiek bendrai buvo spėjimų, kad rungtynės baigsis tokiu skirtumu.') }}
        </div>


    </div>
    </div>





@endsection