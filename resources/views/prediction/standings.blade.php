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

    </form>

@endsection

<script>
    function updateUserStandings(prediction_standingID) {
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
