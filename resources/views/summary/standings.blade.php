@extends('layouts.master_blank')
@section('content')

    <table class="table table-sm table-bordered table-hover table-nonfluid">
        <thead>
        <tr class="table-dark">
            <td rowspan="2" style="vertical-align: middle; text-align: center; border-right: double"><strong>Komanda</strong></td>
            @foreach($predictionStandings as $predictionStanding)
                @if($predictionStanding->team_id == 1)
                    <td class="text-center" style="border-right: double" colspan="3"><strong>{{ $predictionStanding->username }}</strong></td>
                @endif
            @endforeach
        </tr>
        <tr class="table-dark">
            @foreach($predictionStandings as $predictionStanding)
                @if($predictionStanding->team_id == 1)
                    <td class="text-center"><strong>V</strong></td>
                    <td class="text-center"><strong>1/4</strong></td>
                    <td class="text-center" style="border-right: double"><strong>&nbsp;F&nbsp;</strong></td>
                @endif
            @endforeach
        </tr>
        </thead>
        <tbody>
        @foreach($teams as $team)
            <tr class="table-default">
                <td class="align-middle" style="white-space: nowrap; border-right: double;" rowspan="2"><strong>{{ $team->team }}</strong></td>

                @foreach(array_filter($predictionStandings, fn($ps) => $ps->team_id == $team->id) as $predictionStanding)
                    <td class="text-center align-middle">{{ $predictionStanding->group_position }}</td>
                    <td class="text-center align-middle">
                        @if($predictionStanding->quarterfinal == 1)
                            {{ "x" }}
                        @endif
                    </td>
                    <td class="text-center align-middle" style="border-right: double">{{ $predictionStanding->final }}</td>
                @endforeach
            </tr>
            <tr class="table-default">
                @foreach(array_filter($predictionStandings, fn($ps) => $ps->team_id == $team->id) as $predictionStanding)
                    <td class="text-center align-middle">
                        <span
                            @if ($predictionStanding->group_position == $team->group_position) class="badge bg-success" @elseif(isset($predictionStanding->group_position)) class="badge bg-light" @endif>
                           {{ $predictionStanding->group_position_points }}
                        </span>
                    </td>
                    <td class="text-center align-middle">
                        <span
                            @if ($predictionStanding->quarterfinal == 1 && $team->quarterfinal == 1) class="badge bg-success"
                            @elseif ($predictionStanding->quarterfinal == 1 && isset($team->quarterfinal)) class="badge bg-danger"
                            @elseif ($predictionStanding->quarterfinal == 1 && !isset($team->quarterfinal)) class="badge bg-light" @endif>
                            @if($predictionStanding->quarterfinal == 1){{ $predictionStanding->quarterfinal_points }}@endif
                        </span>
                    </td>
                    <td class="text-center align-middle" style="border-right: double">{{ $predictionStanding->final_points }}</td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>

@endsection
