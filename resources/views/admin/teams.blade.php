@extends('admin.layouts.master')
@section('content')

<form method="post" action="{{ route('admin.teams') }}" id="teams-form">
@csrf

<div class="sb-card">
    <div class="sb-card-title">
        <i class="bi bi-flag-fill sb-card-icon"></i> Komandos
        <span class="badge bg-secondary fw-normal ms-1">{{ $teams->count() }}</span>
    </div>

    @if(Session::has('info'))
    <div class="alert alert-success py-2 mb-3">{{ Session::get('info') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 at-table">
            <thead class="table-light">
                <tr>
                    <th class="at-col-flag"></th>
                    <th class="at-col-name">Komanda</th>
                    <th class="at-col-link">Vėliava (URL)</th>
                    <th class="at-col-short text-center" title="Grupė">Grp</th>
                    <th class="at-col-short text-center" title="Vieta grupėje">#</th>
                    <th class="at-col-chk text-center" title="Šešioliktfinalis">Š16</th>
                    <th class="at-col-chk text-center" title="Aštuntfinalis">Š8</th>
                    <th class="at-col-chk text-center" title="Ketvirtfinalis">KF</th>
                    <th class="at-col-chk text-center" title="Pusfinalis">PF</th>
                    <th class="at-col-short text-center" title="Finalas">F</th>
                </tr>
            </thead>
            <tbody>
                @php $prevGroup = null; @endphp
                @foreach($teams->sortBy([['group_name', 'asc'], ['group_position', 'asc']]) as $team)

                @if($team->group_name !== $prevGroup)
                <tr class="at-group-sep">
                    <td colspan="10">Grupė {{ $team->group_name ?: '—' }}</td>
                </tr>
                @php $prevGroup = $team->group_name; @endphp
                @endif

                <tr class="at-team-row" data-group="{{ $team->group_name }}">
                    <td>
                        <input type="hidden" name="teamID[{{ $team->id }}]" value="{{ $team->id }}">
                        @if($team->link)
                        <img src="{{ $team->link }}" alt="" class="at-flag" onerror="this.style.display='none'">
                        @else
                        <div class="at-flag-empty"><i class="bi bi-image text-muted"></i></div>
                        @endif
                    </td>
                    <td>
                        {{-- Text inputs: onchange fires on blur only (correct behaviour) --}}
                        <input type="text" class="form-control form-control-sm"
                               name="team[{{ $team->id }}]" value="{{ $team->team }}"
                               onchange="atAutoSave()">
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm at-link-input"
                               name="link[{{ $team->id }}]" value="{{ $team->link }}" placeholder="https://…"
                               onchange="atAutoSave()">
                    </td>
                    <td class="text-center">
                        <input type="text" class="form-control form-control-sm text-center at-tiny-input"
                               name="groupName[{{ $team->id }}]" value="{{ $team->group_name }}" maxlength="2"
                               onchange="atAutoSave()">
                    </td>
                    <td class="text-center">
                        {{-- Number inputs: onblur (not onchange) to avoid firing on arrow keys / scroll --}}
                        {{-- onfocus select-all prevents accidental concatenation (e.g. "3"→type "1"→"31") --}}
                        <input type="number" class="form-control form-control-sm text-center at-tiny-input at-pos-input"
                               name="groupPosition[{{ $team->id }}]" value="{{ $team->group_position }}"
                               min="1" max="6" data-group="{{ $team->group_name }}"
                               onfocus="this.select()"
                               onblur="atAutoSave()">
                    </td>
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input at-chk"
                               name="last32[{{ $team->id }}]" {{ $team->last32 ? 'checked' : '' }}
                               data-round="last32" data-team-id="{{ $team->id }}"
                               onchange="atAutoSave()">
                    </td>
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input at-chk"
                               name="last16[{{ $team->id }}]" {{ $team->last16 ? 'checked' : '' }}
                               data-round="last16" data-team-id="{{ $team->id }}"
                               onchange="atAutoSave()">
                    </td>
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input at-chk"
                               name="quarterfinal[{{ $team->id }}]" {{ $team->quarterfinal ? 'checked' : '' }}
                               data-round="quarterfinal" data-team-id="{{ $team->id }}"
                               onchange="atAutoSave()">
                    </td>
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input at-chk"
                               name="semifinal[{{ $team->id }}]" {{ $team->semifinal ? 'checked' : '' }}
                               data-round="semifinal" data-team-id="{{ $team->id }}"
                               onchange="atAutoSave()">
                    </td>
                    <td class="text-center">
                        <input type="number" class="form-control form-control-sm text-center at-tiny-input"
                               name="final[{{ $team->id }}]" value="{{ $team->final }}" min="0"
                               onfocus="this.select()"
                               onblur="atAutoSave()">
                    </td>
                </tr>

                @endforeach
            </tbody>
        </table>
    </div>
</div>

</form>

<script>
var _atRounds = ['last32', 'last16', 'quarterfinal', 'semifinal'];
var _atLimits = { last32: 32, last16: 16, quarterfinal: 8, semifinal: 4 };

function atEnforceAllLimits() {
    _atRounds.forEach(function(round) {
        document.querySelectorAll('input.at-chk[data-round="' + round + '"]').forEach(function(cb) {
            cb.disabled = false;
        });
    });

    // Cascade DOWN: unchecking a round disables and clears all later rounds for that team
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

    // Global caps: disable unchecked boxes once the limit is reached
    _atRounds.forEach(function(round) {
        var boxes   = document.querySelectorAll('input.at-chk[data-round="' + round + '"]');
        var checked = 0;
        boxes.forEach(function(cb) { if (cb.checked) checked++; });
        if (checked >= _atLimits[round]) {
            boxes.forEach(function(cb) { if (!cb.checked) cb.disabled = true; });
        }
    });
}

// Cascade UP: checking a round auto-checks all prerequisite lower rounds
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
            // Range check: must be 1–6
            var outOfRange = inp.value !== '' && (parseInt(inp.value, 10) < 1 || parseInt(inp.value, 10) > 6);
            // Duplicate check: warn only, don't block save
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
    document.getElementById('teams-form').submit();
}

document.addEventListener('DOMContentLoaded', function() {
    atEnforceAllLimits();
    atValidateGroupPositions();
});
</script>

@endsection
