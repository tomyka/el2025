@extends('layouts.master')
@section('content')

    @if (count($errors->all()))
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-danger">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{$error}}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif
    @if (Session::has('info'))
        <div class="row">
            <div class="col-md-12">
                <p class="alert alert-primary">{{Session::get('info')}}</p>
            </div>
        </div>
    @endif

    <div class="ps-table-wrap">
        <form class="form-horizontal" role="form" method="post" action="">
            {{csrf_field()}}
            <div class="row" >
                <div class="ps-col-team table-header text-center">
                    <span class="d-none d-md-inline">Komanda</span>
                    <span class="d-md-none">Kom.</span>
                </div>
                <div class="ps-col-sm table-header text-center">
                    <span class="d-none d-md-inline">Grupė</span>
                    <span class="d-md-none">Gr.</span>
                </div>
                <div class="ps-col-sm table-header text-center">Vieta</div>
                <div class="ps-col-sm table-header text-center">
                    <span class="d-none d-md-inline">Šešioliktfinalis</span>
                    <span class="d-md-none">1/16</span>
                </div>
                <div class="ps-col-sm table-header text-center">
                    <span class="d-none d-md-inline">Aštuntfinalis</span>
                    <span class="d-md-none">1/8</span>
                </div>
                <div class="ps-col-sm table-header text-center">
                    <span class="d-none d-md-inline">Ketvirtfinalis</span>
                    <span class="d-md-none">1/4</span>
                </div>
                <div class="ps-col-sm table-header text-center">
                    <span class="d-none d-md-inline">Pusfinalis</span>
                    <span class="d-md-none">1/2</span>
                </div>
                <div class="ps-col-sm table-header text-center">
                    <span class="d-none d-md-inline">Finalas</span>
                    <span class="d-md-none">F</span>
                </div>
            </div>
            @php $prevGroup = null; @endphp
            @foreach ($predictionStandings as $predictionStanding)
            @php $groupChanged = $prevGroup !== $predictionStanding->group_name; $prevGroup = $predictionStanding->group_name; @endphp

            <div class="row prediction-row {{ $groupChanged && !$loop->first ? 'group-start' : '' }}">
                <input type="hidden" name="prediction_standingID{{$predictionStanding->id}}" id="prediction_standingID{{$predictionStanding->id}}" value="{{$predictionStanding->id}}">
                <div class="ps-col-team d-flex align-items-center">
                    <a href="{{$predictionStanding->link}}" target="_blank"><img src="{{URL::to('img/teams/'.str_replace(' ','%20',strtolower($predictionStanding->team)).'.svg')}}" width=22><span class="d-none d-md-inline" style="white-space:nowrap">{{$predictionStanding->team}}</span></a>
                </div>
                <div class="ps-col-sm d-flex align-items-center justify-content-center">
                    <span>{{ $predictionStanding->group_name }}</span>
                </div>
                <div class="ps-col-sm d-flex align-items-center justify-content-center">
                    <input class="form-control input-size-3" type="text" onchange="updateUserStandings({{$predictionStanding->id}})" name="groupPosition{{$predictionStanding->id}}" id="groupPosition{{$predictionStanding->id}}" value="{{ $predictionStanding->group_position }}" {{session('disabled')}}>
                </div>
                <div class="ps-col-sm d-flex align-items-center justify-content-center">
                    <input type="checkbox" class="form-check-input" onchange="updateUserStandings({{$predictionStanding->id}})" name="last32{{$predictionStanding->id}}" id="last32{{$predictionStanding->id}}" {{(($predictionStanding->last32==1)?"checked":"")}} {{session('disabled')}}>
                </div>
                <div class="ps-col-sm d-flex align-items-center justify-content-center">
                    <input type="checkbox" class="form-check-input" onchange="updateUserStandings({{$predictionStanding->id}})" name="last16{{$predictionStanding->id}}" id="last16{{$predictionStanding->id}}" {{(($predictionStanding->last16==1)?"checked":"")}} {{session('disabled')}}>
                </div>
                <div class="ps-col-sm d-flex align-items-center justify-content-center">
                    <input type="checkbox" class="form-check-input" onchange="updateUserStandings({{$predictionStanding->id}})" name="quarterfinal{{$predictionStanding->id}}" id="quarterfinal{{$predictionStanding->id}}" {{(($predictionStanding->quarterfinal==1)?"checked":"")}} {{session('disabled')}}>
                </div>
                <div class="ps-col-sm d-flex align-items-center justify-content-center">
                    <input type="checkbox" class="form-check-input" onchange="updateUserStandings({{$predictionStanding->id}})" name="semifinal{{$predictionStanding->id}}" id="semifinal{{$predictionStanding->id}}" {{(($predictionStanding->semifinal==1)?"checked":"")}} {{session('disabled')}}>
                </div>
                <div class="ps-col-sm d-flex align-items-center justify-content-center">
                    <input class="form-control input-size-3" type="text" onchange="updateUserStandings({{$predictionStanding->id}})" name="final{{$predictionStanding->id}}" id="final{{$predictionStanding->id}}" value="{{$predictionStanding->final}}" {{session('disabled')}}>
                </div>

            </div>
            @endforeach
            <BR>

        </form>
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7 col-12">
                Vieta - reikia surašyti vietas grupėje (1-4), kurias komandos, Jūsų nuomone, užims.
                <BR>Šešiolikfinalis - reikia varnelėmis pažymėti 32 komandas.
                <BR>Aštuntfinalis - reikia varnelėmis pažymėti 16 komandų.
                <BR>Ketvirtfinalis - reikia varnelėmis pažymėti 8 komandas.
                <BR>Pusfinalis - reikia varnelėmis pažymėti 4 komandas.
                <BR>Finalas - Finalo etape reikia pažymėti tik 1-2 vietas užimsiančias komandas.
                <BR>P.S. Lentelės struktūra gali nebūtinai atitikti Jūsų pateiktus rezultatų spėjimus.
            </div>
        </div>
    </div>
@endsection

<script>
    function updateUserStandings(prediction_standingID) {
        var groupPosition = document.getElementById('groupPosition'+prediction_standingID);
        var last32 = document.getElementById('last32'+prediction_standingID).checked;
        var last16 = document.getElementById('last16'+prediction_standingID).checked;
        var quarterfinal = document.getElementById('quarterfinal'+prediction_standingID).checked;
        var semifinal = document.getElementById('semifinal'+prediction_standingID).checked;
        var final = document.getElementById('final'+prediction_standingID);

            var formData = {
                prediction_standingID : prediction_standingID,
                groupPosition : $(groupPosition).val(),
                last32 : ((last32)?1:0),
                last16 : ((last16)?1:0),
                quarterfinal : ((quarterfinal)?1:0),
                semifinal : ((semifinal)?1:0),
                final : $(final).val()
            };

            console.log(formData);
            $.ajax({
                type: "POST",
                url: "{{route('prediction.standings')}}",
                data: formData,
                dataType: "json",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                encode: true,
            }).done(function (data) {
                console.log(data);
            });
    }
</script>
