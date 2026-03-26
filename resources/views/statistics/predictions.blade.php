@extends('layouts.master')
@section('content')
    <div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-xs-6">
            <table class="table table-nonfluid">
                <tr>
                    <th class="th-small">Skirtumas</th>
                    <th class="th-small">Faktas</th>
                    <th class="th-small">Spėjimas</th>
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
            Paaiškinimas:
            <BR>
            Skirtumas - rungtynių baigties skirtumas
            <BR>
            Faktas - kiek rungtynių realiai baigėsi tokiu skirtumu
            <BR>
            Spėjimas - kiek bendrai buvo spėjimų, kad rungtynės baigsis tokiu skirtumu.
        </div>


    </div>
    </div>





@endsection