@extends('layouts.master')
@section('content')

@php
    $nextUnscoredGame = \App\Models\Game::whereNull('home_team_score')
        ->whereNull('away_team_score')
        ->min('game_date');
    $registrationOpen = is_null($nextUnscoredGame) || now()->lt($nextUnscoredGame);
@endphp

<div class="sb-auth-page">

    <div class="sb-auth-body">

        <h1 class="sb-auth-title">{{ __('Registruotis') }}</h1>

        @if (!$registrationOpen)
            <div class="alert alert-warning text-center">{{ __('Registracija šiuo metu uždaryta.') }}</div>
        @else

        @if ($errors->any())
            <div class="alert alert-danger mb-4">{{ $errors->first() }}</div>
        @endif

        {{-- Google --}}
        <a href="{{ route('auth.google') }}" class="sb-auth-google-btn">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="20" height="20"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
            {{ __('Registruotis su Google') }}
        </a>

        <div class="sb-auth-divider">{{ __('arba registruotis el. paštu') }}</div>

        <form method="POST" action="{{ route('register') }}" x-data="{ showPwd: false, showPwd2: false }">
            @csrf

            {{-- Honeypot: hidden from real users, bots fill it and get silently rejected --}}
            <div style="position:absolute;left:-9999px;opacity:0;height:0;width:0;overflow:hidden" aria-hidden="true">
                <label for="website">Leave this blank</label>
                <input type="text" name="website" id="website" tabindex="-1" autocomplete="off" value="">
            </div>

            <input type="text" name="username"
                   class="sb-auth-input @error('username') sb-auth-input-error @enderror"
                   placeholder="{{ __('Vartotojo vardas') }}"
                   value="{{ old('username') }}"
                   required autofocus>

            <div class="d-flex gap-2">
                <input type="text" name="name"
                       class="sb-auth-input @error('name') sb-auth-input-error @enderror"
                       placeholder="{{ __('Vardas') }}"
                       value="{{ old('name') }}"
                       required>
                <input type="text" name="surname"
                       class="sb-auth-input @error('surname') sb-auth-input-error @enderror"
                       placeholder="{{ __('Pavardė') }}"
                       value="{{ old('surname') }}">
            </div>

            <input type="email" name="email"
                   class="sb-auth-input @error('email') sb-auth-input-error @enderror"
                   placeholder="{{ __('El. pašto adresas') }}"
                   value="{{ old('email') }}"
                   required>

            <div class="sb-auth-input-wrap mb-3">
                <input :type="showPwd ? 'text' : 'password'" name="password"
                       class="sb-auth-input @error('password') sb-auth-input-error @enderror"
                       placeholder="{{ __('Slaptažodis') }}"
                       required>
                <button type="button" class="sb-auth-eye" @click="showPwd = !showPwd" tabindex="-1">
                    <i class="bi" :class="showPwd ? 'bi-eye-slash' : 'bi-eye'"></i>
                </button>
            </div>

            <div class="sb-auth-input-wrap mb-3">
                <input :type="showPwd2 ? 'text' : 'password'" name="password_confirmation"
                       class="sb-auth-input"
                       placeholder="{{ __('Pakartokite slaptažodį') }}"
                       required>
                <button type="button" class="sb-auth-eye" @click="showPwd2 = !showPwd2" tabindex="-1">
                    <i class="bi" :class="showPwd2 ? 'bi-eye-slash' : 'bi-eye'"></i>
                </button>
            </div>

            <button type="submit" class="sb-auth-btn-primary">{{ __('Sukurti paskyrą') }}</button>
        </form>

        @endif

        <a href="{{ route('login') }}" class="sb-auth-bottom-link">
            {{ __('Jau turite paskyrą? Prisijungti') }}
        </a>

    </div>
</div>

@endsection
