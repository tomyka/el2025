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
                @php
                    $adminLevel = $user->user_setting?->admin ?? 0;
                    $isActive   = $user->user_setting?->active ?? true;
                @endphp
                <tr class="{{ !$isActive ? 'au-row-hidden' : '' }}">
                    <td class="au-id">{{ $user->id }}</td>

                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="au-avatar {{ !$isActive ? 'au-avatar-hidden' : '' }}">{{ strtoupper(substr($user->username ?? '?', 0, 1)) }}</div>
                            <div>
                                <div class="au-username">
                                    {{ $user->username }}
                                    @if(!$isActive)
                                    <span class="au-hidden-badge" title="Neaktyvus — paslėptas nuo lentelių">neaktyvus</span>
                                    @endif
                                </div>
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
                                    {{ session('admin') < 9 ? 'disabled' : '' }}
                                    onchange="this.form.submit()">
                                <option value="0" {{ $adminLevel == 0 ? 'selected' : '' }}>User</option>
                                <option value="5" {{ $adminLevel == 5 ? 'selected' : '' }}>Editor</option>
                                @if(session('admin') >= 8)
                                <option value="8" {{ $adminLevel == 8 ? 'selected' : '' }}>Admin</option>
                                @elseif($adminLevel == 8)
                                <option value="8" selected disabled>Admin</option>
                                @endif
                                @if(session('admin') >= 8)
                                <option value="9" {{ $adminLevel == 9 ? 'selected' : '' }}>Super</option>
                                @elseif($adminLevel == 9)
                                <option value="9" selected disabled>Super</option>
                                @endif
                            </select>
                        </form>
                    </td>

                    <td class="text-center" style="white-space:nowrap;">
                        @if($adminLevel === 8 && $user->id === (int) session('userID'))
                        <form method="post" action="{{ route('admin.promoteSelf') }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="au-action-btn au-action-promote" title="Pakelti save į Superadmin">
                                <i class="bi bi-shield-lock-fill"></i>
                            </button>
                        </form>
                        @endif
                        @if(session('admin') >= 5)
                        <a href="{{ route('admin.audit', ['user' => $user->id]) }}"
                           class="au-action-btn" title="Auditas" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">
                            <i class="bi bi-clock-history"></i>
                        </a>
                        @endif
                        @if(session('admin') >= 9)
                        <button type="button"
                                class="au-action-btn au-action-hide {{ !$isActive ? 'au-action-hidden-active' : '' }}"
                                title="{{ !$isActive ? 'Aktyvuoti vartotoją' : 'Deaktyvuoti vartotoją' }}"
                                data-user-id="{{ $user->id }}"
                                data-toggle-url="{{ route('admin.toggleHidden') }}"
                                onclick="toggleHiddenUser(this)">
                            <i class="bi {{ !$isActive ? 'bi-eye' : 'bi-eye-slash' }}"></i>
                        </button>
                        <form method="post" action="{{ route('admin.deleteUser') }}"
                              data-confirm-name="{{ $user->username }}"
                              onsubmit="return confirm('Ištrinti vartotoją ' + this.dataset.confirmName + '?')"
                              style="display:inline;">
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
<script>
function toggleHiddenUser(btn) {
    const userID = btn.dataset.userId;
    const url    = btn.dataset.toggleUrl;
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ userID }),
    })
    .then(r => r.json())
    .then(data => {
        const row    = btn.closest('tr');
        const badge  = row.querySelector('.au-hidden-badge');
        const avatar = row.querySelector('.au-avatar');
        const icon   = btn.querySelector('i');
        if (!data.active) {
            row.classList.add('au-row-hidden');
            avatar.classList.add('au-avatar-hidden');
            btn.classList.add('au-action-hidden-active');
            btn.title = 'Aktyvuoti vartotoją';
            icon.className = 'bi bi-eye';
            if (!badge) {
                const nameDiv = row.querySelector('.au-username');
                nameDiv.insertAdjacentHTML('beforeend', ' <span class="au-hidden-badge" title="Neaktyvus — paslėptas nuo lentelių">neaktyvus</span>');
            }
        } else {
            row.classList.remove('au-row-hidden');
            avatar.classList.remove('au-avatar-hidden');
            btn.classList.remove('au-action-hidden-active');
            btn.title = 'Deaktyvuoti vartotoją';
            icon.className = 'bi bi-eye-slash';
            row.querySelector('.au-hidden-badge')?.remove();
        }
    });
}
</script>
@endsection
