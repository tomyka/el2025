@extends('admin.layouts.master')
@section('content')
    @include('partials.errors')
    <div class="form-group">
    <form action="{{route('admin.teaminsert')}}" method="post">
        {{csrf_field()}}
        <div class="row">
            <div class="col-md-12">
              &nbsp;
            </div>

        </div>
        <div class="row ">
                <div class="col-md-2">
                    <div class="input-group">
                    Įveskite komandų kiekį:
                    <input type="text" class="form-control-sm input-size-2" size=1 name="teamCount">
                </div>
            </div>
            <div class="col-md-1">
                <input name="Action" class="btn btn-md btn-outline-dark" type="Submit" value="Submit">
            </div>
        </div>
    </form>
    </div>
@endsection