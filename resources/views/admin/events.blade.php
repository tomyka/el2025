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
                <div class="table-header col-md-1 text-center">Nr.</div>
                <div class="table-header col-md-2 text-center">Event</div>
                <div class="table-header col-md-1 text-center">EventDay</div>
                <div class="table-header col-md-1 text-center">EventSurvival</div>
                <div class="table-header col-md-1 text-center">Active</div>
                <div class="table-header col-md-1 text-center">Rate</div>
            </div>

            @foreach($events as $event )
                <form role="form" method="post" action="{{route('admin.events')}}">
                    {{csrf_field()}}
                    <div class="row">
                        <input type="hidden" name="eventID" value = "{{$event->id}}">
                        <div class="col-md-1 table-cell text-center">{{$event->id}}</div>
                        <div class="col-md-2 table-cell justify-content-center"><input type="text" class="form-control form-control-sm" name="event" value="{{$event->event}}"></div>
                        <div class="col-md-1 table-cell d-flex justify-content-center"><input type="text" class="form-control form-control-sm input-size-2" name="eventDay" value="{{$event->event_day}}"></div>
                        <div class="col-md-1 table-cell text-center">
                            <div class="form-check form-switch d-flex align-items-center justify-content-center">
                                <input type="checkbox" class="form-check-input" name="eventSurvival" {{(($event->event_survival==1)?"checked":"")}}>
                            </div>
                        </div>
                        <div class="col-md-1 table-cell text-center">
                            <div class="form-check form-switch d-flex align-items-center justify-content-center">
                                <input type="checkbox" class="form-check-input" name="active" {{(($event->active==1)?"checked":"")}}>
                            </div>
                        </div>
                        <div class="col-md-1 table-cell d-flex justify-content-center text-center"><input type="textbox" class="form-control form-control-sm input-size-2" name="rate" value="{{$event->rate}}"></div>
                        <div class="col-md-2 text-center">
                            <input type="Submit" class="btn btn-sm btn-outline-primary" name="update" value="Update">
                            @if (session('admin')==9)
                                <input type="Submit" class="btn btn-sm btn-outline-dark" name="delete" value="Delete">
                            @endif
                        </div>
                    </div>
                </form>
            @endforeach


            <form role="form" method="post" action="{{route('admin.eventInsert')}}">
                {{csrf_field()}}
                <div class="row">
                    <div class="col-md-1 table-cell text-center"></div>
                    <div class="col-md-2 table-cell text-center"><input type="text" class="form-control form-control-sm" name="event" value=""></div>
                    <div class="col-md-1 table-cell d-flex justify-content-center"><input type="text" class="form-control form-control-sm input-size-2" name="eventDay" value=""></div>
                    <div class="col-md-1 table-cell">
                        <div class="form-check form-switch d-flex align-items-center justify-content-center">
                            <input type="checkbox" class="form-check-input" name="eventSurvival">
                        </div>

                    </div>
                    <div class="col-md-1 table-cell">
                        <div class="form-check form-switch d-flex align-items-center justify-content-center">
                            <input type="checkbox" class="form-check-input" name="active">
                        </div>
                    </div>
                    <div class="col-md-1 table-cell d-flex justify-content-center text-center"><input type="textbox" class="form-control form-control-sm input-size-2" name="rate" value=""></div>
                    <div class="col-md-2 col-xs-1 text-center"><input name="insert" class="btn btn-sm btn-outline-primary" type="Submit" value="Insert"></div>
                </div>
            </form>
        </div>
@endsection
