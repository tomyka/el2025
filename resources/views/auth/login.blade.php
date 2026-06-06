@extends('layouts.master')
@section('content')

<div class="d-flex justify-content-center pt-2">
    <div class="card border-0 shadow" style="width:100%; max-width:420px; border-radius:12px;">
        <div class="card-body p-4">

            <h5 class="fw-bold mb-1">Prisijungti</h5>
            <p class="text-muted small mb-4">Džiaugiamės tave matydami vėl!</p>

            @if (session('status'))
            <div class="alert alert-success py-2 mb-3 small">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
            <div class="alert alert-danger py-2 mb-3 small">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-floating mb-3">
                    <input type="email" name="email" id="loginEmail"
                           class="form-control"
                           placeholder="el@pastas.lt"
                           value="{{ old('email') }}"
                           autocomplete="email" autofocus>
                    <label for="loginEmail">El. paštas</label>
                </div>

                <div class="form-floating mb-4">
                    <input type="password" name="password" id="loginPassword"
                           class="form-control"
                           placeholder="Slaptažodis"
                           autocomplete="current-password">
                    <label for="loginPassword">Slaptažodis</label>
                </div>

                <button type="submit" class="btn w-100 fw-semibold mb-2"
                        style="background:var(--sb-accent); color:#0f172a;">
                    Prisijungti
                </button>
            </form>

            <div class="sb-auth-divider">arba</div>

            <a href="{{ route('auth.google') }}"
               class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2">
                <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="18" height="18" alt="">
                Prisijungti su Google
            </a>

            <div class="d-flex justify-content-between mt-3">
                <a href="{{ route('password.request') }}" class="text-muted small text-decoration-none">
                    Pamiršau slaptažodį
                </a>
                <a href="{{ route('register') }}" class="text-muted small text-decoration-none">
                    Registruotis →
                </a>
            </div>

        </div>
    </div>
</div>

@endsection
