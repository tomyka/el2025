# Google OAuth Login — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add Google OAuth login/registration alongside existing email/password auth, with silent auto-linking when an email already exists.

**Architecture:** `laravel/socialite` handles the OAuth redirect/callback. A `google_id` nullable column is added to `users` and `password` is made nullable. A single `GoogleAuthController` covers three callback cases: existing google_id (login), existing email (auto-link + login), new user (create + postRegister + login). All existing email/password auth is unchanged.

**Tech Stack:** Laravel 11, `laravel/socialite`, Google OAuth 2.0, Bootstrap 5 modal (existing)

---

## File Map

| File | Action |
|---|---|
| `database/migrations/2026_06_05_000000_add_google_id_to_users.php` | Create — add `google_id`, make `password` nullable |
| `app/Models/User.php` | Modify — add `google_id` to `$fillable` |
| `config/services.php` | Modify — add `google` entry |
| `.env.example` | Modify — add three Google env keys |
| `app/Http/Controllers/Auth/GoogleAuthController.php` | Create — `redirect()` and `callback()` |
| `routes/auth.php` | Modify — add two Google routes in `guest` middleware group |
| `resources/views/modals/login.blade.php` | Modify — add Google button below login form |
| `tests/Feature/Auth/GoogleAuthTest.php` | Create — feature tests for all three callback cases |

> **Note:** The test suite connects to the Docker MySQL container (service name `mysql`). Run `docker compose up -d` before running tests, or they will fail with a connection error.

---

### Task 1: Install Socialite and add migration

**Files:**
- Create: `database/migrations/2026_06_05_000000_add_google_id_to_users.php`

- [ ] **Step 1: Install Socialite**

```bash
composer require laravel/socialite
```

Expected: `laravel/socialite` appears in `composer.json` and `vendor/laravel/socialite/` exists.

- [ ] **Step 2: Generate the migration**

```bash
php artisan make:migration add_google_id_to_users
```

This creates a file in `database/migrations/` with a timestamp prefix. Open it.

- [ ] **Step 3: Write the migration content**

Replace the generated file content with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('google_id');
            $table->string('password')->nullable(false)->change();
        });
    }
};
```

- [ ] **Step 4: Run the migration**

Without Docker (local PHP + MySQL):
```bash
php artisan migrate
```

With Docker:
```bash
docker compose run --rm artisan migrate
```

Expected output includes: `Migrated: ...add_google_id_to_users`

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock database/migrations/
git commit -m "feat: install Socialite and add google_id column to users"
```

---

### Task 2: Update User model and config

**Files:**
- Modify: `app/Models/User.php`
- Modify: `config/services.php`
- Modify: `.env.example`

- [ ] **Step 1: Add `google_id` to `$fillable` in `app/Models/User.php`**

Current `$fillable`:
```php
protected $fillable = [
    'username',
    'name',
    'surname',
    'email',
    'password',
];
```

Replace with:
```php
protected $fillable = [
    'username',
    'name',
    'surname',
    'email',
    'password',
    'google_id',
];
```

- [ ] **Step 2: Add Google entry to `config/services.php`**

The file currently ends with `'slack' => [...]`. Add the `google` entry inside the returned array, after the `slack` entry:

```php
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI'),
    ],
```

The full file should now return an array with keys: `postmark`, `ses`, `resend`, `slack`, `google`.

- [ ] **Step 3: Add Google keys to `.env.example`**

Append to `.env.example`:
```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost/auth/google/callback
```

- [ ] **Step 4: Add your credentials to local `.env`** (NOT committed — `.env` is gitignored)

Append to your local `.env`:
```
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=http://localhost/auth/google/callback
```

**Getting credentials from Google Cloud Console:**
1. Go to [console.cloud.google.com](https://console.cloud.google.com)
2. Create or select a project
3. Navigate to: APIs & Services → Credentials → Create Credentials → OAuth 2.0 Client ID
4. Application type: **Web application**
5. Authorized redirect URIs: add `http://localhost/auth/google/callback` (local) and `https://your-domain/auth/google/callback` (production)
6. Copy the Client ID and Client Secret into `.env`

- [ ] **Step 5: Commit**

```bash
git add app/Models/User.php config/services.php .env.example
git commit -m "feat: add google_id to User fillable and configure Socialite services"
```

---

### Task 3: GoogleAuthController, routes, and tests

**Files:**
- Create: `app/Http/Controllers/Auth/GoogleAuthController.php`
- Modify: `routes/auth.php`
- Create: `tests/Feature/Auth/GoogleAuthTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Auth/GoogleAuthTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    private function fakeSocialiteUser(string $googleId, string $email, string $name): void
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn($googleId);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getName')->andReturn($name);

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);
    }

    public function test_redirect_route_returns_redirect(): void
    {
        Socialite::shouldReceive('driver->redirect')->andReturn(
            redirect('https://accounts.google.com/o/oauth2/auth')
        );

        $response = $this->get('/auth/google');

        $response->assertRedirect();
    }

    public function test_existing_google_user_is_logged_in(): void
    {
        $user = User::factory()->create(['google_id' => 'gid-123', 'password' => null]);
        $this->fakeSocialiteUser('gid-123', $user->email, 'Test User');

        $response = $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('main'));
    }

    public function test_email_match_auto_links_google_id_and_logs_in(): void
    {
        $user = User::factory()->create(['google_id' => null]);
        $this->fakeSocialiteUser('gid-456', $user->email, 'Test User');

        $response = $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($user);
        $this->assertEquals('gid-456', $user->fresh()->google_id);
        $response->assertRedirect(route('main'));
    }

    public function test_new_google_user_is_created_and_logged_in(): void
    {
        $this->fakeSocialiteUser('gid-789', 'jonas@example.com', 'Jonas Petraitis');

        $response = $this->get('/auth/google/callback');

        $user = User::where('email', 'jonas@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('gid-789', $user->google_id);
        $this->assertEquals('jonas', $user->username);
        $this->assertEquals('Jonas', $user->name);
        $this->assertEquals('Petraitis', $user->surname);
        $this->assertNull($user->password);
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('main'));
    }
}
```

- [ ] **Step 2: Run tests — confirm they fail**

```bash
php artisan test tests/Feature/Auth/GoogleAuthTest.php
```

Expected: FAIL — route not found (404 on `/auth/google`).

- [ ] **Step 3: Create `app/Http/Controllers/Auth/GoogleAuthController.php`**

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

- [ ] **Step 4: Add routes to `routes/auth.php`**

At the top of `routes/auth.php`, add the import after the existing `use` statements:

```php
use App\Http\Controllers\Auth\GoogleAuthController;
```

Inside the existing `Route::middleware('guest')->group(function () {` block, add these two routes before the closing `});`:

```php
    Route::get('auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
```

- [ ] **Step 5: Run tests — confirm they pass**

```bash
php artisan test tests/Feature/Auth/GoogleAuthTest.php
```

Expected: 4 tests, 4 passed.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Auth/GoogleAuthController.php routes/auth.php tests/Feature/Auth/GoogleAuthTest.php
git commit -m "feat: add GoogleAuthController with three-case callback and feature tests"
```

---

### Task 4: Add Google button to login modal

**Files:**
- Modify: `resources/views/modals/login.blade.php`

- [ ] **Step 1: Add the Google button**

Open `resources/views/modals/login.blade.php`. The file currently contains one `<div class="modal-body">` with a `<form>` inside. Add the Google button section **after** the closing `</form>` tag, still inside the outer `modal-body` div.

The complete file content should be:

```html
            <div class="modal-body" style="padding-bottom: 0px">

                <form class="form" role="form" id="loginmenu" method="post" action="{{ route('login') }}">
                    @csrf
                    <div class="form-group row">
                        <input id="email" placeholder="Email" class="form form-control" type="text" name="email">
                    </div>
                    <div class="form-group row">
                        <input id="password" placeholder="Slaptažodis" class="form form-control" type="password" name="password">
                    </div>
                    <div class="modal-footer">
                        <div class="form-group" id="loginmenu">
                            <button type="submit" class="btn btn-outline-primary mr-auto">Prisijungti</button>
                        </div>
                    </div>
                </form>

                <div class="text-center px-3 pb-3">
                    <hr class="my-2">
                    <a href="{{ route('auth.google') }}" class="btn btn-outline-secondary w-100">
                        <img src="https://www.svgrepo.com/show/475656/google-color.svg"
                             width="18" height="18" class="me-2" alt="Google">
                        Prisijungti su Google
                    </a>
                </div>

            </div>
```

- [ ] **Step 2: Verify in browser**

Open the app as a guest (logged out). The header shows "Prisijungti" — click it to open the login modal. Confirm:
- Google button appears below the email/password form, separated by a horizontal rule
- Clicking the button navigates away from the modal toward Google's consent screen (requires `GOOGLE_CLIENT_ID` set in `.env`)

- [ ] **Step 3: Commit**

```bash
git add resources/views/modals/login.blade.php
git commit -m "feat: add Sign in with Google button to login modal"
```
