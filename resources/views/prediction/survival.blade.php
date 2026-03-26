@extends('layouts.master')
@section('content')

@if (session('eventSurvival')==1)
    <div class="container-fluid">
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
                    <p class="alert alert-warning">{{Session::get('info')}}</p>
                </div>
            </div>
        @endif

        <div class="row" >
            <div class="col col-md-4 col-12" style="margin-right: auto; margin-left: auto;">
                <table class="table table-sm">
                    <thead>
                        <tr class="table-dark">
                            <td class="text-center">Komanda</td>
                            <td class="text-center">{{session('eventID')}} turas</td>
                        </tr>
                    </thead>
                    <tbody>
                        <form class="form-horizontal" role="form" method="post" action="">
                        {{csrf_field()}}
                        @foreach($predictionSurvivals as $predictionSurvival)
                                <tr class="table-bordered">
                                    <td id="tableCellBorderless">{{$predictionSurvival->team}}({{$predictionSurvival->location}}) <I style="font-size: x-small"> vs. {{$predictionSurvival->opponent}}</I></td>
                                    <td class="text-center" id="tableCellBorderlessRadio">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="predictionSurvival{{$predictionSurvival->team_id}}" name="teamID" onclick="updateUserSurvivalSelection({{$predictionSurvival->team_id}})"   class="custom-control-input" value="{{$predictionSurvival->team_id}}" {{($predictionSurvival->active!=1||$survivalPoints>=0)&&$predictionSurvival->event_id!=session('eventID')?" disabled":""}} {{$predictionSurvival->event_id==session('eventID')?" checked":""}}>
                                            <label class="custom-control-label" for="predictionSurvival{{$predictionSurvival->team_id}}"></label>
                                        </div>
                                    </td>
                                </tr>
                        @endforeach
                        </form>

                    </tbody>
                </table>
            </div>
            <div class="col-md-8 col-12">
                <p><strong>Taisyklės:</strong></p>
                <p>Kiekvieną turą pasirenkate komandą, kuri, jūsų nuomone laimės turo rungtynes. Jei jūsų pasirinkta komanda pralaimi - žaidimą pradedate iš naujo. Jei jūsų pasirinkta komanda laimi - žaidžiate toliau.</p>
                <p>Tęsiant Išlikimo žaidimą tos pačios komandos rinktis daugiau nebegalima, todėl komandą kiekvienam turui rinkitės atidžiai.</p>
                <p>Komandos pasirinkimą galima keisti iki tos komandos rungtynių pradžios. Rungtynėms prasidėjus nebegalima rinktis jose dalyvaujančių komandų, kaip ir negalima pakeisti tose rungtynėse dalyvaujančios pasirinktos komandos.</p>
                <p>Taškai skaičiuojami taip:<strong style="color: #DF4400">10*(iš eilės atspėtų turų) taškų.</strong>. Pvz., jei atspėjate nugalėtoją 4 turus iš eilės, gausite 10+20+30+40 taškų už kiekvieną iš eilės atspėtą turą. Kuo ilgiau spėjate teisingai - tuo daugiau taškų gaunate.</p>
                <p>Išlikimo žaidimai tęsis iki reguliariojo sezono pabaigos, t.y. suklydus spėjant nugalėtoją, nuo kito turo automatiškai pradėsite žaidimą iš naujo.</p>
            </div>
        </div>
    </div>
@else
    <div class="row">
        <div class="col-md-12">
            <p>Išlikimo žaidimas baigėsi.</p>
        </div>
    </div>
@endif
@endsection

<script>
    function updateUserSurvivalSelection(teamID) {
        if (teamID != "" ){
            var formData = {
                teamID : teamID
            };
            console.log(formData);
            $.ajax({
                type: "POST",
                url: "{{route('prediction.survival')}}",
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
    }
</script>
