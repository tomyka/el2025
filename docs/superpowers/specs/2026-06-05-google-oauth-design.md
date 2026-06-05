# Google OAuth Login — Design Spec
Date: 2026-06-05

## Summary

Add Google OAuth login alongside the existing email/password auth. Users can sign in with Google; existing email/password accounts are silently auto-linked if the email matches. Email/password registration and login remain unchanged.

---

## 1. Decisions

| Question | Decision |
|---|---|
| Email conflict (existing account + Google same email) | Auto-link silently — attach `google_id` to existing user, log in |
| Missing username/surname for Google users | Derive automatically — username from email prefix, name/surname from Google display name split |
| Provider storage | `google_id` nullable column on `users` — no separate table |
| Package | `laravel/socialite` |

---

## 2. Data Model

### Migration: modify `users` table

- Add `google_id` — `string`, nullable, unique. Used to look up returning Google users.
- Modify `password` — change to nullable. Google-only users have no password.

No new tables.

---

## 3. Files Changed

| File | Change |
|---|---|
| `database/migrations/YYYY_MM_DD_add_google_oauth_to_users.php` | New migration: add `google_id`, make `password` nullable |
| `app/Models/User.php` | Add `google_id` to `$fillable`; remove `password` from required validation assumption |
| `app/Http/Controllers/Auth/GoogleAuthController.php` | New controller: `redirect()` and `callback()` |
| `routes/auth.php` | Add two Google routes inside `guest` middleware group |
| `resources/views/modals/login.blade.php` | Add Google button below existing form |
| `config/services.php` | Add `google` entry |
| `.env.example` | Add `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` |

---

## 4. Auth Flow

### Returning Google user
1. User clicks "Prisijungti su Google" → `GET /auth/google`
2. Redirected to Google consent screen
3. Google calls back to `GET /auth/google/callback`
4. Controller finds user by `google_id` → `Auth::login($user)` → redirect `main`

### Existing email/password user (auto-link)
1–3. Same as above
4. No `google_id` match; email match found → `$user->update(['google_id' => $googleUser->getId()])` → `Auth::login($user)` → redirect `main`

### New user (no existing account)
1–3. Same as above
4. No match by `google_id` or email → create `User`:
   - `google_id` = Google ID
   - `email` = Google email
   - `username` = email prefix before `@` (e.g. `tomas.k@gmail.com` → `tomas.k`)
   - `name` = first word of Google display name
   - `surname` = remainder of display name (empty string if single-word name)
   - `password` = null
5. Call `PostRegisterController::postRegisterActions($user->id)` — creates user_settings, group membership, prediction rows
6. `Auth::login($user)` → redirect `main`

---

## 5. Controller

**`app/Http/Controllers/Auth/GoogleAuthController.php`**

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PostRegisterController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            Auth::login($user);
            return redirect()->route('main');
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            $user->update(['google_id' => $googleUser->getId()]);
            Auth::login($user);
            return redirect()->route('main');
        }

        $nameParts = explode(' ', $googleUser->getName(), 2);
        $user = User::create([
            'google_id' => $googleUser->getId(),
            'email'     => $googleUser->getEmail(),
            'username'  => strstr($googleUser->getEmail(), '@', true),
            'name'      => $nameParts[0],
            'surname'   => $nameParts[1] ?? '',
            'password'  => null,
        ]);

        (new PostRegisterController())->postRegisterActions($user->id);

        Auth::login($user);
        return redirect()->route('main');
    }
}
```

---

## 6. Routes

Added to `routes/auth.php` inside the `guest` middleware group:

```php
Route::get('auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
```

---

## 7. UI

**`resources/views/modals/login.blade.php`** — add below the existing form:

```html
<div class="modal-body pt-0 text-center">
    <hr class="my-2">
    <a href="{{ route('auth.google') }}" class="btn btn-outline-secondary w-100">
        <img src="https://www.svgrepo.com/show/475656/google-color.svg"
             width="18" height="18" class="me-2" alt="Google">
        Prisijungti su Google
    </a>
</div>
```

The registration tab gets no Google button — the deadline guard stays on email registration only. Google login/register always works regardless of deadline (it's a single flow).

---

## 8. Config

**`config/services.php`** — add:
```php
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect'      => env('GOOGLE_REDIRECT_URI'),
],
```

**`.env.example`** — add:
```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost/auth/google/callback
```

**Google Cloud Console:** Create OAuth 2.0 credentials (Web application type). Add authorized redirect URI: `https://your-domain/auth/google/callback`.

---

## 9. Out of Scope

- Additional OAuth providers (GitHub, Facebook, etc.)
- Account unlinking (removing Google from a linked account)
- Avatar/profile picture sync from Google
- Email verification for Google users (Google already verifies email)
- Admin panel changes
