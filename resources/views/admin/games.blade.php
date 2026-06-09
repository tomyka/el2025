@extends('admin.layouts.master')
@section('content')

<div class="sb-card" x-data="gameModal()">
    <div class="sb-card-title">
        <i class="bi bi-calendar-event-fill sb-card-icon"></i> Žaidimai
        <span class="badge bg-secondary fw-normal ms-1">{{ $games->count() }}</span>
    </div>

    @if(Session::has('info'))
    <div class="alert alert-success py-2 mb-3">{{ Session::get('info') }}</div>
    @endif

    {{-- Hidden delete form — inside x-data scope so :value="gameID" works --}}
    <form id="agmDeleteForm" method="post" action="{{ route('admin.deleteGame') }}" style="display:none">
        @csrf
        <input type="hidden" name="gameID" :value="gameID">
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 agm-table">
            <thead class="table-light">
                <tr>
                    <th class="agm-col-id text-muted">#</th>
                    <th class="agm-col-date">Data / laikas</th>
                    <th>Rungtynės</th>
                    <th class="agm-col-stage">Etapas</th>
                    @if(session('admin') >= 9)
                    <th class="agm-col-actions"></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($games as $game)
                <tr>
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
                    @if(session('admin') >= 9)
                    <td class="text-end">
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary agm-action-btn"
                                title="Redaguoti"
                                @click="openModal('{{ $game->id }}', '{{ substr(str_replace(' ', 'T', $game->game_date), 0, 16) }}', '{{ $game->home_team_id ?? '' }}', '{{ $game->away_team_id ?? '' }}', '{{ $game->event_id ?? '' }}')">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </td>
                    @endif
                </tr>
                @endforeach

                {{-- Insert row — superadmin only --}}
                @if(session('admin') >= 9)
                <tr class="agm-insert-row">
                    <form method="post" action="{{ route('admin.insertGame') }}">
                    @csrf
                    <td class="agm-id"><i class="bi bi-plus-lg text-muted"></i></td>
                    <td>
                        <input type="datetime-local" class="form-control form-control-sm"
                               name="gameDateTime"
                               value="{{ substr(str_replace(' ', 'T', $gameMaxDateTime), 0, 16) }}">
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            <select name="homeTeamID" class="form-select form-select-sm">
                                <option value="">— Šeimininkai —</option>
                                @foreach($teams as $teamID => $teamName)
                                <option value="{{ $teamID }}">{{ $teamName }}</option>
                                @endforeach
                            </select>
                            <select name="awayTeamID" class="form-select form-select-sm">
                                <option value="">— Svečiai —</option>
                                @foreach($teams as $teamID => $teamName)
                                <option value="{{ $teamID }}">{{ $teamName }}</option>
                                @endforeach
                            </select>
                        </div>
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
                        <button type="submit"
                                class="btn btn-sm btn-primary agm-action-btn"
                                title="Pridėti žaidimą">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </td>
                    </form>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- Edit modal --}}
    <div class="modal fade" id="agmEditModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Redaguoti žaidimą
                        <span class="text-muted fw-normal fs-6" x-text="'#' + gameID"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="{{ route('admin.updateGame') }}">
                    @csrf
                    <input type="hidden" name="gameID" :value="gameID">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Data ir laikas</label>
                            <input type="datetime-local" class="form-control"
                                   name="gameDateTime" x-model="gameDateTime">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Šeimininkai</label>
                            <select name="homeTeamID" class="form-select" x-model="homeTeamID">
                                <option value="">—</option>
                                @foreach($teams as $teamID => $teamName)
                                <option value="{{ $teamID }}">{{ $teamName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Svečiai</label>
                            <select name="awayTeamID" class="form-select" x-model="awayTeamID">
                                <option value="">—</option>
                                @foreach($teams as $teamID => $teamName)
                                <option value="{{ $teamID }}">{{ $teamName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Etapas</label>
                            <select name="eventID" class="form-select" x-model="eventID">
                                <option value="">—</option>
                                @foreach($events as $eventID => $eventName)
                                <option value="{{ $eventID }}">{{ $eventName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button"
                                class="btn btn-outline-danger btn-sm"
                                @click="confirmDelete()">
                            Ištrinti žaidimą
                        </button>
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
</div>

<script>
function gameModal() {
    return {
        gameID: '',
        gameDateTime: '',
        homeTeamID: '',
        awayTeamID: '',
        eventID: '',
        openModal(id, dateTime, homeTeamID, awayTeamID, eventID) {
            this.gameID     = String(id);
            this.gameDateTime = dateTime;
            this.homeTeamID = String(homeTeamID);
            this.awayTeamID = String(awayTeamID);
            this.eventID    = String(eventID);
            new bootstrap.Modal(document.getElementById('agmEditModal')).show();
        },
        confirmDelete() {
            if (confirm('Ištrinti žaidimą #' + this.gameID + '?')) {
                document.getElementById('agmDeleteForm').submit();
            }
        }
    }
}
</script>

@endsection
