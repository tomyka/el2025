@extends('admin.layouts.master')
@section('content')

<div class="sb-card">
    <div class="sb-card-title d-flex align-items-center justify-content-between">
        <span>
            <i class="bi bi-calendar-event-fill sb-card-icon"></i> {{ __('Žaidimai') }}
            <span class="badge bg-secondary fw-normal ms-1">{{ $games->count() }}</span>
        </span>
        @if(session('admin') >= 9)
        <button class="btn btn-sm btn-primary" onclick="agmOpenInsert()">
            <i class="bi bi-plus-lg"></i> {{ __('Naujas') }}
        </button>
        @endif
    </div>

    @if(Session::has('info'))
    <div class="alert alert-success py-2 mb-3">{{ Session::get('info') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 agm-table">
            <thead class="table-light">
                <tr>
                    <th class="agm-col-id text-muted">#</th>
                    <th class="agm-col-date">{{ __('Data / laikas') }}</th>
                    <th class="agm-col-home text-end">{{ __('Šeimininkai') }}</th>
                    <th class="agm-col-vs"></th>
                    <th class="agm-col-away">{{ __('Svečiai') }}</th>
                    <th class="agm-col-stage">{{ __('Etapas') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($games as $game)
                <tr @if(session('admin') >= 1) style="cursor:pointer"
                    ondblclick="agmOpenModal('{{ $game->id }}', '{{ \Carbon\Carbon::parse($game->game_date, 'UTC')->setTimezone('Europe/Vilnius')->format('Y-m-d\TH:i') }}', '{{ $game->home_team_id ?? '' }}', '{{ $game->away_team_id ?? '' }}', '{{ $game->event_id ?? '' }}')"
                    @endif>
                    <td class="agm-id">{{ $game->id }}</td>
                    <td class="agm-datetime">
                        {{ \Carbon\Carbon::parse($game->game_date, 'UTC')->setTimezone('Europe/Vilnius')->format('d M · H:i') }}
                    </td>
                    <td class="agm-team text-end">{{ $game->home_team->team ?? '—' }}</td>
                    <td class="agm-vs-sep text-center">vs</td>
                    <td class="agm-team">{{ $game->away_team->team ?? '—' }}</td>
                    <td>
                        <span class="agm-badge-stage">{{ $game->event->event ?? '—' }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Shared insert/edit modal --}}
    <div class="modal fade" id="agmEditModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
            <div class="modal-content" style="border-radius:16px;overflow:hidden;border:1px solid var(--sb-border);box-shadow:0 8px 32px rgba(0,0,0,.12)">
                <div class="modal-header border-0 px-4 pt-4 pb-0">
                    <h6 class="modal-title fw-bold" id="agmModalHeading" style="font-size:.9rem"></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" id="agmForm">
                    @csrf
                    <input type="hidden" name="gameID" id="agmGameID">
                    <div class="modal-body px-4 pt-3 pb-2">
                        <div class="mb-3">
                            <label class="form-label" style="font-size:.78rem;font-weight:600;color:var(--sb-muted)">{{ __('Data ir laikas') }}</label>
                            <div class="d-flex gap-2">
                                <input type="date" class="form-control form-control-sm" id="agmDate" style="flex:1">
                                <select class="form-select form-select-sm" id="agmTime" style="width:90px;flex-shrink:0">
                                    @for ($h = 0; $h < 24; $h++)
                                        @foreach (['00','30'] as $m)
                                        <option value="{{ sprintf('%02d',$h) }}:{{ $m }}">{{ sprintf('%02d',$h) }}:{{ $m }}</option>
                                        @endforeach
                                    @endfor
                                </select>
                            </div>
                            <input type="hidden" name="gameDateTime" id="agmDateTime">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size:.78rem;font-weight:600;color:var(--sb-muted)">{{ __('Šeimininkai') }}</label>
                            <select name="homeTeamID" class="form-select form-select-sm" id="agmHomeTeamID">
                                <option value="">—</option>
                                @foreach($teams as $teamID => $teamName)
                                <option value="{{ $teamID }}">{{ $teamName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size:.78rem;font-weight:600;color:var(--sb-muted)">{{ __('Svečiai') }}</label>
                            <select name="awayTeamID" class="form-select form-select-sm" id="agmAwayTeamID">
                                <option value="">—</option>
                                @foreach($teams as $teamID => $teamName)
                                <option value="{{ $teamID }}">{{ $teamName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size:.78rem;font-weight:600;color:var(--sb-muted)">{{ __('Etapas') }}</label>
                            <select name="eventID" class="form-select form-select-sm" id="agmEventID">
                                <option value="">—</option>
                                @foreach($events as $eventID => $eventName)
                                <option value="{{ $eventID }}">{{ $eventName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-2 justify-content-between">
                        @if(session('admin') >= 9)
                        <button type="button" id="agmDeleteBtn"
                                class="btn btn-outline-danger btn-sm"
                                onclick="agmConfirmDelete()">
                            <i class="bi bi-trash3"></i> {{ __('Ištrinti') }}
                        </button>
                        @else
                        <span></span>
                        @endif
                        <div>
                            <button type="button" class="btn btn-outline-secondary btn-sm me-2"
                                    data-bs-dismiss="modal">{{ __('Atšaukti') }}</button>
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('Išsaugoti') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Hidden delete form --}}
    <form id="agmDeleteForm" method="post" action="{{ route('admin.deleteGame') }}" style="display:none">
        @csrf
        <input type="hidden" name="gameID" id="agmDeleteGameID">
    </form>
</div>

<script>
var agmInsertAction = '{{ route('admin.insertGame') }}';
var agmUpdateAction = '{{ route('admin.updateGame') }}';
var agmDefaultDateTime = '{{ \Carbon\Carbon::parse($gameMaxDateTime, 'UTC')->setTimezone('Europe/Vilnius')->format('Y-m-d\TH:i') }}';
var agmDefaultEventID  = '{{ $lastEnteredEventID ?? '' }}';
var agmTeamsData    = {!! $teamsData->keyBy('id')->map(fn($t) => ['id'=>$t->id,'team'=>$t->team,'last32'=>(int)$t->last32,'last16'=>(int)$t->last16,'quarterfinal'=>(int)$t->quarterfinal,'semifinal'=>(int)$t->semifinal,'final'=>(int)$t->final])->values()->toJson() !!};
var agmEventsKO     = {!! $eventsKnockout->toJson() !!};
var agmUsedByEvent  = {!! $usedByEvent->toJson() !!};
var agmLabelNewGame  = @json(__('Naujas žaidimas'));
var agmLabelEditGame = @json(__('Redaguoti žaidimą'));
var agmLabelDelete   = @json(__('Ištrinti žaidimą'));

function agmSnapTime(t) {
    var p = t.split(':'), h = parseInt(p[0], 10), m = parseInt(p[1], 10);
    if (m < 15) m = 0; else if (m < 45) m = 30; else { m = 0; h = (h + 1) % 24; }
    return (h < 10 ? '0' + h : h) + ':' + (m === 0 ? '00' : '30');
}

function agmSetDateTime(dateTimeStr) {
    var parts = (dateTimeStr || '').split('T');
    document.getElementById('agmDate').value = parts[0] || '';
    document.getElementById('agmTime').value = agmSnapTime(parts[1] ? parts[1].substring(0, 5) : '00:00');
}

function agmRebuildTeams() {
    var gameID  = parseInt(document.getElementById('agmGameID').value) || 0;
    var eventID = String(document.getElementById('agmEventID').value);
    var homeVal = String(document.getElementById('agmHomeTeamID').value);
    var awayVal = String(document.getElementById('agmAwayTeamID').value);

    var isKO = agmEventsKO[eventID] == 1;

    // collect used team IDs in this event, excluding current game
    var usedIDs = [];
    var gamesInEvent = agmUsedByEvent[eventID] || {};
    Object.keys(gamesInEvent).forEach(function(gid) {
        if (parseInt(gid) !== gameID) {
            (gamesInEvent[gid] || []).forEach(function(tid) { usedIDs.push(String(tid)); });
        }
    });

    var eligible = agmTeamsData.filter(function(t) {
        if (isKO && !t.last32 && !t.last16 && !t.quarterfinal && !t.semifinal && !t.final) return false;
        return true;
    });

    agmFillSelect(document.getElementById('agmHomeTeamID'), eligible, usedIDs, awayVal, homeVal);
    agmFillSelect(document.getElementById('agmAwayTeamID'), eligible, usedIDs, homeVal, awayVal);
}

function agmFillSelect(sel, eligible, usedIDs, excludeID, keepVal) {
    sel.innerHTML = '<option value="">—</option>';
    eligible.forEach(function(t) {
        var tid = String(t.id);
        if (usedIDs.indexOf(tid) >= 0) return;
        if (tid === excludeID) return;
        var opt = document.createElement('option');
        opt.value = tid;
        opt.textContent = t.team;
        if (tid === keepVal) opt.selected = true;
        sel.appendChild(opt);
    });
}

document.getElementById('agmEventID').addEventListener('change', agmRebuildTeams);
document.getElementById('agmHomeTeamID').addEventListener('change', agmRebuildTeams);
document.getElementById('agmAwayTeamID').addEventListener('change', agmRebuildTeams);

document.getElementById('agmForm').addEventListener('submit', function() {
    document.getElementById('agmDateTime').value =
        document.getElementById('agmDate').value + 'T' + document.getElementById('agmTime').value;
});

function agmOpenInsert() {
    document.getElementById('agmModalHeading').textContent = agmLabelNewGame;
    document.getElementById('agmForm').action = agmInsertAction;
    document.getElementById('agmGameID').value = '';
    document.getElementById('agmDeleteGameID').value = '';
    agmSetDateTime(agmDefaultDateTime);
    document.getElementById('agmEventID').value = agmDefaultEventID;
    agmRebuildTeams();
    var deleteBtn = document.getElementById('agmDeleteBtn');
    if (deleteBtn) deleteBtn.style.display = 'none';
    new bootstrap.Modal(document.getElementById('agmEditModal')).show();
}

function agmOpenModal(id, dateTime, homeTeamID, awayTeamID, eventID) {
    document.getElementById('agmModalHeading').textContent = agmLabelEditGame + ' #' + id;
    document.getElementById('agmForm').action = agmUpdateAction;
    document.getElementById('agmGameID').value = id;
    document.getElementById('agmDeleteGameID').value = id;
    agmSetDateTime(dateTime);
    document.getElementById('agmEventID').value = eventID;
    // set team values before rebuild so agmRebuildTeams can preserve them
    document.getElementById('agmHomeTeamID').value = homeTeamID;
    document.getElementById('agmAwayTeamID').value = awayTeamID;
    agmRebuildTeams();
    var deleteBtn = document.getElementById('agmDeleteBtn');
    if (deleteBtn) deleteBtn.style.display = '';
    new bootstrap.Modal(document.getElementById('agmEditModal')).show();
}

function agmConfirmDelete() {
    var id = document.getElementById('agmDeleteGameID').value;
    if (confirm(agmLabelDelete + ' #' + id + '?')) {
        document.getElementById('agmDeleteForm').submit();
    }
}
</script>

@endsection
