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


    <form class="form-horizontal" role="form" method="post" action="userSettings">
        {{csrf_field()}}
        <input type="hidden" class="form-control" size=1 name="user_settingsID" value="{{$userSettings->id}}">
        <input type="hidden" class="form-control" size=1 name="userID" value="{{$userSettings->user_id}}">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-2 col-6 table-header text-left">Rodyti spėjimų</div>
                <div class="col-md-2 col-6 table-cell text-center"><input class="form-control form-control-sm input-size-2" type="text" size="2" name="resultAmount" value="{{$userSettings->result_amount}}"></div>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-4 col-12 text-center">&nbsp;</div>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-4 col-12 text-center"><input class="btn btn-sm btn-outline-primary" type="submit" name="update" value="Įvesti" ></div>
            </div>

        </div>
    </form>

@endsection
