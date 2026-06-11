@extends('admin.layouts.master')
@section('content')

<div class="sb-card">
    <div class="sb-card-title d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span><i class="bi bi-clock-history sb-card-icon"></i> Auditas</span>
        <form method="GET" action="{{ route('admin.audit') }}" class="d-flex align-items-center gap-2">
            <select name="user" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                <option value="">— Visi vartotojai —</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ $userFilter == $u->id ? 'selected' : '' }}>
                        {{ $u->username }}
                    </option>
                @endforeach
            </select>
            @if($userFilter)
                <a href="{{ route('admin.audit') }}" class="btn btn-sm btn-outline-secondary">✕</a>
            @endif
        </form>
    </div>

    <ul class="nav nav-tabs mb-3" id="auditTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-logins-btn" data-bs-toggle="tab"
                    data-bs-target="#tab-logins" type="button" role="tab">
                <i class="bi bi-box-arrow-in-right"></i> Prisijungimai
                <span class="badge bg-secondary fw-normal ms-1">{{ $logins->total() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-predictions-btn" data-bs-toggle="tab"
                    data-bs-target="#tab-predictions" type="button" role="tab">
                <i class="bi bi-pencil-square"></i> Prognozės
                <span class="badge bg-secondary fw-normal ms-1">{{ $predictions->total() }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="auditTabContent">

        {{-- Login history --}}
        <div class="tab-pane fade show active" id="tab-logins" role="tabpanel">
            @if($logins->isEmpty())
                <p class="text-muted py-3">Prisijungimų įrašų nėra.</p>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-2">
                    <thead class="table-light">
                        <tr>
                            <th>Vartotojas</th>
                            <th>Metodas</th>
                            <th class="d-none d-md-table-cell">IP adresas</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logins as $login)
                        <tr>
                            <td>{{ $login->username ?? '—' }}</td>
                            <td>
                                @if($login->login_method === 'google')
                                    <span class="badge bg-danger">
                                        <i class="bi bi-google"></i> Google
                                    </span>
                                @else
                                    <span class="badge bg-primary">
                                        <i class="bi bi-envelope-fill"></i> El. paštas
                                    </span>
                                @endif
                            </td>
                            <td class="d-none d-md-table-cell text-muted"
                                style="font-family:monospace;font-size:.85rem;">
                                {{ $login->ip_address ?? '—' }}
                            </td>
                            <td style="white-space:nowrap;font-size:.85rem;">
                                {{ \Carbon\Carbon::parse($login->created_at)->format('Y-m-d H:i') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $logins->links() }}
            @endif
        </div>

        {{-- Prediction changes --}}
        <div class="tab-pane fade" id="tab-predictions" role="tabpanel">
            @if($predictions->isEmpty())
                <p class="text-muted py-3">Prognozių pakeitimų nėra.</p>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-2">
                    <thead class="table-light">
                        <tr>
                            <th>Vartotojas</th>
                            <th>Rungtynės</th>
                            <th class="text-end">Sena</th>
                            <th></th>
                            <th>Nauja</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($predictions as $p)
                        <tr>
                            <td>{{ $p->username ?? '—' }}</td>
                            <td style="font-size:.85rem;">
                                {{ $p->home_team ?? '?' }} — {{ $p->away_team ?? '?' }}
                            </td>
                            <td class="text-end text-muted" style="font-size:.85rem;">
                                @if($p->old_home_team_score !== null)
                                    {{ $p->old_home_team_score }} : {{ $p->old_away_team_score }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-muted px-1">→</td>
                            <td style="font-size:.85rem;font-weight:500;">
                                {{ $p->home_team_score }} : {{ $p->away_team_score }}
                            </td>
                            <td style="white-space:nowrap;font-size:.85rem;">
                                {{ \Carbon\Carbon::parse($p->created_at)->format('Y-m-d H:i') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $predictions->links() }}
            @endif
        </div>

    </div>
</div>

@endsection
