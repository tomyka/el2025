@extends('layouts.master')
@section('content')

@php
    $nextUnscoredGame = \App\Models\Game::whereNull('home_team_score')
        ->whereNull('away_team_score')
        ->min('game_date');
    $registrationOpen = is_null($nextUnscoredGame) || now()->lt($nextUnscoredGame);
@endphp

<div class="sb-auth-page">

    {{-- Logo --}}
    <a href="{{ route('/') }}" class="sb-auth-logo">
        <img src="{{ asset('img/favicon-512.png') }}" alt="SportBet" style="height:1.6rem;">
        <span>Sport<span class="sb-brand-dot">Bet</span></span>
    </a>

    <div class="sb-auth-body">

        <h1 class="sb-auth-title">Registruotis</h1>

        @if (!$registrationOpen)
            <div class="alert alert-warning text-center">Registracija šiuo metu uždaryta.</div>
        @else

        @if ($errors->any())
            <div class="alert alert-danger mb-4">{{ $errors->first() }}</div>
        @endif

        {{-- Google --}}
        <a href="{{ route('auth.google') }}" class="sb-auth-google-btn">
            <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="20" height="20" alt="">
            Registruotis su Google
        </a>

        <div class="sb-auth-divider">arba registruotis el. paštu</div>

        <form method="POST" action="{{ route('register') }}" x-data="{ showPwd: false, showPwd2: false }">
            @csrf

            <input type="text" name="username"
                   class="sb-auth-input @error('username') sb-auth-input-error @enderror"
                   placeholder="Vartotojo vardas *"
                   value="{{ old('username') }}"
                   required autofocus>

            <div class="d-flex gap-2">
                <input type="text" name="name"
                       class="sb-auth-input @error('name') sb-auth-input-error @enderror"
                       placeholder="Vardas *"
                       value="{{ old('name') }}"
                       required>
                <input type="text" name="surname"
                       class="sb-auth-input @error('surname') sb-auth-input-error @enderror"
                       placeholder="Pavardė"
                       value="{{ old('surname') }}">
            </div>

            <input type="email" name="email"
                   class="sb-auth-input @error('email') sb-auth-input-error @enderror"
                   placeholder="El. pašto adresas *"
                   value="{{ old('email') }}"
                   required>

            <div class="sb-auth-input-wrap mb-3">
                <input :type="showPwd ? 'text' : 'password'" name="password"
                       class="sb-auth-input @error('password') sb-auth-input-error @enderror"
                       placeholder="Slaptažodis *"
                       required>
                <button type="button" class="sb-auth-eye" @click="showPwd = !showPwd" tabindex="-1">
                    <i class="bi" :class="showPwd ? 'bi-eye-slash' : 'bi-eye'"></i>
                </button>
            </div>

            <div class="sb-auth-input-wrap mb-3">
                <input :type="showPwd2 ? 'text' : 'password'" name="password_confirmation"
                       class="sb-auth-input"
                       placeholder="Pakartokite slaptažodį *"
                       required>
                <button type="button" class="sb-auth-eye" @click="showPwd2 = !showPwd2" tabindex="-1">
                    <i class="bi" :class="showPwd2 ? 'bi-eye-slash' : 'bi-eye'"></i>
                </button>
            </div>

            <button type="submit" class="sb-auth-btn-primary">Sukurti paskyrą</button>
        </form>

        @endif

        <a href="{{ route('login') }}" class="sb-auth-bottom-link">
            Jau turite paskyrą? Prisijungti
        </a>

    </div>
</div>

@endsection
