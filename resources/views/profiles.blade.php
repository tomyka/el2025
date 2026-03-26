@extends('layouts.master')
@section('content')
    <form class="form-horizontal" role="form" method="post" action="profiles_post">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-3 col-xs-6 table-header" align="center">Grupė</div>
                <div class="col-md-1 col-xs-6 table-header" align="center">Svečias</div>
            </div>

            @foreach($profiles as $profile)
            <div class="row">
                <input type="hidden" class="form-control-small" size=1 name="ProfileID" value="{{$profile->profileID}}" readonly>
                <div class="col-md-3 col-xs-6" align="center"><input type="text" class="form-control form-control-sm" size=15 name="Profile" value="{{$profile->profileLT}}" readonly></div>
                <div class="col-md-1 col-xs-6" align="center"><input type="checkbox" class="form-control form-control-sm" name="Guest" {{(($profile->guest==1)?"checked":"")}}></div>
                <div class="col-md-1 col-xs-6" align="center"><input type="Submit" class="btn btn-sm" name="Action" value="Update"></div>
                <div class="col-md-1 col-xs-6" align="center"><input type="Submit" class="btn btn-sm" name="Action" value="Delete"></div>
            </div>
            @endforeach
        </div>
    </form>
@endsection