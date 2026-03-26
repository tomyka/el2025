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
                <p class="alert alert-success">{{Session::get('info')}}</p>
            </div>
        </div>
    @endif


    <form class="form-horizontal" role="form" method="post" action="userProfile">
        @csrf
        <input type="hidden" class="form-control" size=1 name="userID" value="{{$user->id}}">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-2 col-6 table-header text-left">Vartotojo vardas</div>
                <div class="col-md-2 col-6 table-cell text-center"><input class="form-control form-control-sm" type="text" name="username" value="{{$user->username}}"></div>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-2 col-6 table-header text-left">Vardas</div>
                <div class="col-md-2 col-6 table-cell text-center"><input class="form-control form-control-sm" type="text" name="name" value="{{$user->name}}"></div>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-2 col-6 table-header text-left">Pavardė</div>
                <div class="col-md-2 col-6 table-cell text-center"><input class="form-control form-control-sm" type="text" name="surname" value="{{$user->surname}}"></div>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-2 col-6 table-header text-left">E-mail</div>
                <div class="col-md-2 col-6 table-cell text-center"><input class="form-control form-control-sm" type="text" name="email" value="{{$user->email}}"></div>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-4 col-12 text-center">&nbsp;</div>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-4 col-12 text-center">
                    <input class="btn btn-outline-primary" type="submit" name="update" value="Įvesti">
                </div>
            </div>
        </div>
    </form>

@endsection
