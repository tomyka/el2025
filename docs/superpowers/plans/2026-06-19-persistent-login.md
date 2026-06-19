# Persistent Login — "Prisiminti mane" Checkbox Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a "Prisiminti mane" checkbox to the login page that persists the session across browser restarts for both Google OAuth and email login.

**Architecture:** A single Alpine.js component wraps both login forms and holds shared `remember` state. The Google button is wrapped in a small POST form that carries the remember flag to a new route, which stores it in session before redirecting to Google. The OAuth callback reads and clears that session value when calling `Auth::login()`. The email form already wires `remember` to `Auth::attempt()` — it just needs the view change.

**Tech Stack:** Laravel 11, Laravel Socialite, Alpine.js, Blade, PHPUnit/Pest (Feature tests)

---

## File Map

| File | Action | What changes |
|---|---|---|
| `tests/Feature/Auth/GoogleAuthTest.php` | Modify | Add 4 new tests for remember behaviour |
| `tests/Feature/Auth/AuthenticationTest.php` | Modify | Add 1 test for email remember |
| `app/Http/Controllers/Auth/GoogleAuthController.php` | Modify | Add `rememberAndRedirect()`; update 3 `Auth::login()` calls |
| `routes/auth.php` | Modify | Add `POST auth/google/remember` route |
| `resources/views/auth/login.blade.php` | Modify | Wrap in Alpine component, add checkbox, wrap Google button in POST form |

---

## Task 1: Route + `rememberAndRedirect()` method

**Files:**
- Modify: `tests/Feature/Auth/GoogleAuthTest.php`
- Modify: `app/Http/Controllers/Auth/GoogleAuthController.php`
- Modify: `routes/auth.php`

- [ ] **Step 1: Write failing tests** — add to `GoogleAuthTest.php` inside the class, after the existing tests:

```php
public function test_remember_and_redirect_stores_true_in_session(): void
{
    Socialite::shouldReceive('driver->redirect')->andReturn(
        redirect('https://accounts.google.com/o/oauth2/auth')
    );

    $response = $this->post('/auth/google/remember', ['remember' => '1']);

    $response->assertSessionHas('remember_me', true);
    $response->assertRedirect();
}

public function test_remember_and_redirect_stores_false_when_unchecked(): void
{
    Socialite::shouldReceive('driver->redirect')->andReturn(
        redirect('https://accounts.google.com/o/oauth2/auth')
    );

    $response = $this->post('/auth/google/remember');

    $response->assertSessionHas('remember_me', false);
    $response->assertRedirect();
}
```

- [ ] **Step 2: Run tests to verify they fail**

```
php artisan test tests/Feature/Auth/GoogleAuthTest.php --filter "remember_and_redirect"
```

Expected: 2 failures — `POST /auth/google/remember` returns 404 (route doesn't exist yet).

- [ ] **Step 3: Add the route** — in `routes/auth.php`, inside the `Route::middleware('guest')->group(...)` block, after the existing Google routes:

```php
Route::post('auth/google/remember', [GoogleAuthController::class, 'rememberAndRedirect'])->name('auth.google.remember');
```

- [ ] **Step 4: Add `rememberAndRedirect()` to `GoogleAuthController`** — add this method after the existing `redirect()` method:

```php
public function rememberAndRedirect(Request $request)
{
    session(['remember_me' => $request->boolean('remember')]);
    return Socialite::driver('google')->redirect();
}
```

- [ ] **Step 5: Run tests to verify they pass**

```
php artisan test tests/Feature/Auth/GoogleAuthTest.php --filter "remember_and_redirect"
```

Expected: 2 tests pass.

- [ ] **Step 6: Run the full test suite to check for regressions**

```
php artisan test tests/Feature/Auth/GoogleAuthTest.php
```

Expected: All existing tests still pass.

- [ ] **Step 7: Commit**

```
git add tests/Feature/Auth/GoogleAuthTest.php app/Http/Controllers/Auth/GoogleAuthController.php routes/auth.php
git commit -m "feat: add POST /auth/google/remember route to carry remember flag into OAuth"
```

---

## Task 2: Update `callback()` to honour the session remember flag

**Files:**
- Modify: `tests/Feature/Auth/GoogleAuthTest.php`
- Modify: `app/Http/Controllers/Auth/GoogleAuthController.php`

- [ ] **Step 1: Write failing tests** — add to `GoogleAuthTest.php`:

```php
public function test_callback_with_remember_flag_sets_remember_token(): void
{
    $user = User::factory()->create(['google_id' => 'gid-rem', 'remember_token' => null]);
    $this->fakeSocialiteUser('gid-rem', $user->email, 'Test User');

    $this->withSession(['remember_me' => true])
         ->get('/auth/google/callback');

    $this->assertNotNull($user->fresh()->remember_token);
}

public function test_callback_clears_remember_me_from_session(): void
{
    $user = User::factory()->create(['google_id' => 'gid-clr']);
    $this->fakeSocialiteUser('gid-clr', $user->email, 'Test User');

    $response = $this->withSession(['remember_me' => true])
                     ->get('/auth/google/callback');

    $response->assertSessionMissing('remember_me');
}

public function test_callback_without_remember_flag_does_not_set_remember_token(): void
{
    $user = User::factory()->create(['google_id' => 'gid-norem', 'remember_token' => null]);
    $this->fakeSocialiteUser('gid-norem', $user->email, 'Test User');

    $this->get('/auth/google/callback');

    $this->assertNull($user->fresh()->remember_token);
}
```

- [ ] **Step 2: Run tests to verify they fail**

```
php artisan test tests/Feature/Auth/GoogleAuthTest.php --filter "callback_with_remember|callback_clears|callback_without_remember"
```

Expected: The `remember_token` and session tests fail because `callback()` still calls `Auth::login($user)` without the flag.

- [ ] **Step 3: Update all three `Auth::login()` calls in `callback()`**

In `GoogleAuthController::callback()`, replace every occurrence of:
```php
Auth::login($user);
```
with:
```php
Auth::login($user, session('remember_me', false));
session()->forget('remember_me');
```

There are three such calls — one per login branch (existing google_id match, email match, and new user registration). All three get the same replacement.

The full updated `callback()` method:

```php
public function callback(Request $request)
{
    try {
        $googleUser = Socialite::driver('google')->user();
    } catch (\Exception $e) {
        return redirect()->route('/')->with('error', 'Google prisijungimas nepavyko. Bandykite dar kartą.');
    }

    $user = User::where('google_id', $googleUser->getId())->first();

    if ($user) {
        Auth::login($user, session('remember_me', false));
        session()->forget('remember_me');
        (new AuditLoginsController())->insertAuditLogin($user->id, $request->ip(), 'google');
        return redirect()->route('main');
    }

    $user = User::where('email', $googleUser->getEmail())->first();

    if ($user) {
        $user->update(['google_id' => $googleUser->getId()]);
        Auth::login($user, session('remember_me', false));
        session()->forget('remember_me');
        (new AuditLoginsController())->insertAuditLogin($user->id, $request->ip(), 'google');
        return redirect()->route('main');
    }

    if (!$this->registrationIsOpen()) {
        return redirect()->route('main');
    }

    $nameParts = explode(' ', $googleUser->getName(), 2);
    $user = User::create([
        'google_id'         => $googleUser->getId(),
        'email'             => $googleUser->getEmail(),
        'email_verified_at' => now(),
        'username'          => strstr($googleUser->getEmail(), '@', true),
        'name'              => $nameParts[0],
        'surname'           => $nameParts[1] ?? '',
        'password'          => null,
    ]);

    event(new Registered($user));
    (new PostRegisterController())->postRegisterActions($user->id);

    Auth::login($user, session('remember_me', false));
    session()->forget('remember_me');
    (new AuditLoginsController())->insertAuditLogin($user->id, $request->ip(), 'google');
    return redirect()->route('main');
}
```

- [ ] **Step 4: Run new tests to verify they pass**

```
php artisan test tests/Feature/Auth/GoogleAuthTest.php --filter "callback_with_remember|callback_clears|callback_without_remember"
```

Expected: 3 tests pass.

- [ ] **Step 5: Run the full Google auth test suite**

```
php artisan test tests/Feature/Auth/GoogleAuthTest.php
```

Expected: All tests pass (existing + new).

- [ ] **Step 6: Commit**

```
git add tests/Feature/Auth/GoogleAuthTest.php app/Http/Controllers/Auth/GoogleAuthController.php
git commit -m "feat: honour remember_me session flag in Google OAuth callback"
```

---

## Task 3: Add email remember test

**Files:**
- Modify: `tests/Feature/Auth/AuthenticationTest.php`

- [ ] **Step 1: Write the failing test** — add to `AuthenticationTest.php`:

```php
public function test_users_can_authenticate_with_remember_me(): void
{
    $user = User::factory()->create(['remember_token' => null]);

    $this->post('/login', [
        'email'    => $user->email,
        'password' => 'password',
        'remember' => '1',
    ]);

    $this->assertAuthenticated();
    $this->assertNotNull($user->fresh()->remember_token);
}
```

- [ ] **Step 2: Run the test to verify it fails**

```
php artisan test tests/Feature/Auth/AuthenticationTest.php --filter "remember_me"
```

Expected: FAIL — `remember_token` is null because the view hasn't been updated yet (but the backend already supports it). Actually this test may already pass since `LoginRequest` wires `remember` from the POST body — run it to confirm.

If it passes: the backend already works, the view is the only missing piece. Continue to Task 4.

If it fails: check that `User::factory()->create()` sets `password = 'password'` (the default factory does this via `Hash::make('password')`). Confirm and rerun.

- [ ] **Step 3: Run the full authentication test suite**

```
php artisan test tests/Feature/Auth/AuthenticationTest.php
```

Expected: All tests pass.

- [ ] **Step 4: Commit**

```
git add tests/Feature/Auth/AuthenticationTest.php
git commit -m "test: add remember me coverage for email login"
```

---

## Task 4: Update the login view

**Files:**
- Modify: `resources/views/auth/login.blade.php`

No automated test for the view — verify manually after.

- [ ] **Step 1: Replace the entire content of `login.blade.php`** with the following. Key changes: outer `x-data` component holds `remember` and `showPwd` state; Google button moves into a POST form; hidden inputs carry `remember` to each form; checkbox sits between the Google form and the divider.

```blade
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

        <a href="{{ route('register') }}" class="sb-auth-bottom-link">
            Neturite paskyros? Sukurkite
        </a>

    </div>
</div>

@endsection
```

- [ ] **Step 2: Add the `.sb-auth-remember` style** — find where the `.sb-auth-*` styles are defined (likely `resources/css/app.css` or a dedicated auth stylesheet) and add:

```css
.sb-auth-remember {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0.75rem 0;
    font-size: 0.875rem;
    color: inherit;
    cursor: pointer;
}

.sb-auth-remember input[type="checkbox"] {
    cursor: pointer;
}
```

- [ ] **Step 3: Rebuild assets**

```
npm run build
```

Expected: Build completes without errors.

- [ ] **Step 4: Manual smoke test**

Open `/login` in a browser:
1. Check the "Prisiminti mane" checkbox.
2. Click "Tęsti su Google" → completes OAuth → redirected to main page.
3. Close the browser entirely and reopen → navigate to the app → should still be logged in (no login prompt).
4. Repeat with unchecked checkbox — after browser close, should be logged out.
5. Repeat steps with email login.

- [ ] **Step 5: Commit**

```
git add resources/views/auth/login.blade.php resources/css/
git commit -m "feat: add Prisiminti mane checkbox to login page for persistent sessions"
```
