@extends('admin.layouts.master')
@section('content')

<div class="sb-card">
    <div class="sb-card-title d-flex align-items-center gap-2">
        <i class="bi bi-people-fill sb-card-icon"></i> Vartotojai
        <span class="badge bg-secondary fw-normal">{{ count($users) }}</span>
    </div>

    @if(count($errors->all()))
    <div class="alert alert-danger py-2 mb-3">
        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
    @endif
    @if(Session::has('info'))
    <div class="alert alert-success py-2 mb-3">{{ Session::get('info') }}</div>
    @endif
    @if(Session::has('error'))
    <div class="alert alert-danger py-2 mb-3">{{ Session::get('error') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 au-table">
            <thead class="table-light">
                <tr>
                    <th class="au-col-id">#</th>
                    <th>Vartotojas</th>
                    <th class="d-none d-md-table-cell">El. paštas</th>
                    <th class="d-none d-lg-table-cell au-col-auth">Auth</th>
                    <th class="au-col-admin">Admin</th>
                    <th class="au-col-actions"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                @php $adminLevel = $user->user_setting?->admin ?? 0; @endphp
                <tr>
                    <td class="au-id">{{ $user->id }}</td>

                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="au-avatar">{{ strtoupper(substr($user->username ?? '?', 0, 1)) }}</div>
                            <div>
                                <div class="au-username">{{ $user->username }}</div>
                                @if($user->name || $user->surname)
                                <div class="au-fullname">{{ trim($user->name . ' ' . $user->surname) }}</div>
                                @endif
                            </div>
                        </div>
                    </td>

                    <td class="d-none d-md-table-cell au-email">{{ $user->email }}</td>

                    <td class="d-none d-lg-table-cell text-center">
                        @if($user->google_id)
                        <span class="au-auth-badge au-auth-google" title="Google"><i class="bi bi-google"></i></span>
                        @else
                        <span class="au-auth-badge au-auth-email" title="El. paštas"><i class="bi bi-envelope-fill"></i></span>
                        @endif
                    </td>

                    <td class="text-center">
                        <form method="post" action="{{ route('admin.updateUser') }}" style="margin:0;">
                            @csrf
                            <input type="hidden" name="userID"   value="{{ $user->id }}">
                            <input type="hidden" name="username" value="{{ $user->username }}">
                            <select name="admin"
                                    class="form-select form-select-sm au-admin-select {{ $adminLevel >= 1 ? 'au-admin-elevated' : '' }}"
                                    {{ session('admin') < 8 ? 'disabled' : '' }}
                                    onchange="this.form.submit()">
                                <option value="0" {{ $adminLevel == 0 ? 'selected' : '' }}>User</option>
                                <option value="1" {{ $adminLevel == 1 ? 'selected' : '' }}>Admin</option>
                                <option value="9" {{ $adminLevel == 9 ? 'selected' : '' }}>Super</option>
                            </select>
                        </form>
                    </td>

                    <td class="text-center">
                        @if(session('admin') == 9)
                        <form method="post" action="{{ route('admin.deleteUser') }}"
                              onsubmit="return confirm('Ištrinti vartotoją {{ addslashes($user->username) }}?')">
                            @csrf
                            <input type="hidden" name="userID"   value="{{ $user->id }}">
                            <input type="hidden" name="username" value="{{ $user->username }}">
                            <button type="submit" class="au-action-btn au-action-delete" title="Ištrinti">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
