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

                <tr>
                    <td>
                        <input type="hidden" name="teamID[{{ $team->id }}]" value="{{ $team->id }}">
                        @if($team->link)
                        <img src="{{ $team->link }}" alt="" class="at-flag" onerror="this.style.display='none'">
                        @else
                        <div class="at-flag-empty"><i class="bi bi-image text-muted"></i></div>
                        @endif
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm"
                               name="team[{{ $team->id }}]" value="{{ $team->team }}"
                               onchange="document.getElementById('teams-form').submit()">
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm at-link-input"
                               name="link[{{ $team->id }}]" value="{{ $team->link }}" placeholder="https://…"
                               onchange="document.getElementById('teams-form').submit()">
                    </td>
                    <td class="text-center">
                        <input type="text" class="form-control form-control-sm text-center at-tiny-input"
                               name="groupName[{{ $team->id }}]" value="{{ $team->group_name }}" maxlength="2"
                               onchange="document.getElementById('teams-form').submit()">
                    </td>
                    <td class="text-center">
                        <input type="number" class="form-control form-control-sm text-center at-tiny-input"
                               name="groupPosition[{{ $team->id }}]" value="{{ $team->group_position }}" min="1" max="6"
                               onchange="document.getElementById('teams-form').submit()">
                    </td>
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input at-chk"
                               name="last32[{{ $team->id }}]" {{ $team->last32 ? 'checked' : '' }}
                               onchange="document.getElementById('teams-form').submit()">
                    </td>
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input at-chk"
                               name="last16[{{ $team->id }}]" {{ $team->last16 ? 'checked' : '' }}
                               onchange="document.getElementById('teams-form').submit()">
                    </td>
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input at-chk"
                               name="quarterfinal[{{ $team->id }}]" {{ $team->quarterfinal ? 'checked' : '' }}
                               onchange="document.getElementById('teams-form').submit()">
                    </td>
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input at-chk"
                               name="semifinal[{{ $team->id }}]" {{ $team->semifinal ? 'checked' : '' }}
                               onchange="document.getElementById('teams-form').submit()">
                    </td>
                    <td class="text-center">
                        <input type="number" class="form-control form-control-sm text-center at-tiny-input"
                               name="final[{{ $team->id }}]" value="{{ $team->final }}" min="0"
                               onchange="document.getElementById('teams-form').submit()">
                    </td>
                </tr>

                @endforeach
            </tbody>
        </table>
    </div>
</div>

</form>
@endsection
