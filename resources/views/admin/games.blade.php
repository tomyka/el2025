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
            <div class="col-md-12">&nbsp;</div>
        </div>
        <form role="form" method="post" action="{{route('admin.insertGame')}}">
            {{csrf_field()}}
            <div class="row">
                <div class="col-md-1 d-none d-lg-block text-center"><input type="text" class="form-control form-control-sm" size=16 name="gameDate" value="{{substr($gameMaxDateTime,0,10)}}"></div>
                <div class="col-md-1 d-none d-lg-block text-center">
                    <select name="gameHour" class="form-select form-select-sm">
                        <option value="18" {{ substr($gameMaxDateTime, 11, 2) == '18' ? 'selected' : '' }}>18</option>
                        <option value="19" {{ substr($gameMaxDateTime, 11, 2) == '19' ? 'selected' : '' }}>19</option>
                        <option value="20" {{ substr($gameMaxDateTime, 11, 2) == '20' ? 'selected' : '' }}>20</option>
                        <option value="21" {{ substr($gameMaxDateTime, 11, 2) == '21' ? 'selected' : '' }}>21</option>
                        <option value="22" {{ substr($gameMaxDateTime, 11, 2) == '22' ? 'selected' : '' }}>22</option>
                    </select>
                </div>
                <div class="col-md-1 d-none d-lg-block text-center">
                    <select name="gameMinute" class="form-select form-select-sm">
                        <option value="00" {{ substr($gameMaxDateTime, 14, 2) == '00' ? 'selected' : '' }}>00</option>
                        <option value="05" {{ substr($gameMaxDateTime, 14, 2) == '05' ? 'selected' : '' }}>05</option>
                        <option value="15" {{ substr($gameMaxDateTime, 14, 2) == '15' ? 'selected' : '' }}>15</option>
                        <option value="30" {{ substr($gameMaxDateTime, 14, 2) == '30' ? 'selected' : '' }}>30</option>
                        <option value="45" {{ substr($gameMaxDateTime, 14, 2) == '45' ? 'selected' : '' }}>45</option>
                    </select>
                </div>
                <div class="col-md-2 d-none d-lg-block text-right">
                    <select name="homeTeamID" class="form-select form-select-sm">
                        <option value="">Select Home Team</option>
                        @foreach($teams as $teamID => $teamName)
                            <option value="{{ $teamID }}">{{ $teamName }}</option>
                        @endforeach
                    </select>
                </div>
                  <div class="col-md-2 d-none d-lg-block text-left">
                      <select name="awayTeamID" class="form-select form-select-sm">
                          <option value="">Select Away Team</option>
                          @foreach($teams as $teamID => $teamName)
                              <option value="{{ $teamID }}">{{ $teamName }}</option>
                          @endforeach
                      </select>
                  </div>
                <div class="col-md-2 d-none d-lg-block text-center">
                    <select name="eventID" class="form-select form-select-sm">
                        <option value="">Select an Event</option>
                        @foreach($events as $eventID => $eventName)
                            <option value="{{ $eventID }}" {{ $eventID == $lastEnteredEventID ? 'selected' : '' }}>
                                {{ $eventName }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-none d-lg-block text-center"><input name="insert" class="btn btn-sm btn-outline-primary" type="Submit" value="Insert"></div>
            </div>
        </form>


                <div class="row">
                    <div class="col-md-1 d-none d-lg-block text-center">Nr.</div>
                    <div class="col-lg-2 col-md-3 d-none d-md-block text-center">GameDate</div>
                    <div class="col-md-2 col-4 text-center">HomeTeam</div>
                    <div class="col-md-2 col-4 text-center">AwayTeam</div>
<!--                    <div class="col-md-1 col-4 text-center">Winner</div>-->
                    <div class="col-lg-2 col-md-3 d-none d-md-block text-center">Event</div>
                </div>

        @foreach($games as $game)
            <form role="form" method="post">
                {{csrf_field()}}
                <div class="row">
                    <input type="hidden" name="gameID" value = "{{$game->id}}">
                    <div class="col-md-1 d-none d-lg-block text-center">{{$game->id}}</div>
                    <div class="col-lg-2 col-md-3 d-none d-md-block text-center">
                        <input type="text" class="form-control form-control-sm" size=14 name="gameDate" id="gameDate{{$game->id}}" value="{{$game->game_date}}">
                    </div>
                    <div class="col-md-2 col-4 text-right">
                        <select name="homeTeamID" class="form-select form-select-sm">
                            <option value="">Select Home Team</option>
                            @foreach($teams as $teamID => $teamName)
                                <option value="{{ $teamID }}" {{ $teamID == $game->home_team_id ? 'selected' : '' }}>
                                    {{ $teamName }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-4 text-center">
                        <select name="awayTeamID" class="form-select form-select-sm">
                            <option value="">Select Away Team</option>
                            @foreach($teams as $teamID => $teamName)
                                <option value="{{ $teamID }}" {{ $teamID == $game->away_team_id ? 'selected' : '' }}>
                                    {{ $teamName }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-3 d-none d-md-block text-center">
                        <select name="eventID" class="form-select form-select-sm">
                            <option value="">Select an Event</option>
                            @foreach($events as $eventID => $eventName)
                                <option value="{{ $eventID }}" {{ $eventID == $game->event_id ? 'selected' : '' }}>
                                    {{ $eventName }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-2 col-md-2 col-lg-2 d-flex align-items-center justify-content-center">
                        <input type="Submit" class="btn btn-sm btn-outline-primary" name="update" value="Update" formaction="{{route('admin.updateGame')}}">
                            @if (session('admin')==9)
                                <input type="Submit" class="btn btn-sm btn-outline-danger" name="delete" value="Delete" formaction="{{route('admin.deleteGame')}}">
                            @endif
                    </div>
                </div>
            </form>
        @endforeach

    </div>

@endsection

