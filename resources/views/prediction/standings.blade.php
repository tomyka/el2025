@extends('layouts.master')
@section('content')

    @if (count($errors->all()))
        <div class="alert alert-danger mb-3">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if (Session::has('info'))
        <p class="alert alert-primary">{{ Session::get('info') }}</p>
    @endif

    @php $grouped = collect($predictionStandings)->groupBy('group_name'); @endphp

    <form method="post" action="">
        {{csrf_field()}}

        <div class="ps-groups-grid">
            @foreach ($grouped as $groupName => $teams)
            <div class="ps-group-card">
                <div class="ps-group-title">Grupė {{ $groupName }}</div>

                <div class="ps-group-row ps-group-header-row">
                    <span></span>
                    <span class="text-center">Vieta</span>
                    <span class="text-center d-none d-sm-block">1/16</span>
                    <span class="text-center d-sm-none">16</span>
                    <span class="text-center d-none d-sm-block">1/8</span>
                    <span class="text-center d-sm-none">8</span>
                    <span class="text-center d-none d-sm-block">1/4</span>
                    <span class="text-center d-sm-none">4</span>
                    <span class="text-center d-none d-sm-block">1/2</span>
                    <span class="text-center d-sm-none">2</span>
                    <span class="text-center">F</span>
                </div>

                @foreach ($teams as $s)
                <div class="ps-group-row prediction-row" data-group="{{ $s->group_name }}">
                    <input type="hidden" name="prediction_standingID{{$s->id}}" id="prediction_standingID{{$s->id}}" value="{{$s->id}}">
                    <div class="d-flex align-items-center gap-1">
                        <img src="{{URL::to('img/teams/'.str_replace(' ','%20',strtolower($s->team)).'.svg')}}" width="18">
                        <span class="ps-team-name">{{ $s->team }}</span>
                    </div>
                    <div class="d-flex justify-content-center">
                        <input class="ps-input ps-pos-input" type="number" min="1" max="4"
                               onchange="updateUserStandings({{$s->id}})"
                               name="groupPosition{{$s->id}}" id="groupPosition{{$s->id}}"
                               value="{{ $s->group_position }}" {{session('disabled')}}>
                    </div>
                    <div class="d-flex justify-content-center">
                        <input type="checkbox" class="form-check-input"
                               onchange="updateUserStandings({{$s->id}})"
                               name="last32{{$s->id}}" id="last32{{$s->id}}"
                               {{($s->last32==1 ? 'checked' : '')}} {{session('disabled')}}>
                    </div>
                    <div class="d-flex justify-content-center">
                        <input type="checkbox" class="form-check-input"
                               onchange="updateUserStandings({{$s->id}})"
                               name="last16{{$s->id}}" id="last16{{$s->id}}"
                               {{($s->last16==1 ? 'checked' : '')}} {{session('disabled')}}>
                    </div>
                    <div class="d-flex justify-content-center">
                        <input type="checkbox" class="form-check-input"
                               onchange="updateUserStandings({{$s->id}})"
                               name="quarterfinal{{$s->id}}" id="quarterfinal{{$s->id}}"
                               {{($s->quarterfinal==1 ? 'checked' : '')}} {{session('disabled')}}>
                    </div>
                    <div class="d-flex justify-content-center">
                        <input type="checkbox" class="form-check-input"
                               onchange="updateUserStandings({{$s->id}})"
                               name="semifinal{{$s->id}}" id="semifinal{{$s->id}}"
                               {{($s->semifinal==1 ? 'checked' : '')}} {{session('disabled')}}>
                    </div>
                    <div class="d-flex justify-content-center">
                        <input class="ps-input" type="number" min="1" max="4"
                               onchange="updateUserStandings({{$s->id}})"
                               name="final{{$s->id}}" id="final{{$s->id}}"
                               value="{{$s->final}}" {{session('disabled')}}>
                    </div>
                </div>
                @endforeach
            </div>
            @endforeach
        </div>

        <div class="ps-legend mt-3">
            <p><strong>Vieta</strong> — vieta grupėje (1–4).</p>
            <p><strong>1/16 – 1/2</strong> — pažymėkite komandas, patenkančias į kiekvieną etapą.</p>
            <p><strong>F</strong> — galutinė vieta finalo etape (1–4).</p>
        </div>

        <div id="ps-progress" class="mt-3 d-flex flex-wrap gap-2">
            <span class="badge bg-secondary" id="prog-pos">Vieta: 0 / {{ count($predictionStandings) }}</span>
            @php $totalTeams = count($predictionStandings); @endphp
            @if ($totalTeams >= 32)
            <span class="badge bg-secondary" id="prog-last32">1/16: 0 / 32</span>
            @endif
            <span class="badge bg-secondary" id="prog-last16">1/8: 0 / 16</span>
            <span class="badge bg-secondary" id="prog-qf">1/4: 0 / 8</span>
            <span class="badge bg-secondary" id="prog-sf">1/2: 0 / 4</span>
            <span class="badge bg-secondary" id="prog-final">F: 0 / 4</span>
        </div>

    </form>

@endsection

<script>
    var predictionLocked = {{ session('disabled') === 'disabled' ? 'true' : 'false' }};

    function updateProgressCounters() {
        var totalTeams = document.querySelectorAll('.prediction-row').length;

        // group positions filled
        var posFilled = 0;
        document.querySelectorAll('.ps-pos-input').forEach(function(inp) {
            if (inp.value !== '') posFilled++;
        });
        var posEl = document.getElementById('prog-pos');
        if (posEl) {
            posEl.textContent = 'Vieta: ' + posFilled + ' / ' + totalTeams;
            posEl.className   = 'badge ' + (posFilled === totalTeams ? 'bg-success' : 'bg-danger');
        }

        // last32
        var l32El = document.getElementById('prog-last32');
        if (l32El) {
            var l32 = document.querySelectorAll('input[type="checkbox"][id^="last32"]:checked').length;
            l32El.textContent = '1/16: ' + l32 + ' / 32';
            l32El.className   = 'badge ' + (l32 === 32 ? 'bg-success' : 'bg-danger');
        }

        var targets = [['last16', 'prog-last16', '1/8: ', 16],
                       ['quarterfinal', 'prog-qf', '1/4: ', 8],
                       ['semifinal', 'prog-sf', '1/2: ', 4]];
        targets.forEach(function(t) {
            var el = document.getElementById(t[1]);
            if (!el) return;
            var n = document.querySelectorAll('input[type="checkbox"][id^="' + t[0] + '"]:checked').length;
            el.textContent = t[2] + n + ' / ' + t[3];
            el.className   = 'badge ' + (n === t[3] ? 'bg-success' : 'bg-danger');
        });

        // final positions filled (non-empty number inputs with id^=final)
        var finalFilled = 0;
        document.querySelectorAll('input[type="number"][id^="final"]').forEach(function(inp) {
            if (inp.value !== '') finalFilled++;
        });
        var finEl = document.getElementById('prog-final');
        if (finEl) {
            finEl.textContent = 'F: ' + finalFilled + ' / 4';
            finEl.className   = 'badge ' + (finalFilled === 4 ? 'bg-success' : 'bg-danger');
        }
    }

    function enforceAllLimits() {
        // Only reset disabled states when prediction is still open
        if (!predictionLocked) {
            ['last32', 'last16', 'quarterfinal', 'semifinal'].forEach(function(prefix) {
                document.querySelectorAll('input[type="checkbox"][id^="' + prefix + '"]').forEach(function(cb) {
                    cb.disabled = false;
                });
            });
            document.querySelectorAll('input[type="number"][id^="final"]').forEach(function(inp) {
                inp.disabled = false;
            });
        }

        // Cascade: if team not in lower round, disable all higher rounds for that team
        document.querySelectorAll('input[type="checkbox"][id^="last32"]').forEach(function(cb) {
            var id  = cb.id.replace('last32', '');
            var l16 = document.getElementById('last16' + id);
            var qf  = document.getElementById('quarterfinal' + id);
            var sf  = document.getElementById('semifinal' + id);
            var fin = document.getElementById('final' + id);

            var in32 = cb.checked;
            var in16 = in32 && l16 && l16.checked;
            var inQF = in16 && qf  && qf.checked;
            var inSF = inQF && sf  && sf.checked;

            if (l16 && !in32) { l16.disabled = true; l16.checked = false; }
            if (qf  && !in16) { qf.disabled  = true; qf.checked  = false; }
            if (sf  && !inQF) { sf.disabled  = true; sf.checked  = false; }
            if (fin && !inSF) { fin.disabled = true; fin.value   = '';    }
        });

        // Global limits: disable unchecked boxes once cap is reached
        [['last32', 32], ['last16', 16], ['quarterfinal', 8], ['semifinal', 4]].forEach(function(pair) {
            var prefix = pair[0], limit = pair[1];
            var boxes = document.querySelectorAll('input[type="checkbox"][id^="' + prefix + '"]');
            var checked = 0;
            boxes.forEach(function(cb) { if (cb.checked) checked++; });
            if (checked >= limit) {
                boxes.forEach(function(cb) { if (!cb.checked) cb.disabled = true; });
            }
        });

        // Per-group cap for last32: max 3 per group (at most 3 of 4 teams advance)
        document.querySelectorAll('.ps-group-card').forEach(function(card) {
            var boxes = card.querySelectorAll('input[type="checkbox"][id^="last32"]');
            var checked = 0;
            boxes.forEach(function(cb) { if (cb.checked) checked++; });
            if (checked >= 3) {
                boxes.forEach(function(cb) { if (!cb.checked) cb.disabled = true; });
            }
        });

        updateProgressCounters();
    }

    function validateGroupPositions() {
        document.querySelectorAll('.ps-group-card').forEach(function(card) {
            var inputs = card.querySelectorAll('.ps-pos-input');
            var seen = {};
            inputs.forEach(function(inp) {
                var v = inp.value;
                if (v !== '') seen[v] = (seen[v] || 0) + 1;
            });
            inputs.forEach(function(inp) {
                var v = inp.value;
                if (v === '') { setInputState(inp, ''); return; }
                setInputState(inp, seen[v] > 1 ? 'error' : 'ok');
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        enforceAllLimits();
        validateGroupPositions();
        updateProgressCounters();
    });

    // Cascade UP: checking a round auto-checks all higher rounds for that team.
    // Capture phase ensures this runs before inline onchange → AJAX reads updated state.
    var _bracketRounds = ['last32', 'last16', 'quarterfinal', 'semifinal'];
    document.addEventListener('change', function(e) {
        var cb = e.target;
        if (!cb.matches('input[type="checkbox"]') || !cb.checked) return;
        for (var idx = 0; idx < _bracketRounds.length; idx++) {
            if (cb.id.startsWith(_bracketRounds[idx])) {
                var id = cb.id.replace(_bracketRounds[idx], '');
                for (var i = idx + 1; i < _bracketRounds.length; i++) {
                    var higher = document.getElementById(_bracketRounds[i] + id);
                    if (higher && !higher.disabled) higher.checked = true;
                }
                break;
            }
        }
    }, true);

    function updateUserStandings(prediction_standingID) {
        enforceAllLimits();
        var groupPositionEl = document.getElementById('groupPosition' + prediction_standingID);
        var finalEl         = document.getElementById('final' + prediction_standingID);

        if (groupPositionEl.value !== '') {
            var posVal = parseInt(groupPositionEl.value);
            if (posVal < 1 || posVal > 4) {
                setInputState(groupPositionEl, 'error');
                return;
            }
            var row   = groupPositionEl.closest('.prediction-row');
            var group = row ? row.dataset.group : null;
            if (group) {
                var siblings = document.querySelectorAll('.prediction-row[data-group="' + group + '"] .ps-pos-input');
                var duplicate = false;
                siblings.forEach(function (inp) {
                    if (inp !== groupPositionEl && inp.value === groupPositionEl.value) duplicate = true;
                });
                if (duplicate) {
                    setInputState(groupPositionEl, 'error');
                    return;
                }
            }
            setInputState(groupPositionEl, 'ok');
        } else {
            setInputState(groupPositionEl, '');
        }

        $.ajax({
            type: 'POST',
            url: '{{ route("prediction.standings") }}',
            data: {
                prediction_standingID : prediction_standingID,
                groupPosition : groupPositionEl.value,
                last32        : document.getElementById('last32'       + prediction_standingID).checked ? 1 : 0,
                last16        : document.getElementById('last16'       + prediction_standingID).checked ? 1 : 0,
                quarterfinal  : document.getElementById('quarterfinal' + prediction_standingID).checked ? 1 : 0,
                semifinal     : document.getElementById('semifinal'    + prediction_standingID).checked ? 1 : 0,
                final         : finalEl.value,
            },
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function () {
            if (groupPositionEl.value !== '') setInputState(groupPositionEl, 'ok');
            if (finalEl.value !== '')         setInputState(finalEl, 'ok');
            validateGroupPositions();
            updateProgressCounters();
        }).fail(function () {
            if (groupPositionEl.value !== '') setInputState(groupPositionEl, 'error');
            if (finalEl.value !== '')         setInputState(finalEl, 'error');
        });
    }

    function setInputState(el, state) {
        el.classList.remove('ps-input-ok', 'ps-input-error');
        if (state === 'ok')    el.classList.add('ps-input-ok');
        if (state === 'error') el.classList.add('ps-input-error');
    }
</script>
