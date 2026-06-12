<div class="p-4">

    @if ($errors->has('email') && !$errors->hasAny(['username','name','surname']))
    <div class="alert alert-danger py-2 mb-3 small">{{ $errors->first('email') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="bi bi-envelope"></i>
                </span>
                <input type="email" name="email"
                       class="form-control border-start-0 ps-1"
                       placeholder="El. paštas"
                       value="{{ old('email') }}"
                       autocomplete="email">
            </div>
        </div>

        <div class="mb-4">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="bi bi-lock"></i>
                </span>
                <input type="password" name="password"
                       class="form-control border-start-0 ps-1"
                       placeholder="Slaptažodis"
                       autocomplete="current-password">
            </div>
        </div>

        <button type="submit" class="btn w-100 fw-semibold"
                style="background:var(--sb-accent);color:#0f172a;">
            Prisijungti
        </button>
    </form>

    <div class="sb-auth-divider">arba</div>

    <a href="{{ route('auth.google') }}"
       class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="18" height="18"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
        Prisijungti su Google
    </a>

    <div class="text-center mt-3">
        <a href="{{ route('password.request') }}" class="text-muted small text-decoration-none">
            Pamiršau slaptažodį
        </a>
    </div>

</div>
