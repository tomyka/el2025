@extends('layouts.master')
@section('content')
                <table class="table table-sm table-hover table-bordered">
                    <thead>
                        <tr class="table-dark">
                            <td class="text-center">Dalyvis</td>
                            @foreach($events as $event)
                                    <td class="table-dark text-center">{{$event->id}} turas</td>
                            @endforeach
                        </tr>
                    </thead>
                    @foreach($users as $user)
                        <tr class="table-light">
                            <td class="td-small text-left">{{$user->username}}</td>
                             @foreach($predictionSurvivals as $predictionSurvival)
                                 @if($user->id==$predictionSurvival->user_id)
                                     <td class="text-center">
                                         @if($predictionSurvival->team != "")
                                            <img src="{{URL::to('img/Teams/'.$predictionSurvival->team.'.png')}}" height=22>
                                            <div>{{$predictionSurvival->survival_points}}</div>
                                         @endif
                                     </td>
                                 @endif
                             @endforeach
                        </tr>
                    @endforeach



                </table>



@endsection
