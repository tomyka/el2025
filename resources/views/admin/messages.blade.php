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
                <div class="table-header col-md-3 text-center">Message</div>
                <div class="table-header col-md-1 text-center">Active</div>
                <div class="table-header col-md-2 text-center">Profile</div>
            </div>

            @foreach($messages as $message )
                <form role="form" method="post" action="{{route('admin.messages')}}">
                    {{csrf_field()}}
                    <div class="row">
                        <input type="hidden" name="messageID" value = "{{$message->id}}">
                        <div class="col-md-1 table-cell text-center">{{$message->id}}</div>
                        <div class="col-md-3 table-cell text-center"><input type="text" class="form-control form-control-sm" name="message" value="{{$message->message}}"></div>
                        <div class="col-md-1 table-cell text-center">
                            <div class="form-check form-switch d-flex align-items-center justify-content-center">
                                <input type="checkbox" class="form-check-input" name="active" {{(($message->active==1)?"checked":"")}}>
                            </div>
                        </div>
                        <div class="col-md-2 table-cell text-center">
                            <select name="groupID" class="form-select">
                                <option value="">Select a Group</option>
                                @foreach($groups as $groupID => $groupName)
                                    <option value="{{ $groupID }}" {{ $groupID == $message->group_id ? 'selected' : '' }}>
                                        {{ $groupName }}
                                    </option>
                                @endforeach
                            </select>

                        </div>
                        <div class="col-md-2 text-center">
                            <input type="Submit" class="btn btn-sm btn-primary " name="update" value="Update">
                            <input type="Submit" class="btn btn-sm btn-dark" name="delete" value="Delete">
                        </div>
                    </div>
                </form>

            @endforeach
        <form role="form" method="post" action="{{route('admin.messageInsert')}}">
        {{csrf_field()}}
        <div class="row">
            <div class="col-md-1 table-cell text-center">&nbsp;</div>
            <div class="col-md-3 table-cell text-center"><input type="text" class="form-control form-control-sm" size=30 name="message" value=""></div>
            <div class="col-md-1 table-cell text-center">
                <div class="form-check form-switch d-flex align-items-center justify-content-center">
                    <input type="checkbox" class="form-check-input" name="active">
                </div>
            </div>
            <div class="col-md-2 table-cell text-center">
                <select name="groupID" class="form-select form-select-sm">
                    <option value="">Select a Group</option>
                    @foreach($groups as $groupID => $groupName)
                        <option value="{{ $groupID }}">{{ $groupName }}</option>
                    @endforeach
                </select>

            </div>
            <div class="col-md-1 col-xs-1 text-center"><input name="insert" class="btn btn-sm btn-outline-primary" type="Submit" value="Insert"></div>
        </div>
        </form>
        </div>

@endsection
