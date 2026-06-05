<x-guest-layout>

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

        <div class="mb-3">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="bi bi-envelope"></i>
                </span>
                <input type="email" name="email"
                       class="form-control border-start-0 ps-1"
                       placeholder="El. paštas"
                       value="{{ old('email') }}"
                       autocomplete="email"
                       autofocus>
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

</x-guest-layout>
