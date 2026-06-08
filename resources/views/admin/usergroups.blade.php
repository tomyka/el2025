@extends('admin.layouts.master')
@section('content')

<div class="sb-card">
    <div class="sb-card-title">
        <i class="bi bi-diagram-3-fill sb-card-icon"></i> Vartotojų grupės
        <span class="badge bg-secondary fw-normal ms-1">{{ $userGroups->count() }}</span>
    </div>

    @if(Session::has('info'))
    <div class="alert alert-success py-2 mb-3">{{ Session::get('info') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem;">
            <thead class="table-light">
                <tr>
                    <th style="width:40px" class="text-muted" title="ID">#</th>
                    <th>Vartotojas</th>
                    <th>Grupė</th>
                    <th style="width:70px" class="text-center">Aktyvus</th>
                    <th style="width:70px" class="text-center">Svečias</th>
                    <th style="width:70px" class="text-center">Mokestis</th>
                </tr>
            </thead>
            <tbody>
                @php $prevGroup = null; @endphp
                @foreach($userGroups as $ug)
                @if($ug->group_id !== $prevGroup)
                <tr style="background: var(--sb-surface);">
                    <td colspan="6" style="font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--sb-muted); padding:6px 10px; border-top:2px solid var(--sb-border);">
                        {{ $ug->group->group ?? '—' }}
                    </td>
                </tr>
                @php $prevGroup = $ug->group_id; @endphp
                @endif
                <tr>
                    <td class="text-muted" style="font-size:.75rem;">{{ $ug->id }}</td>
                    <td>
                        <div style="font-weight:600;">{{ $ug->user->username ?? '—' }}</div>
                        <div style="font-size:.78rem; color:var(--sb-muted);">{{ trim(($ug->user->name ?? '') . ' ' . ($ug->user->surname ?? '')) ?: null }}</div>
                    </td>
                    <td style="color:var(--sb-muted); font-size:.82rem;">{{ $ug->group->group ?? '—' }}</td>
                    <td class="text-center">
                        @if($ug->active)
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Taip</span>
                        @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Ne</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($ug->guest)
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Taip</span>
                        @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Ne</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($ug->fee)
                        <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-lg"></i></span>
                        @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="bi bi-dash"></i></span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
