@extends('admin.layouts.master')
@section('content')

    @if (Session::has('info'))
        <div class="row">
            <div class="col-md-12">
                <p class="alert alert-success">{{Session::get('info')}}</p>
            </div>
        </div>
     @endif

        <div class="container-fluid">
            <div class="row">
                <div class="table-header col-md-1 text-center">Nr.</div>
                <div class="table-header col-md-3 text-center">Aprašymas</div>
                <div class="table-header col-md-1 text-center">Reikšmė</div>
            </div>

            @foreach($settings as $setting )
                <form role="form" method="post" action="{{route('admin.settings')}}">
                    {{csrf_field()}}
                    <div class="row">
                        <input type="hidden" name="settingID" value = "{{$setting->id}}">
                        <div class="col-md-1 table-cell text-center">{{$setting->id}}</div>
                        <div class="col-md-3 table-cell text-center"><input type="text" class="form-control form-control-sm" name="setting" value="{{$setting->setting}}"></div>
                        <div class="col-md-3 table-cell text-center"><input type="text" class="form-control form-control-sm" name="value" value="{{$setting->value}}"></div>
                        <div class="col-md-2 text-center">
                            <input type="Submit" class="btn btn-sm btn-primary " name="update" value="Update">
                            <input type="Submit" class="btn btn-sm btn-dark" name="delete" value="Delete">
                        </div>
                    </div>
                </form>

            @endforeach

        </div>

@endsection
