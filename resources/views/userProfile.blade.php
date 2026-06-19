@extends('layouts.master')
@section('content')

{{-- Flash alerts --}}
@if(Session::has('info'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ Session::get('info') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('status') === 'password-updated')
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>Slaptažodis sėkmingai pakeistas.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Profile header --}}
<div class="sb-card mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="profile-avatar">
            {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}{{ strtoupper(substr($user->surname ?? '', 0, 1)) }}
        </div>
        <div class="flex-grow-1 min-w-0">
            <div class="fw-bold fs-6 lh-1 mb-1">{{ $user->name }} {{ $user->surname }}</div>
            <div class="text-muted" style="font-size:.82rem">
                <i class="bi bi-at me-1"></i>{{ $user->username }}
                <span class="mx-1 text-muted opacity-50">&bull;</span>
                <i class="bi bi-envelope me-1"></i>{{ $user->email }}
            </div>
            @if(isset($user->userSetting) && $user->userSetting?->admin > 0)
            <span class="badge rounded-pill mt-2" style="background:var(--sb-accent);font-size:.7rem">
                <i class="bi bi-shield-check me-1"></i>Admin
            </span>
            @endif
        </div>
        <div class="text-muted d-none d-md-flex flex-column align-items-end" style="font-size:.75rem;gap:2px;flex-shrink:0">
            <div><i class="bi bi-calendar3 me-1"></i>Narys nuo</div>
            <div class="fw-semibold text-dark">{{ $user->created_at?->format('Y-m-d') ?? '—' }}</div>
        </div>
    </div>
</div>

{{-- Two-column forms --}}
<div class="row g-3 mb-3">

    {{-- Account details --}}
    <div class="col-lg-6">
        <div class="sb-card h-100">
            <div class="sb-card-title"><i class="bi bi-person me-1"></i>Paskyros informacija</div>

            @if($errors->any() && !$errors->hasBag('updatePassword'))
            <div class="alert alert-danger alert-dismissible py-2 mb-3" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $err)
                        <li style="font-size:.83rem">{{ $err }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form method="POST" action="{{ route('userProfile') }}">
                @csrf
                <input type="hidden" name="userID" value="{{ $user->id }}">

                <div class="form-floating mb-2">
                    <input id="fp_username"
                           class="form-control @error('username') is-invalid @enderror"
                           type="text" name="username" placeholder="username"
                           value="{{ old('username', $user->username) }}" required>
                    <label for="fp_username">Vartotojo vardas</label>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <div class="form-floating">
                            <input id="fp_name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   type="text" name="name" placeholder="Vardas"
                                   value="{{ old('name', $user->name) }}" required>
                            <label for="fp_name">Vardas</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating">
                            <input id="fp_surname"
                                   class="form-control @error('surname') is-invalid @enderror"
                                   type="text" name="surname" placeholder="Pavardė"
                                   value="{{ old('surname', $user->surname) }}" required>
                            <label for="fp_surname">Pavardė</label>
                        </div>
                    </div>
                </div>
                <div class="form-floating mb-3">
                    <input id="fp_email"
                           class="form-control @error('email') is-invalid @enderror"
                           type="email" name="email" placeholder="el@pastas.lt"
                           value="{{ old('email', $user->email) }}" required>
                    <label for="fp_email">El. paštas</label>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="bi bi-check2 me-1"></i>Išsaugoti
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Password change / set --}}
    <div class="col-lg-6">
        <div class="sb-card h-100">

            @if(is_null($user->password))
            {{-- Google user: no password yet — set one --}}
            <div class="sb-card-title"><i class="bi bi-key me-1"></i>Nustatyti slaptažodį</div>

            <p class="text-muted mb-3" style="font-size:.82rem">
                Jūs prisijungėte per Google. Nustatykite slaptažodį, kad galėtumėte prisijungti ir be Google.
            </p>

            @if($errors->hasBag('setPassword'))
            <div class="alert alert-danger alert-dismissible py-2 mb-3" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach($errors->getBag('setPassword')->all() as $err)
                        <li style="font-size:.83rem">{{ $err }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form method="POST" action="{{ route('password.set') }}"
                  x-data="{ n: false, r: false }">
                @csrf
                <div class="form-floating mb-2 position-relative">
                    <input id="pw_new"
                           :type="n ? 'text' : 'password'"
                           class="form-control pe-5"
                           name="password" placeholder=" "
                           autocomplete="new-password" required>
                    <label for="pw_new">Naujas slaptažodis</label>
                    <button type="button" class="profile-eye" @click="n = !n" tabindex="-1">
                        <i class="bi" :class="n ? 'bi-eye-slash' : 'bi-eye'"></i>
                    </button>
                </div>
                <div class="form-floating mb-3 position-relative">
                    <input id="pw_confirm"
                           :type="r ? 'text' : 'password'"
                           class="form-control pe-5"
                           name="password_confirmation" placeholder=" "
                           autocomplete="new-password" required>
                    <label for="pw_confirm">Pakartoti slaptažodį</label>
                    <button type="button" class="profile-eye" @click="r = !r" tabindex="-1">
                        <i class="bi" :class="r ? 'bi-eye-slash' : 'bi-eye'"></i>
                    </button>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="bi bi-key me-1"></i>Nustatyti slaptažodį
                    </button>
                </div>
            </form>

            @else
            {{-- Regular user: change existing password --}}
            <div class="sb-card-title"><i class="bi bi-shield-lock me-1"></i>Slaptažodžio keitimas</div>

            @if($errors->hasBag('updatePassword'))
            <div class="alert alert-danger alert-dismissible py-2 mb-3" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach($errors->getBag('updatePassword')->all() as $err)
                        <li style="font-size:.83rem">{{ $err }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}"
                  x-data="{ c: false, n: false, r: false }">
                @csrf
                @method('PUT')
                <div class="form-floating mb-2 position-relative">
                    <input id="pw_current"
                           :type="c ? 'text' : 'password'"
                           class="form-control pe-5"
                           name="current_password" placeholder=" "
                           autocomplete="current-password" required>
                    <label for="pw_current">Dabartinis slaptažodis</label>
                    <button type="button" class="profile-eye" @click="c = !c" tabindex="-1">
                        <i class="bi" :class="c ? 'bi-eye-slash' : 'bi-eye'"></i>
                    </button>
                </div>
                <div class="form-floating mb-2 position-relative">
                    <input id="pw_new"
                           :type="n ? 'text' : 'password'"
                           class="form-control pe-5"
                           name="password" placeholder=" "
                           autocomplete="new-password" required>
                    <label for="pw_new">Naujas slaptažodis</label>
                    <button type="button" class="profile-eye" @click="n = !n" tabindex="-1">
                        <i class="bi" :class="n ? 'bi-eye-slash' : 'bi-eye'"></i>
                    </button>
                </div>
                <div class="form-floating mb-3 position-relative">
                    <input id="pw_confirm"
                           :type="r ? 'text' : 'password'"
                           class="form-control pe-5"
                           name="password_confirmation" placeholder=" "
                           autocomplete="new-password" required>
                    <label for="pw_confirm">Pakartoti slaptažodį</label>
                    <button type="button" class="profile-eye" @click="r = !r" tabindex="-1">
                        <i class="bi" :class="r ? 'bi-eye-slash' : 'bi-eye'"></i>
                    </button>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-outline-secondary btn-sm px-4">
                        <i class="bi bi-lock me-1"></i>Keisti slaptažodį
                    </button>
                </div>
            </form>
            @endif

        </div>
    </div>

</div>

{{-- Notification preferences --}}
<div class="sb-card mb-3">
    <div class="sb-card-title"><i class="bi bi-bell me-1"></i>Pranešimai</div>
    <form method="POST" action="{{ route('profile.notifications') }}">
        @csrf
        <p class="text-muted mb-3" style="font-size:.82rem">
            Gauti priminimus el. paštu apie artėjančias rungtynes prieš pat žaidimo pradžią.
        </p>
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch"
                   id="receive_reminders" name="receive_reminders" value="1"
                   {{ $user->userSetting?->receive_reminders ? 'checked' : '' }}>
            <label class="form-check-label" for="receive_reminders">Įjungti priminimus</label>
        </div>
        <div class="text-end">
            <button type="submit" class="btn btn-primary btn-sm px-4">
                <i class="bi bi-check2 me-1"></i>Išsaugoti
            </button>
        </div>
    </form>
</div>

{{-- Danger zone --}}
<div class="sb-card" x-data="{ open: false }">
    <div class="d-flex align-items-center justify-content-between gap-2">
        <div>
            <div class="fw-semibold text-danger" style="font-size:.82rem">
                <i class="bi bi-exclamation-triangle me-1"></i>Pavojaus zona
            </div>
            <div class="text-muted mt-1" style="font-size:.78rem">Paskyros ištrynimas yra negrįžtamas veiksmas.</div>
        </div>
        <button class="btn btn-sm btn-outline-danger flex-shrink-0" @click="open = !open">
            <i class="bi me-1" :class="open ? 'bi-x' : 'bi-trash'"></i>
            <span x-text="open ? 'Atšaukti' : 'Ištrinti paskyrą'"></span>
        </button>
    </div>

    <div x-show="open" x-transition.duration.150ms class="mt-3 pt-3 border-top">
        @if($errors->hasBag('userDeletion'))
        <div class="alert alert-danger py-2 mb-3">
            <ul class="mb-0 ps-3">
                @foreach($errors->getBag('userDeletion')->all() as $err)
                    <li style="font-size:.83rem">{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <p class="text-muted mb-3" style="font-size:.83rem">
            Visi jūsų duomenys bus ištrinti. Šio veiksmo negalima atšaukti.<br>
            Norėdami patvirtinti, įveskite savo slaptažodį.
        </p>

        <form method="POST" action="{{ route('profile.destroy') }}"
              x-data="{ d: false }" style="max-width:360px">
            @csrf
            @method('DELETE')
            <div class="form-floating mb-3 position-relative">
                <input id="del_pw"
                       :type="d ? 'text' : 'password'"
                       class="form-control pe-5"
                       name="password" placeholder=" " required>
                <label for="del_pw">Slaptažodis</label>
                <button type="button" class="profile-eye" @click="d = !d" tabindex="-1">
                    <i class="bi" :class="d ? 'bi-eye-slash' : 'bi-eye'"></i>
                </button>
            </div>
            <button type="submit" class="btn btn-danger btn-sm px-4">
                <i class="bi bi-trash me-1"></i>Ištrinti paskyrą visam laikui
            </button>
        </form>
    </div>
</div>

@endsection
