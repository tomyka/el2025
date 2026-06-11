<div class="p-4">

    <form method="POST" action="{{ route('register') }}">
        @csrf

        {{-- Honeypot: hidden from real users, bots fill it and get silently rejected --}}
        <div style="position:absolute;left:-9999px;opacity:0;height:0;width:0;overflow:hidden" aria-hidden="true">
            <label for="website">Leave this blank</label>
            <input type="text" name="website" id="website" tabindex="-1" autocomplete="off" value="">
        </div>

        <div class="mb-3">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="bi bi-person"></i>
                </span>
                <input type="text" name="username"
                       class="form-control border-start-0 ps-1 @error('username') is-invalid @enderror"
                       placeholder="Slapyvardis"
                       value="{{ old('username') }}"
                       required autofocus>
                @error('username')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row g-2 mb-3">
            <div class="col">
                <input type="text" name="name"
                       class="form-control @error('name') is-invalid @enderror"
                       placeholder="Vardas"
                       value="{{ old('name') }}"
                       required>
                @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col">
                <input type="text" name="surname"
                       class="form-control @error('surname') is-invalid @enderror"
                       placeholder="Pavardė"
                       value="{{ old('surname') }}">
                @error('surname')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="bi bi-envelope"></i>
                </span>
                <input type="email" name="email"
                       class="form-control border-start-0 ps-1 @error('email') is-invalid @enderror"
                       placeholder="El. paštas"
                       value="{{ old('email') }}"
                       required>
                @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="bi bi-lock"></i>
                </span>
                <input type="password" name="password"
                       class="form-control border-start-0 ps-1 @error('password') is-invalid @enderror"
                       placeholder="Slaptažodis"
                       required>
                @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-4">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="bi bi-lock-fill"></i>
                </span>
                <input type="password" name="password_confirmation"
                       class="form-control border-start-0 ps-1"
                       placeholder="Pakartokite slaptažodį"
                       required>
            </div>
        </div>

        <button type="submit" class="btn w-100 fw-semibold"
                style="background:var(--sb-accent);color:#0f172a;">
            Registruotis
        </button>
    </form>

</div>
