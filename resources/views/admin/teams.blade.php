@extends('admin.layouts.master')
@section('content')
    @if (Session::has('info'))
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <p class="alert alert-primary">{{Session::get('info')}}</p>
            </div>
        </div>
    </div>
     @endif
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 col-6 text-center">&nbsp;</div>
        </div>
    <form method="post" action="{{route('admin.teams')}}">

            {{csrf_field()}}
            <div class="row">
                <div class="col-md-2 col-6 text-center">Komanda</div>
            <div class="col-md-1 d-none d-lg-block text-center">Grupė</div>
                <div class="col-md-4 d-none d-lg-block text-center">Nuoroda</div>
                <div class="col-md-1 col-2 text-center">Vieta</div>
                <div class="col-md-1 col-1 text-center">Aštuntfinalis</div>
                <div class="col-md-1 col-1 text-center">Ketvirtfinalis</div>
                <div class="col-md-1 col-1 text-center">Pusfinalis</div>
                <div class="col-md-1 col-2 text-center">Finalas</div>
            </div>

            @foreach($teams as $team )

            <div class="row">
                <input type="hidden" name="teamID[{{$team->id}}]" value = "{{$team->id}}">
                <div class="col-md-2 col-6 justify-content-center"><input type="text" class="form-control form-control-sm" name="team[{{$team->id}}]" value="{{$team->team}}"></div>
                <div class="col-md-1 col-md-1 d-flex justify-content-center"><input type="text" class="form-control form-control-sm input-size-1" size=1  name="groupName[{{$team->id}}]" value="{{$team->group_name}}"></div>
                <div class="col-md-4 col-md-1 d-none d-lg-block"><input type="text" class="form-control form-control-sm"  name="link[{{$team->id}}]" value="{{$team->link}}"></div>
                <div class="col-md-1 col-2 d-flex justify-content-center"><input type="text" class="form form-control input-size-2" name="groupPosition[{{$team->id}}]" value="{{$team->group_position}}"></div>
                <div class="col-md-1 table-cell text-center">
                    <div class="form-check form-switch d-flex align-items-center justify-content-center">
                        <input type="checkbox" class="form-check-input" name="last16[{{$team->id}}]" {{(($team->last16==1)?"checked":"")}}>
                    </div>
                </div>
                <div class="col-md-1 table-cell text-center">
                    <div class="form-check form-switch d-flex align-items-center justify-content-center">
                        <input type="checkbox" class="form-check-input" name="quarterfinal[{{$team->id}}]" {{(($team->quarterfinal==1)?"checked":"")}}>
                    </div>
                </div>
                <div class="col-md-1 table-cell text-center">
                    <div class="form-check form-switch d-flex align-items-center justify-content-center">
                        <input type="checkbox" class="form-check-input" name="semifinal[{{$team->id}}]" {{(($team->semifinal==1)?"checked":"")}}>
                    </div>
                </div>
                <div class="col-md-1 col-2 d-flex justify-content-center"><input type="text" class="form form-control input-size-1" name="final[{{$team->id}}]" value="{{$team->final}}"></div>
            </div>

            @endforeach
        <div class="row">
            <div class="col-md-12 col-xs-12 text-center">&nbsp;</div>
        </div>

        <div class="row">
            <div class="col-md-12 col-xs-12 text-center"><input name="Action" class="btn btn-md btn-outline-dark" type="Submit" value="Submit"></div>
        </div>
        </form>
    </div>

@endsection
