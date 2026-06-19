@extends('layouts.master')
@section('content')

<div class="sb-auth-page">

    <div class="sb-auth-body" x-data="{ remember: false, showPwd: false }">

        <h1 class="sb-auth-title">Prisijungti</h1>

        @if (session('status'))
            <div class="alert alert-success mb-4">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger mb-4">{{ $errors->first() }}</div>
        @endif

        {{-- Google --}}
        <form method="POST" action="{{ route('auth.google.remember') }}">
            @csrf
            <input type="hidden" name="remember" :value="remember ? '1' : '0'">
            <button type="submit" class="sb-auth-google-btn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="20" height="20"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
                Tęsti su Google
            </button>
        </form>

        {{-- Remember me checkbox — applies to both login methods --}}
        <div class="sb-auth-remember">
            <input type="checkbox" id="remember" x-model="remember">
            <label for="remember">Prisiminti mane</label>
        </div>

        <div class="sb-auth-divider">arba prisijungti el. paštu</div>

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <input type="hidden" name="remember" :value="remember ? '1' : '0'">

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

        @php $firstGame = \App\Models\Game::min('game_date'); @endphp
        @if (is_null($firstGame) || now()->lt($firstGame))
        <a href="{{ route('register') }}" class="sb-auth-bottom-link">
            Neturite paskyros? Sukurkite
        </a>
        @endif

    </div>
</div>

@endsection
