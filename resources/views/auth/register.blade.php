@extends('layouts.master')
@section('content')

@php
    $nextUnscoredGame = \App\Models\Game::whereNull('home_team_score')
        ->whereNull('away_team_score')
        ->min('game_date');
    $registrationOpen = is_null($nextUnscoredGame) || now()->lt($nextUnscoredGame);
@endphp

<div class="d-flex justify-content-center pt-2">
    <div class="card border-0 shadow" style="width:100%; max-width:420px; border-radius:12px;">
        <div class="card-body p-4">

            <h5 class="fw-bold mb-1">Registruotis</h5>
            <p class="text-muted small mb-4">Sukurk paskyrą ir pradėk spėjimus!</p>

            @if (!$registrationOpen)
            <div class="alert alert-warning py-2 small">
                Registracija šiuo metu uždaryta.
            </div>
            @else

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="form-floating mb-3">
                    <input type="text" name="username" id="regUsername"
                           class="form-control @error('username') is-invalid @enderror"
                           placeholder="Slapyvardis"
                           value="{{ old('username') }}"
                           required autofocus>
                    <label for="regUsername">Slapyvardis</label>
                    @error('username')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-2 mb-3">
                    <div class="col">
                        <div class="form-floating">
                            <input type="text" name="name" id="regName"
                                   class="form-control @error('name') is-invalid @enderror"
                                   placeholder="Vardas"
                                   value="{{ old('name') }}"
                                   required>
                            <label for="regName">Vardas</label>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-floating">
                            <input type="text" name="surname" id="regSurname"
                                   class="form-control @error('surname') is-invalid @enderror"
                                   placeholder="Pavardė"
                                   value="{{ old('surname') }}">
                            <label for="regSurname">Pavardė</label>
                            @error('surname')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-floating mb-3">
                    <input type="email" name="email" id="regEmail"
                           class="form-control @error('email') is-invalid @enderror"
                           placeholder="el@pastas.lt"
                           value="{{ old('email') }}"
                           required>
                    <label for="regEmail">El. paštas</label>
                    @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-floating mb-3">
                    <input type="password" name="password" id="regPassword"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Slaptažodis"
                           required>
                    <label for="regPassword">Slaptažodis</label>
                    @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-floating mb-4">
                    <input type="password" name="password_confirmation" id="regPasswordConfirm"
                           class="form-control"
                           placeholder="Pakartokite slaptažodį"
                           required>
                    <label for="regPasswordConfirm">Pakartokite slaptažodį</label>
                </div>

                <button type="submit" class="btn w-100 fw-semibold"
                        style="background:var(--sb-accent); color:#0f172a;">
                    Registruotis
                </button>
            </form>

            @endif

            <div class="text-center mt-3">
                <a href="{{ route('login') }}" class="text-muted small text-decoration-none">
                    ← Jau turiu paskyrą
                </a>
            </div>

        </div>
    </div>
</div>

@endsection
