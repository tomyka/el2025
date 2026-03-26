@extends('layouts.master')
@section('content')

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
                        <p class="alert alert-success">{{Session::get('info')}}</p>
                    </div>
                </div>
            @endif

            <div class="row">
                <div class="col-md-3 col-xs-6 table-header" align="center">Grupė</div>
                <div class="col-md-1 col-xs-6 table-header" align="center">Aktyvi</div>
                <div class="col-md-1 col-xs-6 table-header" align="center">Svečias</div>
            </div>

            @foreach($userGroups as $userGroup)
                <form class="form-horizontal" role="form" method="post" >
                    {{csrf_field()}}
                <div class="row">
                    <input type="hidden" class="form-control-small" size=1 name="user_groupID" value="{{$userGroup->id}}">
                    <div class="col-md-3 col-xs-6" align="center" id="tableCellBorderLess"><input type="text" class="form-control form-control-sm" size=15 name="group" value="{{$userGroup->group->group}}" readonly></div>
                    <div class="col-md-1 table-cell text-center">
                        <div class="form-check form-switch d-flex align-items-center justify-content-center">
                            <input type="checkbox" class="form-check-input" name="active" {{(($userGroup->active==1)?"checked":"")}}>
                        </div>
                    </div>
                    <div class="col-md-1 table-cell text-center">
                        <div class="form-check form-switch d-flex align-items-center justify-content-center">
                            <input type="checkbox" class="form-check-input" name="guest" {{(($userGroup->guest==1)?"checked":"")}}>
                        </div>
                    </div>
                    <div class="col-md-2 col-xs-6" align="center" id="tableCellBorderLess">
                        <input type="Submit" class="btn btn-sm btn-outline-primary" name="update" value="Atnaujinti" formaction="{{route('updateUserGroup')}}">
                        <input type="Submit" class="btn btn-sm btn-outline-primary" name="delete" value="Ištrinti" formaction="{{route('deleteUserGroup')}}">
                    </div>
                </div>
                </form>
            @endforeach

                @if($groups!="[]")
                <form role="form" method="post" action="{{route('insertUserGroup')}}">
                {{csrf_field()}}
                <div class="row">
                    <div class="col-md-3 table-cell" align="center" id="tableCellBorderLess">
                        <select name="groupID" class="form-select form-select-sm">
                            @foreach($groups as $id => $name)
                                <option value="{{ $id }}">
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1 col-2" align="center">
                        <fieldset>
                            <div class="form-check form-switch d-flex align-items-center justify-content-center">
                                <input type="checkbox" class="form-check-input" name="active" disabled>
                            </div>
                        </fieldset>
                    </div>
                    <div class="col-md-1 col-2" align="center">
                        <fieldset>
                            <div class="form-check form-switch d-flex align-items-center justify-content-center">
                                <input type="checkbox" class="form-check-input" name="guest">
                            </div>
                        </fieldset>
                    </div>

                    <div class="col-md-2 col-xs-1" align="center" id="tableCellBorderLess"><input name="insert" class="btn btn-sm btn-primary" type="Submit" value="Pridėti" formaction="{{route('insertUserGroup')}}"></div>
                </div>
                </form>
                @endif
        </div>

@endsection
