# Honeypot Bot Protection Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a honeypot hidden field to the registration form so naive bots are silently rejected without any friction for real users.

**Architecture:** Two-part change — a hidden `<input name="website">` added to the Blade form (off-screen via CSS, not `display:none`), and a single guard at the top of `RegisteredUserController::store()` that silently redirects if the field is non-empty. No external dependencies, no JS required.

**Tech Stack:** Laravel 11, PHP 8.2+, Blade, PHPUnit, SQLite (tests)

---

## File Map

| File | Change |
|---|---|
| `resources/views/auth/register.blade.php` | Add hidden honeypot input inside the email registration `<form>` |
| `app/Http/Controllers/Auth/RegisteredUserController.php` | Add honeypot guard at top of `store()` before validation |
| `tests/Feature/HoneypotTest.php` | New — two tests: filled honeypot rejected, empty honeypot passes |

---

## Task 1: Honeypot backend guard + tests

**Files:**
- Modify: `app/Http/Controllers/Auth/RegisteredUserController.php:29-59`
- Create: `tests/Feature/HoneypotTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/HoneypotTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class HoneypotTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        RateLimiter::clear('register');
        parent::tearDown();
    }

    private function makeOpenGame(): void
    {
        $home = Team::create(['team' => 'Home']);
        $away = Team::create(['team' => 'Away']);
        Game::create([
            'home_team_id'    => $home->id,
            'away_team_id'    => $away->id,
            'game_date'       => now()->addDay(),
            'home_team_score' => null,
            'away_team_score' => null,
        ]);
    }

    public function test_honeypot_filled_silently_rejects_registration(): void
    {
        $this->makeOpenGame();

        $this->post(route('register'), [
            'username'              => 'botuser',
            'name'                  => 'Bot',
            'email'                 => 'bot@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'website'               => 'http://spam.example.com',
        ])->assertRedirect();

        $this->assertDatabaseMissing('users', ['email' => 'bot@test.com']);
    }

    public function test_honeypot_empty_allows_registration(): void
    {
        $this->makeOpenGame();

        $this->post(route('register'), [
            'username'              => 'realuser',
            'name'                  => 'Real',
            'email'                 => 'real@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'website'               => '',
        ])->assertRedirect(route('main'));

        $this->assertDatabaseHas('users', ['email' => 'real@test.com']);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test tests/Feature/HoneypotTest.php
```

Expected: `test_honeypot_filled_silently_rejects_registration` FAILS — user IS created (no guard yet). `test_honeypot_empty_allows_registration` may pass already.

- [ ] **Step 3: Add the honeypot guard to RegisteredUserController**

In `app/Http/Controllers/Auth/RegisteredUserController.php`, add the honeypot check inside `store()`, immediately after the registration deadline check:

```php
public function store(Request $request): RedirectResponse
{
    if (!$this->registrationIsOpen()) {
        return redirect()->route('main');
    }

    // Honeypot: real users never fill this field; bots typically do
    if ($request->filled('website')) {
        return redirect()->route('main');
    }

    $request->validate([
        'username' => ['required', 'string', 'max:255'],
        'name'     => ['required', 'string', 'max:255'],
        'surname'  => ['nullable', 'string', 'max:255'],
        'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
        'password' => ['required', 'confirmed', Rules\Password::defaults()],
    ]);

    $user = User::create([
        'username' => $request->username,
        'name'     => $request->name,
        'surname'  => $request->surname ?? '',
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    event(new Registered($user));

    Auth::login($user);

    $postRegisterController = new PostRegisterController();
    $postRegisterController->postRegisterActions($user->id);

    return redirect(route('main', absolute: false));
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
php artisan test tests/Feature/HoneypotTest.php
```

Expected: both tests pass.

- [ ] **Step 5: Run full suite to check for regressions**

```bash
php artisan test
```

Expected: all tests pass (previously passing count + 2 new).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Auth/RegisteredUserController.php tests/Feature/HoneypotTest.php
git commit -m "fix: silently reject registration when honeypot field is filled"
```

---

## Task 2: Add honeypot field to registration form

**Files:**
- Modify: `resources/views/auth/register.blade.php` — inside the `<form>` element

The form currently starts at line 33 with `<form method="POST" ...>`. Add the honeypot `<div>` immediately after the opening `<form>` tag, before the first real input.

- [ ] **Step 1: Add hidden honeypot input to register.blade.php**

In `resources/views/auth/register.blade.php`, add after the `@csrf` line (line 34), before the username input:

```blade
{{-- Honeypot: hidden from real users, bots fill it and get silently rejected --}}
<div style="position:absolute;left:-9999px;opacity:0;height:0;width:0;overflow:hidden" aria-hidden="true">
    <label for="website">Leave this blank</label>
    <input type="text" name="website" id="website" tabindex="-1" autocomplete="off" value="">
</div>
```

The full form opening section should look like:

```blade
<form method="POST" action="{{ route('register') }}" x-data="{ showPwd: false, showPwd2: false }">
    @csrf

    {{-- Honeypot: hidden from real users, bots fill it and get silently rejected --}}
    <div style="position:absolute;left:-9999px;opacity:0;height:0;width:0;overflow:hidden" aria-hidden="true">
        <label for="website">Leave this blank</label>
        <input type="text" name="website" id="website" tabindex="-1" autocomplete="off" value="">
    </div>

    <input type="text" name="username"
           class="sb-auth-input @error('username') sb-auth-input-error @enderror"
```

**Why this hiding approach:**
- `position:absolute; left:-9999px` — off-screen, not `display:none` (bots skip `display:none`)
- `opacity:0; height:0; width:0; overflow:hidden` — belt-and-suspenders visual hiding
- `aria-hidden="true"` — screen readers skip the whole `<div>`
- `tabindex="-1"` — keyboard navigation skips the input
- `autocomplete="off"` — browser autofill does not populate it
- `value=""` — explicit empty default, prevents browser from restoring a cached value

- [ ] **Step 2: Run full test suite to verify no regressions**

```bash
php artisan test
```

Expected: all tests pass. The view change has no PHP logic — no new tests needed for the HTML.

- [ ] **Step 3: Commit**

```bash
git add resources/views/auth/register.blade.php
git commit -m "feat: add honeypot hidden field to registration form"
```

---

## Final verification

- [ ] **Run full test suite one last time**

```bash
php artisan test
```

Expected: all tests pass with 2 new tests added.

- [ ] **Push**

```bash
git push
```
