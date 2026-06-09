@extends('layouts.master')
@section('content')

<div class="sb-auth-page">

    <div class="sb-auth-body">

        <h1 class="sb-auth-title">Prisijungti</h1>

        @if (session('status'))
            <div class="alert alert-success mb-4">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger mb-4">{{ $errors->first() }}</div>
        @endif

        {{-- Google --}}
        <a href="{{ route('auth.google') }}" class="sb-auth-google-btn">
            <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="20" height="20" alt="">
            Tęsti su Google
        </a>

        <div class="sb-auth-divider">arba prisijungti el. paštu</div>

        <form method="POST" action="{{ route('login') }}" x-data="{ showPwd: false }">
            @csrf

            <input type="email" name="email"
                   class="sb-auth-input @error('email') sb-auth-input-error @enderror"
                   placeholder="El. pašto adresas *"
                   value="{{ old('email') }}"
                   autocomplete="email" autofocus>

            <div class="sb-auth-input-wrap">
                <input :type="showPwd ? 'text' : 'password'" name="password"
                       class="sb-auth-input @error('password') sb-auth-input-error @enderror"
                       placeholder="Slaptažodis *"
                       autocomplete="current-password">
                <button type="button" class="sb-auth-eye" @click="showPwd = !showPwd" tabindex="-1">
                    <i class="bi" :class="showPwd ? 'bi-eye-slash' : 'bi-eye'"></i>
                </button>
            </div>

            <div class="text-end mb-3">
                <a href="{{ route('password.request') }}" class="sb-auth-link">Pamiršote slaptažodį?</a>
            </div>

            <button type="submit" class="sb-auth-btn-primary">Prisijungti</button>
        </form>

        <a href="{{ route('register') }}" class="sb-auth-bottom-link">
            Neturite paskyros? Sukurkite
        </a>

    </div>
</div>

@endsection
