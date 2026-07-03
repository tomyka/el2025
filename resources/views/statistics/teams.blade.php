@extends('layouts.master')
@section('content')

    <table class="table table-nonfluid">
        <tr>
            <th class="th-small">{{ __('Komanda') }}</th>
            <th class="th-small">{{ __('Viso uždirbta taškų') }}</th>
            <th class="th-small">{{ __('Už atspėtą nugalėtoją') }}</th>
            <th class="th-small">{{ __('Neatspėta baigtis') }}</th>
            <th class="th-small">{{ __('Atspėta baigtis') }}</th>
            <th class="th-small">{{ __('Atspėta, kad laimės') }}</th>
            <th class="th-small">{{ __('Atspėta, kad pralaimės') }}</th>
        </tr>

        @foreach($teamStatistics as $teamStatistic)
            <tr>
                <td align="center" class="td-points" >{{$teamStatistic->teamLT}}</td>
                <td align="center" class="td-points" >{{$teamStatistic->fullPoints}}</td>
                <td align="center" class="td-points" >{{$teamStatistic->correctGuessPoints}}</td>
                <td align="center" class="td-points" >{{$teamStatistic->wrongGuess}}</td>
                <td align="center" class="td-points" >{{$teamStatistic->correctGuess}}</td>
                <td align="center" class="td-points" >{{$teamStatistic->correctGameWinner}}</td>
                <td align="center" class="td-points" >{{$teamStatistic->correctGameLoser}}</td>



            </tr>


        @endforeach



    </table>



@endsection