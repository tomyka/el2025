@extends('admin.layouts.master')
@section('content')

    @if (Session::has('info'))
        <div class="row">
            <div class="col-md-12">
                <p class="alert alert-info">{{Session::get('info')}}</p>
            </div>
        </div>
     @endif



        <div class="container-fluid">
            <div class="row">
                <div class="table-header col-md-1" align="center">Nr.</div>
                <div class="table-header col-md-2" align="center">DescriptionLT</div>
                <div class="table-header col-md-2" align="center">DescriptionEN</div>
                <div class="table-header col-md-1" align="center">StartEvent</div>
                <div class="table-header col-md-1" align="center">EndEvent</div>
                <div class="table-header col-md-1" align="center">RoundPoints</div>
                <div class="table-header col-md-1" align="center">Winner</div>
                <div class="table-header col-md-1" align="center">Active</div>
            </div>

            @foreach($lms as $lmsgame)
                <form role="form" method="post" action="{{route('admin.lms')}}">
                    {{csrf_field()}}
                    <div class="row">
                        <input type="hidden" name="lmsID" value = "{{$lmsgame->lmsID}}">
                        <div class="col-md-1 table-cell" align="center">{{$lmsgame->lmsID}}</div>
                        <div class="col-md-2 table-cell" align="center"><input type="text" class="form-control form-control-sm" size=30 name="descriptionLT" value="{{$lmsgame->descriptionLT}}"></div>
                        <div class="col-md-2 table-cell" align="center"><input type="text" class="form-control form-control-sm" size=30 name="descriptionEN" value="{{$lmsgame->descriptionEN}}"></div>
                        <div class="col-md-1 table-cell" align="center"><input type="text" class="form-control form-control-sm" size=30 name="startEventID" value="{{$lmsgame->startEventID}}"></div>
                        <div class="col-md-1 table-cell" align="center"><input type="text" class="form-control form-control-sm" size=30 name="endEventID" value="{{$lmsgame->endEventID}}"></div>
                        <div class="col-md-1 table-cell" align="center"><input type="text" class="form-control form-control-sm" size=30 name="roundPoints" value="{{$lmsgame->roundPoints}}"></div>
                        <div class="col-md-1 table-cell" align="center"><input type="text" class="form-control form-control-sm" size=30 name="winnerID" value="{{$lmsgame->winnerID}}"></div>
                        <div class="col-md-1 table-cell" align="center"><input type="checkbox" class="form-control-small" name="active" {{$lmsgame->active==1 ? "checked" : ""}}></div>

                        <div class="col-md-1" align="center"><input type="Submit" class="btn btn-sm" name="update" value="Update"></div>
                        <div class="col-md-1" align="center"><input type="Submit" class="btn btn-sm" name="delete" value="Delete"></div>
                    </div>
                </form>
            @endforeach


            <form role="form" method="post" action="{{route('admin.lmsinsert')}}">
                {{csrf_field()}}
                <div class="row">
                    <div class="col-md-1 table-cell" align="center"></div>
                    <div class="col-md-2 table-cell" align="center"><input type="text" class="form-control form-control-sm" size=30 name="descriptionLT" value=""></div>
                    <div class="col-md-2 table-cell" align="center"><input type="text" class="form-control form-control-sm" size=30 name="descriptionEN" value=""></div>
                    <div class="col-md-1 table-cell" align="center"><input type="text" class="form-control form-control-sm" size=30 name="descriptionEN" value=""></div>
                    <div class="col-md-1 table-cell" align="center"><input type="text" class="form-control form-control-sm" size=30 name="descriptionEN" value=""></div>
                    <div class="col-md-1 table-cell" align="center"><input type="text" class="form-control form-control-sm" size=30 name="descriptionEN" value=""></div>
                    <div class="col-md-1 table-cell" align="center"><input type="text" class="form-control form-control-sm" size=30 name="descriptionEN" value=""></div>
                    <div class="col-md-1 table-cell" align="center"><input type="checkbox" class="form-control-small" name="active"></div>
                    <div class="col-md-1 col-xs-1" align="center"><input name="insert" class="btn btn-sm" type="Submit" value="Insert"></div>
                </div>
            </form>
        </div>
@endsection