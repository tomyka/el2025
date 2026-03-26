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


    <form class="form" role="form"  id="loginmenu" method="post" action="{{ route('userPassword') }}">
        @csrf
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-2 col-6 table-header text-left">Dabartinis slaptažodis</div>
                <div class="col-md-2 col-6 table-cell text-center"><input id="currentPasswordInput" class="form form-control" type="password" name="currentPassword" required></div>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-2 col-6 table-header text-left">Naujas slaptažodis</div>
                <div class="col-md-2 col-6 table-cell text-center"><input id="passwordInput" class="form form-control" type="password" name="newPassword" required></div>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-2 col-6 table-header text-left">Pakartotinis slaptažodis</div>
                <div class="col-md-2 col-6 table-cell text-center"><input id="passwordInput" class="form form-control" type="password" name="newPasswordConfirmation" required></div>
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