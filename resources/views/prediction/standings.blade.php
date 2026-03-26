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

    <div class="container-fluid">
        <form class="form-horizontal" role="form" method="post" action="">
            {{csrf_field()}}
            <div class="row justify-content-center" >
                <div class="col-lg-2 col-md-2 col-4 table-header text-center">Komanda</div>
<!--                <div class="col-lg-1 col-md-1 col-2 table-header text-center">Grupė</div> -->
                <div class="col-lg-1 col-md-1 col-2 table-header text-center">Vieta</div>
<!--            <div class="col-lg-1 col-md-2 col-3 table-header text-center">Aštuntfinalis</div> -->
                <div class="col-lg-1 col-md-2 col-3 table-header text-center">Ketvirtfinalis</div>
                <div class="col-lg-1 col-md-2 col-3 table-header text-center">Pusfinalis</div>
                <div class="col-lg-1 col-md-1 col-3 table-header text-center">Finalas</div>

            </div>
            @foreach ($predictionStandings as $predictionStanding)

            <div class="row justify-content-center">
                <input type="hidden" name="prediction_standingID{{$predictionStanding->id}}" id="prediction_standingID{{$predictionStanding->id}}" value="{{$predictionStanding->id}}">
                <div class="col-lg-2 col-md-2 col-4 d-flex justify-content-center justify-content-lg-start" id="tableCellBorderLess">
                    <a href="{{$predictionStanding->link}}" target="_blank"><img src="{{URL::to('img/Teams/'.$predictionStanding->team.'.png')}}" width=40><span class="d-none d-lg-inline">{{$predictionStanding->team}}</span></a>
                </div>
<!--
                <div class="col-lg-1 col-md-1 col-2 d-flex justify-content-center" id="tableCellBorderLess">
                    <span>{{ $predictionStanding->group_name }}</span>
                </div>
-->
                <div class="col-lg-1 col-md-1 col-2 d-flex justify-content-center" id="tableCellBorderLess">
                    <input class="form-control input-size-3" type="text" onchange="updateUserStandings({{$predictionStanding->id}})" name="groupPosition{{$predictionStanding->id}}" id="groupPosition{{$predictionStanding->id}}" value="{{ $predictionStanding->group_position }}" {{session('disabled')}}>
                </div>
<!--
                <div class="col-lg-1 col-md-2 col-3 d-flex align-items-center justify-content-center align-middle">
                    <div class="col-1 col-md-1 col-lg-1 d-flex align-items-center justify-content-center">
                        <span class="align-middle"><input type="checkbox" class="form-check-input" onchange="updateUserStandings({{$predictionStanding->id}})" name="last16{{$predictionStanding->id}}" id="last16{{$predictionStanding->id}}" {{(($predictionStanding->last16==1)?"checked":"")}} {{session('disabled')}}></span>
                        <span class="align-content-center"></span>
                    </div>
                </div>
-->
                <div class="col-lg-1 col-md-2 col-3 d-flex align-items-center justify-content-center align-middle">
                    <div class="col-1 col-md-1 col-lg-1 d-flex align-items-center justify-content-center">
                        <span class="align-middle"><input type="checkbox" class="form-check-input" onchange="updateUserStandings({{$predictionStanding->id}})" name="quarterfinal{{$predictionStanding->id}}" id="quarterfinal{{$predictionStanding->id}}" {{(($predictionStanding->quarterfinal==1)?"checked":"")}} {{session('disabled')}}></span>
                        <span class="align-content-center"></span>
                    </div>
                </div>

                <div class="col-lg-1 col-md-2 col-3 d-flex align-items-center justify-content-center align-middle">
                    <div class="col-1 col-md-1 col-lg-1 d-flex align-items-center justify-content-center">
                        <span class="align-middle"><input type="checkbox" class="form-check-input" onchange="updateUserStandings({{$predictionStanding->id}})" name="semifinal{{$predictionStanding->id}}" id="semifinal{{$predictionStanding->id}}" {{(($predictionStanding->semifinal==1)?"checked":"")}} {{session('disabled')}}></span>
                        <span class="align-content-center"></span>
                    </div>
                </div>

                <div class="col-lg-1 col-md-1 col-3 d-flex justify-content-center" id="tableCellBorderLess">
                    <input class="form-control input-size-3" type="text" onchange="updateUserStandings({{$predictionStanding->id}})" name="final{{$predictionStanding->id}}" id="final{{$predictionStanding->id}}" value="{{$predictionStanding->final}}" {{session('disabled')}}>
                </div>

            </div>
            @endforeach
            <BR>

        </form>
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7 col-12">
                Vieta - reikia surašyti vietas (1-20), kurias komandos, Jūsų nuomone, užims reguliame sezone.
<!--            <BR>Aštuntfinalis - reikia varnelėmis pažymėti 16 komandų. -->
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
        //var last16 = document.getElementById('last16'+prediction_standingID).checked;
        var quarterfinal = document.getElementById('quarterfinal'+prediction_standingID).checked;
        var semifinal = document.getElementById('semifinal'+prediction_standingID).checked;
        var final = document.getElementById('final'+prediction_standingID);


            var formData = {
                prediction_standingID : prediction_standingID,
                groupPosition : $(groupPosition).val(),
//                last16 : ((last16)?1:0),
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
