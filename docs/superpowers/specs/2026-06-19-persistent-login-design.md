# Persistent Login — "Prisiminti mane" Checkbox

**Date:** 2026-06-19
**Status:** Approved

## Problem

Users must re-authenticate with Google on every session (120-minute lifetime). There is no "remember me" option for either Google or email login.

## Goal

Add a single "Prisiminti mane" checkbox to the login page that persists the authenticated session across browser restarts for both Google OAuth and email login.

## What already works

- `users.remember_token` column exists — no migration needed.
- `LoginRequest::authenticate()` already calls `Auth::attempt($credentials, $this->boolean('remember'))` — email remember-me is wired, just missing the checkbox in the view.

## What is missing

1. **View** — no checkbox in `login.blade.php`.
2. **Google path** — `Auth::login($user)` ignores remember; no mechanism to carry user's checkbox choice across the OAuth redirect.

## Design

### UI

One "Prisiminti mane" checkbox placed between the Google button and the "arba prisijungti el. paštu" divider. It visually applies to both login methods. Both the Google button and the email form live inside a shared outer wrapper so the checkbox value is available to both.

The Google button becomes a submit button inside a small `<form method="POST" action="/auth/google/remember">` that carries the checkbox value. The email `<form>` already has `name="remember"` support — just add the checkbox input.

### New route

```
POST /auth/google/remember
```

Handler (can live in `GoogleAuthController::rememberAndRedirect()`):
1. Store `session(['remember_me' => $request->boolean('remember')])`.
2. Return redirect to `Socialite::driver('google')->redirect()`.

### GoogleAuthController::callback() changes

All three `Auth::login($user)` calls updated to:
```php
Auth::login($user, session('remember_me', false));
session()->forget('remember_me');
```

### Email login

No backend change. Add to `login.blade.php`:
```html
<input type="checkbox" name="remember" id="remember">
<label for="remember">Prisiminti mane</label>
```

## Files changed

| File | Change |
|---|---|
| `resources/views/auth/login.blade.php` | Add checkbox; wrap Google button in POST form |
| `app/Http/Controllers/Auth/GoogleAuthController.php` | Add `rememberAndRedirect()`; update all `Auth::login()` calls |
| `routes/auth.php` | Add `POST /auth/google/remember` route |

## Out of scope

- Session lifetime change (`SESSION_LIFETIME`) — Laravel's remember cookie lasts 5 years independently of session lifetime; no config change needed.
- Any other login method changes.
