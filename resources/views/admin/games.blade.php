@extends('admin.layouts.master')
@section('content')

<div class="sb-card">
    <div class="sb-card-title d-flex align-items-center justify-content-between">
        <span>
            <i class="bi bi-calendar-event-fill sb-card-icon"></i> Žaidimai
            <span class="badge bg-secondary fw-normal ms-1">{{ $games->count() }}</span>
        </span>
        @if(session('admin') >= 9)
        <button class="btn btn-sm btn-primary" onclick="agmOpenInsert()">
            <i class="bi bi-plus-lg"></i> Naujas
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
                    <th class="agm-col-date">Data / laikas</th>
                    <th>Rungtynės</th>
                    <th class="agm-col-stage">Etapas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($games as $game)
                <tr @if(session('admin') >= 1) style="cursor:pointer"
                    ondblclick="agmOpenModal('{{ $game->id }}', '{{ substr(str_replace(' ', 'T', $game->game_date), 0, 16) }}', '{{ $game->home_team_id ?? '' }}', '{{ $game->away_team_id ?? '' }}', '{{ $game->event_id ?? '' }}')"
                    @endif>
                    <td class="agm-id">{{ $game->id }}</td>
                    <td class="agm-datetime">
                        {{ \Carbon\Carbon::parse($game->game_date)->format('d M · H:i') }}
                    </td>
                    <td class="agm-match">
                        {{ $game->home_team->team ?? '—' }}<span class="agm-vs">vs</span>{{ $game->away_team->team ?? '—' }}
                    </td>
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
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agmModalHeading"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" id="agmForm">
                    @csrf
                    <input type="hidden" name="gameID" id="agmGameID">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Data ir laikas</label>
                            <input type="datetime-local" class="form-control"
                                   name="gameDateTime" id="agmDateTime">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Šeimininkai</label>
                            <select name="homeTeamID" class="form-select" id="agmHomeTeamID">
                                <option value="">—</option>
                                @foreach($teams as $teamID => $teamName)
                                <option value="{{ $teamID }}">{{ $teamName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Svečiai</label>
                            <select name="awayTeamID" class="form-select" id="agmAwayTeamID">
                                <option value="">—</option>
                                @foreach($teams as $teamID => $teamName)
                                <option value="{{ $teamID }}">{{ $teamName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Etapas</label>
                            <select name="eventID" class="form-select" id="agmEventID">
                                <option value="">—</option>
                                @foreach($events as $eventID => $eventName)
                                <option value="{{ $eventID }}">{{ $eventName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        @if(session('admin') >= 9)
                        <button type="button" id="agmDeleteBtn"
                                class="btn btn-outline-danger btn-sm"
                                onclick="agmConfirmDelete()">
                            Ištrinti žaidimą
                        </button>
                        @else
                        <span></span>
                        @endif
                        <div>
                            <button type="button" class="btn btn-secondary btn-sm me-2"
                                    data-bs-dismiss="modal">Atšaukti</button>
                            <button type="submit" class="btn btn-primary btn-sm">Išsaugoti</button>
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
var agmDefaultDateTime = '{{ substr(str_replace(' ', 'T', $gameMaxDateTime), 0, 16) }}';

function agmOpenInsert() {
    document.getElementById('agmModalHeading').textContent = 'Naujas žaidimas';
    document.getElementById('agmForm').action = agmInsertAction;
    document.getElementById('agmGameID').value = '';
    document.getElementById('agmDeleteGameID').value = '';
    document.getElementById('agmDateTime').value = agmDefaultDateTime;
    document.getElementById('agmHomeTeamID').value = '';
    document.getElementById('agmAwayTeamID').value = '';
    document.getElementById('agmEventID').value = '';
    var deleteBtn = document.getElementById('agmDeleteBtn');
    if (deleteBtn) deleteBtn.style.display = 'none';
    new bootstrap.Modal(document.getElementById('agmEditModal')).show();
}

function agmOpenModal(id, dateTime, homeTeamID, awayTeamID, eventID) {
    document.getElementById('agmModalHeading').textContent = 'Redaguoti žaidimą #' + id;
    document.getElementById('agmForm').action = agmUpdateAction;
    document.getElementById('agmGameID').value = id;
    document.getElementById('agmDeleteGameID').value = id;
    document.getElementById('agmDateTime').value = dateTime;
    document.getElementById('agmHomeTeamID').value = homeTeamID;
    document.getElementById('agmAwayTeamID').value = awayTeamID;
    document.getElementById('agmEventID').value = eventID;
    var deleteBtn = document.getElementById('agmDeleteBtn');
    if (deleteBtn) deleteBtn.style.display = '';
    new bootstrap.Modal(document.getElementById('agmEditModal')).show();
}

function agmConfirmDelete() {
    var id = document.getElementById('agmDeleteGameID').value;
    if (confirm('Ištrinti žaidimą #' + id + '?')) {
        document.getElementById('agmDeleteForm').submit();
    }
}
</script>

@endsection
