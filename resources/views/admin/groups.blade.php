@extends('admin.layouts.master')
@section('content')
    @if (Session::has('info'))
        <div class="row">
            <div class="col-md-12">
                <p class="alert alert-primary">{{Session::get('info')}}</p>
            </div>
        </div>
     @endif



        <div class="container-fluid">
            <div class="row">
                <div class="table-header col-md-1 text-center">Group</div>
                <div class="table-header col-md-1 text-center">Fee</div>
                <div class="table-header col-md-4 text-center">FeeDescription</div>
                <div class="table-header col-md-1 text-center">RewardRatio</div>
                <div class="table-header col-md-3 text-center">RewardDescription</div>

            </div>

            @foreach($groups as $group )
                <form role="form" method="post" action="{{route('admin.groups')}}">
                    {{csrf_field()}}
                    <div class="row">
                        <input type="hidden" name="groupID" value = "{{$group->id}}">
                        <div class="col-md-1 table-cell text-center"><input type="text" class="form-control form-control-sm" name="group" value="{{$group->group}}"></div>
                        <div class="col-md-1 table-cell text-center"><input type="text" class="form-control input-size-3" name="fee" value="{{$group->fee}}"></div>
                        <div class="col-md-4 table-cell text-center"><input type="text" class="form-control form-control-sm" name="feeDescription" value="{{$group->fee_description}}"></div>
                        <div class="col-md-1 table-cell text-center"><input type="text" class="form-control input-size-3" name="rewardRatio" value="{{$group->reward_ratio}}"></div>
                        <div class="col-md-3 table-cell text-center"><input type="text" class="form-control form-control-sm" name="rewardDescription" value="{{$group->reward_description}}"></div>
                        <div class="col-md-2 text-center">
                            <input type="Submit" class="btn btn-sm btn-outline-primary" name="update" value="Update">
                            @if (session('admin')==9)
                                <input type="Submit" class="btn btn-sm btn-outline-dark" name="delete" value="Delete">
                            @endif
                        </div>
                    </div>
                </form>
            @endforeach


            <form role="form" method="post" action="{{route('admin.groupInsert')}}">
                {{csrf_field()}}
                    <div class="row">
                        <div class="col-md-1 table-cell text-center"><input type="text" class="form-control form-control-sm" name="group" value=""></div>
                        <div class="col-md-1 table-cell text-center"><input type="text" class="form-control input-size-3" name="fee" value=""></div>
                        <div class="col-md-4 table-cell text-center"><input type="text" class="form-control form-control-sm" name="feeDescription" value=""></div>
                        <div class="col-md-1 table-cell text-center"><input type="text" class="form-control input-size-3" name="rewardRatio" value=""></div>
                        <div class="col-md-3 table-cell text-center"><input type="text" class="form-control form-control-sm" name="rewardDescription" value=""></div>
                        <div class="col-md-2 col-xs-1 text-center"><input name="insert" class="btn btn-sm btn-outline-primary" type="Submit" value="Insert"></div>
                    </div>



            </form>
        </div>
@endsection
