@extends('admin.layouts.master')
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
                    <p class="alert alert-primary">{{Session::get('info')}}</p>
                </div>
            </div>
        @endif

        <div class="row">
            <div class="col-4 col-md-2 col-lg-1 table-header text-center">Username</div>
            <div class="col-md-2 col-lg-1 d-none d-md-block table-header text-center">Name</div>
            <div class="col-md-2 col-lg-1 d-none d-md-block table-header text-center">Surname</div>
            <div class="col-md-3 col-lg-2 d-none d-md-block table-header text-center">EmailAddress</div>
            <div class="col-2 col-md-1 col-lg-1 table-header text-center">Admin</div>
        </div>
        @foreach($users as $user)

            <form class="form-horizontal" role="form" method="post" action="{{route('admin.users')}}">
                {{csrf_field()}}
                <input type="hidden" name="userID" value={{$user->id}}>
                <input type="hidden" name="username" value={{$user->username}}>
                <div class="row">
                    <div class="col-4 col-md-2 col-lg-1 text-left">{{$user->username}}</div>
                    <div class="col-md-2 col-lg-1 d-none d-md-block text-left">{{$user->name}}</div>
                    <div class="col-md-2 col-lg-1 d-none d-md-block text-left">{{$user->surname}}</div>
                    <div class="col-md-2 col-lg-2 d-none d-md-block text-left">{{$user->email}}</div>
                    <div class="col-2 col-md-1 col-lg-1 d-flex justify-content-center"><input type="text" class="form form-control input-size-3" name="admin" value="{{$user->user_setting->admin}}"{{((session('admin')<8)?" readonly":"")}}></div>
                    <div class="col-2 col-md-2 col-lg-2 d-flex align-items-center justify-content-center">
                        <input type="Submit" class="btn btn-sm btn-outline-primary" name="update" value="Update" formaction="{{route('admin.updateUser')}}" {{((session('admin')<8)?" disabled":"")}} >
                        @if (session('admin')==9)
                            <input type="Submit" class="btn btn-sm btn-outline-dark" name="delete" value="Delete" formaction="{{route('admin.deleteUser')}}">
                        @endif
                    </div>
                </div>
            </form>
        @endforeach



    </div>



@endsection
