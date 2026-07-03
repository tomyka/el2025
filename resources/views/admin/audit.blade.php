@extends('admin.layouts.master')
@section('content')

<div class="sb-card">
    <div class="sb-card-title d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span><i class="bi bi-clock-history sb-card-icon"></i> {{ __('Auditas') }}</span>
        <form method="GET" action="{{ route('admin.audit') }}" class="d-flex align-items-center gap-2">
            <select name="user" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                <option value="">{{ __('— Visi vartotojai —') }}</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ $userFilter == $u->id ? 'selected' : '' }}>
                        {{ $u->username }}
                    </option>
                @endforeach
            </select>
            @if($userFilter)
                <a href="{{ route('admin.audit') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-lg"></i>
                </a>
            @endif
        </form>
    </div>

    <ul class="nav nav-tabs adt-tabs mb-3" id="auditTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-logins-btn" data-bs-toggle="tab"
                    data-bs-target="#tab-logins" type="button" role="tab">
                <i class="bi bi-box-arrow-in-right"></i> {{ __('Prisijungimai') }}
                <span class="badge bg-secondary fw-normal ms-1">{{ $logins->total() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-predictions-btn" data-bs-toggle="tab"
                    data-bs-target="#tab-predictions" type="button" role="tab">
                <i class="bi bi-pencil-square"></i> {{ __('Prognozės') }}
                <span class="badge bg-secondary fw-normal ms-1">{{ $predictions->total() }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="auditTabContent">

        {{-- Login history --}}
        <div class="tab-pane fade show active" id="tab-logins" role="tabpanel">
            @if($logins->isEmpty())
                <p class="text-muted py-3">{{ __('Prisijungimų įrašų nėra.') }}</p>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-2 adt-table">
                    <thead class="table-light">
                        <tr>
                            <th class="adt-col-user">{{ __('Vartotojas') }}</th>
                            <th class="adt-col-method">{{ __('Metodas') }}</th>
                            <th class="adt-col-ip d-none d-md-table-cell">{{ __('IP adresas') }}</th>
                            <th class="adt-col-date">{{ __('Data') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logins as $login)
                        <tr>
                            <td>{{ $login->username ?? '—' }}</td>
                            <td>
                                @if($login->login_method === 'register')
                                    <span class="badge bg-success fw-normal">
                                        <i class="bi bi-person-plus-fill"></i> {{ __('Registracija') }}
                                    </span>
                                @elseif($login->login_method === 'register_google')
                                    <span class="badge bg-success fw-normal">
                                        <i class="bi bi-google"></i> {{ __('Registracija (Google)') }}
                                    </span>
                                @elseif($login->login_method === 'google')
                                    <span class="badge adt-badge-google fw-normal">
                                        <i class="bi bi-google"></i> {{ __('Google') }}
                                    </span>
                                @else
                                    <span class="badge bg-primary fw-normal">
                                        <i class="bi bi-envelope-fill"></i> {{ __('El. paštas') }}
                                    </span>
                                @endif
                            </td>
                            <td class="adt-mono d-none d-md-table-cell">{{ $login->ip_address ?? '—' }}</td>
                            <td class="adt-date">{{ \Carbon\Carbon::parse($login->created_at)->format('Y-m-d H:i') }}</td>
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
                <p class="text-muted py-3">{{ __('Prognozių pakeitimų nėra.') }}</p>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-2 adt-table">
                    <thead class="table-light">
                        <tr>
                            <th class="adt-col-user">{{ __('Vartotojas') }}</th>
                            <th class="adt-col-match">{{ __('Rungtynės') }}</th>
                            <th class="adt-col-score text-end">{{ __('Sena') }}</th>
                            <th class="adt-col-arrow"></th>
                            <th class="adt-col-score">{{ __('Nauja') }}</th>
                            <th class="adt-col-date">{{ __('Data') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($predictions as $p)
                        <tr>
                            <td>{{ $p->username ?? '—' }}</td>
                            <td class="adt-match">{{ $p->home_team ?? '?' }} — {{ $p->away_team ?? '?' }}</td>
                            <td class="adt-score-old text-end">
                                @if($p->old_home_team_score !== null)
                                    {{ $p->old_home_team_score }}:{{ $p->old_away_team_score }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="adt-col-arrow text-muted">→</td>
                            <td class="adt-score-new">{{ $p->home_team_score }}:{{ $p->away_team_score }}</td>
                            <td class="adt-date">{{ \Carbon\Carbon::parse($p->created_at)->format('Y-m-d H:i') }}</td>
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
