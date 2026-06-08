@extends('admin.layouts.master')
@section('content')

<div class="sb-card">
    <div class="sb-card-title">
        <i class="bi bi-calendar-event-fill sb-card-icon"></i> Žaidimai
        <span class="badge bg-secondary fw-normal ms-1">{{ $games->count() }}</span>
    </div>

    @if(Session::has('info'))
    <div class="alert alert-success py-2 mb-3">{{ Session::get('info') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 ag-table">
            <thead class="table-light">
                <tr>
                    <th class="ag-col-id text-muted">#</th>
                    <th class="ag-col-date">Data</th>
                    <th class="ag-col-time text-center">Val.</th>
                    <th class="ag-col-time text-center">Min.</th>
                    <th class="ag-col-team">Šeimininkai</th>
                    <th class="ag-col-team">Svečiai</th>
                    <th class="ag-col-team">Etapas</th>
                    <th class="ag-col-actions"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($games as $game)
                <tr>
                    <form method="post">
                    @csrf
                    <input type="hidden" name="gameID" value="{{ $game->id }}">
                    <td class="ag-id">{{ $game->id }}</td>
                    <td>
                        <input type="text" class="form-control form-control-sm"
                               name="gameDate" value="{{ substr($game->game_date, 0, 10) }}">
                    </td>
                    <td class="text-center">
                        <select name="gameHour" class="form-select form-select-sm">
                            @foreach(['18','19','20','21','22'] as $h)
                            <option value="{{ $h }}" {{ substr($game->game_date, 11, 2) == $h ? 'selected' : '' }}>{{ $h }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="text-center">
                        <select name="gameMinute" class="form-select form-select-sm">
                            @foreach(['00','05','15','30','45'] as $m)
                            <option value="{{ $m }}" {{ substr($game->game_date, 14, 2) == $m ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="homeTeamID" class="form-select form-select-sm">
                            <option value="">—</option>
                            @foreach($teams as $teamID => $teamName)
                            <option value="{{ $teamID }}" {{ $teamID == $game->home_team_id ? 'selected' : '' }}>{{ $teamName }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="awayTeamID" class="form-select form-select-sm">
                            <option value="">—</option>
                            @foreach($teams as $teamID => $teamName)
                            <option value="{{ $teamID }}" {{ $teamID == $game->away_team_id ? 'selected' : '' }}>{{ $teamName }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="eventID" class="form-select form-select-sm">
                            <option value="">—</option>
                            @foreach($events as $eventID => $eventName)
                            <option value="{{ $eventID }}" {{ $eventID == $game->event_id ? 'selected' : '' }}>{{ $eventName }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="text-end" style="white-space:nowrap;">
                        <button type="submit" name="update" value="1"
                                class="btn btn-sm btn-outline-secondary ag-action-btn"
                                formaction="{{ route('admin.updateGame') }}"
                                title="Išsaugoti">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        @if(session('admin') == 9)
                        <button type="submit" name="delete" value="1"
                                class="btn btn-sm btn-outline-secondary ag-action-btn ag-action-delete ms-1"
                                formaction="{{ route('admin.deleteGame') }}"
                                title="Ištrinti"
                                onclick="return confirm('Ištrinti žaidimą #{{ $game->id }}?')">
                            <i class="bi bi-trash3"></i>
                        </button>
                        @endif
                    </td>
                    </form>
                </tr>
                @endforeach

                {{-- Insert row --}}
                <tr class="ag-insert-row">
                    <form method="post" action="{{ route('admin.insertGame') }}">
                    @csrf
                    <td class="ag-id"><i class="bi bi-plus-lg text-muted"></i></td>
                    <td>
                        <input type="text" class="form-control form-control-sm"
                               name="gameDate" value="{{ substr($gameMaxDateTime, 0, 10) }}">
                    </td>
                    <td class="text-center">
                        <select name="gameHour" class="form-select form-select-sm">
                            @foreach(['18','19','20','21','22'] as $h)
                            <option value="{{ $h }}" {{ substr($gameMaxDateTime, 11, 2) == $h ? 'selected' : '' }}>{{ $h }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="text-center">
                        <select name="gameMinute" class="form-select form-select-sm">
                            @foreach(['00','05','15','30','45'] as $m)
                            <option value="{{ $m }}" {{ substr($gameMaxDateTime, 14, 2) == $m ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="homeTeamID" class="form-select form-select-sm">
                            <option value="">— Šeimininkai —</option>
                            @foreach($teams as $teamID => $teamName)
                            <option value="{{ $teamID }}">{{ $teamName }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="awayTeamID" class="form-select form-select-sm">
                            <option value="">— Svečiai —</option>
                            @foreach($teams as $teamID => $teamName)
                            <option value="{{ $teamID }}">{{ $teamName }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="eventID" class="form-select form-select-sm">
                            <option value="">— Etapas —</option>
                            @foreach($events as $eventID => $eventName)
                            <option value="{{ $eventID }}" {{ $eventID == $lastEnteredEventID ? 'selected' : '' }}>{{ $eventName }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="text-end">
                        <button type="submit" name="insert" value="1"
                                class="btn btn-sm btn-primary ag-action-btn"
                                title="Pridėti žaidimą">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </td>
                    </form>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@endsection
