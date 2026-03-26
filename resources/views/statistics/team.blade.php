@extends('layouts.master')
@section('content')

    <div class="col col-md-4">
        <img src="{{URL::to('img/Teams/'.$teamInfo->teamShort.'.png')}}" height=80> {{$teamInfo->team}}
    </div>
    <div class="col col-md-4">
        &nbsp;
    </div>
    <div class="row">
    <div class="col col-md-3">
        <table class="table table-md">
            <tr class="table-secondary">
                <td align="center">Žaista</td>
                <td align="center">Laimėta</td>
                <td align="center">Pralaimėta</td>
            </tr>

                <tr>
                    <td align="center">{{$homeTeamStatistics->gameCount+$awayTeamStatistics->gameCount}}</td>
                    <td align="center">{{$homeTeamStatistics->won+$awayTeamStatistics->won}}</td>
                    <td align="center">{{$homeTeamStatistics->lost+$awayTeamStatistics->lost}}</td>
                </tr>

        </table>
    </div>

    <div class="col col-md-2">
        <table class="table table-md">
            <tr class="table-secondary">
                <td align="center">Įmesta</td>
                <td align="center">Praleista</td>
            </tr>

            <tr>
                <td align="center">{{$homeTeamStatistics->pointsScored+$awayTeamStatistics->pointsScored}}</td>
                <td align="center">{{$homeTeamStatistics->pointsAllowed+$awayTeamStatistics->pointsAllowed}}</td>
            </tr>

        </table>
    </div>
    </div>

@endsection