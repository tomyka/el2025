@extends('admin.layouts.master')
@section('content')

<div class="sb-card">
    <div class="sb-card-title">
        <i class="bi bi-flag-fill sb-card-icon"></i> {{ __('Komandos') }}
        <span class="badge bg-secondary fw-normal ms-1">{{ $teams->count() }}</span>
    </div>

    @if(Session::has('info'))
    <div class="alert alert-success py-2 mb-3">{{ Session::get('info') }}</div>
    @endif

    @php
        $grouped = $teams->sortBy([['group_name', 'asc'], ['id', 'asc']])->groupBy('group_name');
    @endphp

    <form method="post" action="{{ route('admin.teams') }}" id="at-form">
    @csrf
    <div class="row g-3">
        @foreach($grouped as $groupName => $groupTeams)
        <div class="col-md-6">
            <div class="at-group-card">
                <div class="at-group-header">{{ __('Grupė') }} {{ $groupName ?: '—' }}</div>

                <div class="at-group-row at-header-row">
                    <span></span>
                    <span>{{ __('Komandos') }}</span>
                    <span class="text-center">#</span>
                    <span class="text-center" title="{{ __('Šešioliktfinalis') }}">Š16</span>
                    <span class="text-center" title="{{ __('Aštuntfinalis') }}">Š8</span>
                    <span class="text-center" title="{{ __('Ketvirtfinalis') }}">KF</span>
                    <span class="text-center" title="{{ __('Pusfinalis') }}">PF</span>
                    <span class="text-center" title="{{ __('Finalas') }}">F</span>
                </div>

                @foreach($groupTeams as $team)
                <div class="at-group-row at-team-row"
                     @if(session('admin') >= 1)
                     style="cursor:pointer"
                     ondblclick="atOpenModal({{ $team->id }}, {{ json_encode($team->team) }}, {{ json_encode($team->link ?? '') }}, {{ json_encode($team->group_name ?? '') }})"
                     @endif>
                    <input type="hidden" name="teamID[{{ $team->id }}]" value="{{ $team->id }}">
                    <div>
                        @if($team->link)
                        <img src="{{ $team->link }}" alt="" class="at-flag" onerror="this.style.display='none'">
                        @else
                        <div class="at-flag-empty"><i class="bi bi-image" style="font-size:.6rem"></i></div>
                        @endif
                    </div>
                    <div class="at-name">{{ $team->team }}</div>
                    <div class="d-flex justify-content-center">
                        <input type="number" class="at-tiny-input at-pos-input"
                               name="groupPosition[{{ $team->id }}]" value="{{ $team->group_position }}"
                               min="1" max="6" data-group="{{ $team->group_name }}"
                               onfocus="this.select()" onblur="atAutoSave()"
                               onclick="event.stopPropagation()">
                    </div>
                    <div class="d-flex justify-content-center">
                        <input type="checkbox" class="form-check-input at-chk"
                               name="last32[{{ $team->id }}]" {{ $team->last32 ? 'checked' : '' }}
                               data-round="last32" data-team-id="{{ $team->id }}"
                               onchange="atAutoSave()" onclick="event.stopPropagation()">
                    </div>
                    <div class="d-flex justify-content-center">
                        <input type="checkbox" class="form-check-input at-chk"
                               name="last16[{{ $team->id }}]" {{ $team->last16 ? 'checked' : '' }}
                               data-round="last16" data-team-id="{{ $team->id }}"
                               onchange="atAutoSave()" onclick="event.stopPropagation()">
                    </div>
                    <div class="d-flex justify-content-center">
                        <input type="checkbox" class="form-check-input at-chk"
                               name="quarterfinal[{{ $team->id }}]" {{ $team->quarterfinal ? 'checked' : '' }}
                               data-round="quarterfinal" data-team-id="{{ $team->id }}"
                               onchange="atAutoSave()" onclick="event.stopPropagation()">
                    </div>
                    <div class="d-flex justify-content-center">
                        <input type="checkbox" class="form-check-input at-chk"
                               name="semifinal[{{ $team->id }}]" {{ $team->semifinal ? 'checked' : '' }}
                               data-round="semifinal" data-team-id="{{ $team->id }}"
                               onchange="atAutoSave()" onclick="event.stopPropagation()">
                    </div>
                    <div class="d-flex justify-content-center">
                        <input type="number" class="at-tiny-input"
                               name="final[{{ $team->id }}]" value="{{ $team->final }}" min="0"
                               onfocus="this.select()" onblur="atAutoSave()"
                               onclick="event.stopPropagation()">
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
    </form>
</div>

{{-- Edit modal --}}
<div class="modal fade" id="atEditModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:1px solid var(--sb-border);box-shadow:0 8px 32px rgba(0,0,0,.12)">
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <h6 class="modal-title fw-bold" id="atModalHeading" style="font-size:.9rem"></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="{{ route('admin.updateTeamDetails') }}">
                @csrf
                <input type="hidden" name="teamID" id="atTeamID">
                <div class="modal-body px-4 pt-3 pb-2">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.78rem;font-weight:600;color:var(--sb-muted)">{{ __('Pavadinimas') }}</label>
                        <input type="text" class="form-control form-control-sm" name="teamName" id="atTeamName">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.78rem;font-weight:600;color:var(--sb-muted)">{{ __('Vėliavos URL') }}</label>
                        <input type="text" class="form-control form-control-sm" name="teamLink" id="atTeamLink" placeholder="https://…">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.78rem;font-weight:600;color:var(--sb-muted)">{{ __('Grupė') }}</label>
                        <input type="text" class="form-control form-control-sm" name="groupName" id="atGroupName" maxlength="2" placeholder="A">
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-2 justify-content-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm me-2" data-bs-dismiss="modal">{{ __('Atšaukti') }}</button>
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('Išsaugoti') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function atOpenModal(id, name, link, groupName) {
    document.getElementById('atModalHeading').textContent = 'Redaguoti: ' + name;
    document.getElementById('atTeamID').value    = id;
    document.getElementById('atTeamName').value  = name;
    document.getElementById('atTeamLink').value  = link;
    document.getElementById('atGroupName').value = groupName;
    new bootstrap.Modal(document.getElementById('atEditModal')).show();
}

var _atRounds = ['last32', 'last16', 'quarterfinal', 'semifinal'];
var _atLimits = { last32: 32, last16: 16, quarterfinal: 8, semifinal: 4 };

function atEnforceAllLimits() {
    _atRounds.forEach(function(round) {
        document.querySelectorAll('input.at-chk[data-round="' + round + '"]').forEach(function(cb) {
            cb.disabled = false;
        });
    });

    document.querySelectorAll('input.at-chk[data-round="last32"]').forEach(function(cb32) {
        var id  = cb32.dataset.teamId;
        var cb16 = document.querySelector('input.at-chk[data-round="last16"][data-team-id="' + id + '"]');
        var cbQF = document.querySelector('input.at-chk[data-round="quarterfinal"][data-team-id="' + id + '"]');
        var cbSF = document.querySelector('input.at-chk[data-round="semifinal"][data-team-id="' + id + '"]');

        var in32 = cb32.checked;
        var in16 = in32 && cb16 && cb16.checked;
        var inQF = in16 && cbQF && cbQF.checked;

        if (cb16 && !in32) { cb16.disabled = true; cb16.checked = false; }
        if (cbQF && !in16) { cbQF.disabled  = true; cbQF.checked  = false; }
        if (cbSF && !inQF) { cbSF.disabled  = true; cbSF.checked  = false; }
    });

    _atRounds.forEach(function(round) {
        var boxes   = document.querySelectorAll('input.at-chk[data-round="' + round + '"]');
        var checked = 0;
        boxes.forEach(function(cb) { if (cb.checked) checked++; });
        if (checked >= _atLimits[round]) {
            boxes.forEach(function(cb) { if (!cb.checked) cb.disabled = true; });
        }
    });
}

document.addEventListener('change', function(e) {
    var cb = e.target;
    if (!cb.matches('input.at-chk') || !cb.checked) return;
    var idx = _atRounds.indexOf(cb.dataset.round);
    if (idx < 1) return;
    var id = cb.dataset.teamId;
    for (var i = 0; i < idx; i++) {
        var lower = document.querySelector('input.at-chk[data-round="' + _atRounds[i] + '"][data-team-id="' + id + '"]');
        if (lower && !lower.disabled) lower.checked = true;
    }
}, true);

function atValidateGroupPositions() {
    var valid = true;
    var byGroup = {};

    document.querySelectorAll('input.at-pos-input').forEach(function(inp) {
        var grp = inp.dataset.group || '';
        if (!byGroup[grp]) byGroup[grp] = [];
        byGroup[grp].push(inp);
    });

    Object.keys(byGroup).forEach(function(grp) {
        var inputs = byGroup[grp];
        var vals   = inputs.map(function(i) { return i.value; }).filter(function(v) { return v !== ''; });
        inputs.forEach(function(inp) {
            var outOfRange = inp.value !== '' && (parseInt(inp.value, 10) < 1 || parseInt(inp.value, 10) > 6);
            var isDupe = inp.value !== '' && vals.filter(function(v) { return v === inp.value; }).length > 1;
            inp.classList.toggle('is-invalid', outOfRange);
            inp.classList.toggle('at-pos-warn', !outOfRange && isDupe);
            if (outOfRange) valid = false;
        });
    });

    return valid;
}

function atAutoSave() {
    atEnforceAllLimits();
    if (!atValidateGroupPositions()) return;
    document.getElementById('at-form').submit();
}

document.addEventListener('DOMContentLoaded', function() {
    atEnforceAllLimits();
    atValidateGroupPositions();
});
</script>

@endsection
