# English Language Support (LT/EN Switcher) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a LT/EN language switcher with per-user DB persistence so users can toggle between Lithuanian and English throughout the app.

**Architecture:** JSON translation files (`lang/lt.json`, `lang/en.json`) with Laravel's `__()` helper; a `SetLocale` middleware reads `session('locale')` (populated by `SessionController::setSession()` from `user_settings.locale`); a `LocaleController` handles the toggle POST; all 101 blade templates and ~15 controller flash messages are wrapped in `__()`.

**Tech Stack:** Laravel 11, Blade, JSON translation files, `__()` helper, custom middleware.

---

## Reference: String Wrapping Patterns

These rules apply to every blade-editing task in this plan:

| Situation | Pattern |
|---|---|
| Text node | `Foo` → `{{ __('Foo') }}` |
| HTML attribute | `title="Foo"` → `title="{{ __('Foo') }}"` |
| `aria-label` | `aria-label="Foo"` → `aria-label="{{ __('Foo') }}"` |
| Alpine `x-text` with literals | Move strings to `x-data`: `x-data="{ lbl: '{{ __('Foo') }}' }"` then `x-text="lbl"` |
| Database values | Leave as `{{ $var }}` — never wrap dynamic data |
| Add new key to `lang/lt.json` | `"Naujas tekstas": "Naujas tekstas"` (identity) |
| Add new key to `lang/en.json` | `"Naujas tekstas": "New text"` |

---

### Task 1: Migration — add `locale` to `user_settings` + update model

**Files:**
- Create: `database/migrations/2026_07_03_000000_add_locale_to_user_settings.php`
- Modify: `app/Models/UserSetting.php`
- Modify: `database/factories/UserSettingFactory.php`

- [ ] **Step 1: Create the migration**

```php
// database/migrations/2026_07_03_000000_add_locale_to_user_settings.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->string('locale', 5)->default('lt')->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
```

- [ ] **Step 2: Run the migration**

```bash
php artisan migrate
```

Expected: `Migrating: 2026_07_03_000000_add_locale_to_user_settings` → `Migrated`

- [ ] **Step 3: Add `locale` to `UserSetting::$fillable`**

In `app/Models/UserSetting.php`, change:
```php
protected $fillable = [
    'user_id', 'fee', 'color_id', 'receive_reminders', 'active'
];
```
to:
```php
protected $fillable = [
    'user_id', 'fee', 'color_id', 'receive_reminders', 'active', 'locale'
];
```

- [ ] **Step 4: Add `locale` to `UserSettingFactory`**

In `database/factories/UserSettingFactory.php`, add `'locale' => 'lt'` to the `definition()` array:
```php
public function definition(): array
{
    return [
        'user_id' => null,
        'admin' => $this->faker->boolean(20),
        'result_amount' => $this->faker->numberBetween(0, 10),
        'time_zone' => $this->faker->numberBetween(0, 24),
        'locale' => 'lt',
    ];
}
```

- [ ] **Step 5: Run tests to verify nothing broke**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_07_03_000000_add_locale_to_user_settings.php app/Models/UserSetting.php database/factories/UserSettingFactory.php
git commit -m "feat: add locale column to user_settings"
git push
```

---

### Task 2: LocaleController + route (TDD)

**Files:**
- Create: `tests/Feature/LocaleTest.php`
- Create: `app/Http/Controllers/LocaleController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Write the failing tests**

```php
// tests/Feature/LocaleTest.php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_switch_to_english(): void
    {
        $user = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $user->id, 'locale' => 'lt']);

        $this->actingAs($user)
            ->post('/locale', ['locale' => 'en'])
            ->assertRedirect();

        $this->assertDatabaseHas('user_settings', ['user_id' => $user->id, 'locale' => 'en']);
        $this->assertEquals('en', session('locale'));
    }

    public function test_authenticated_user_can_switch_to_lithuanian(): void
    {
        $user = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $user->id, 'locale' => 'en']);

        $this->actingAs($user)
            ->post('/locale', ['locale' => 'lt'])
            ->assertRedirect();

        $this->assertDatabaseHas('user_settings', ['user_id' => $user->id, 'locale' => 'lt']);
        $this->assertEquals('lt', session('locale'));
    }

    public function test_guest_can_switch_locale_via_session_only(): void
    {
        $this->post('/locale', ['locale' => 'en'])
            ->assertRedirect();

        $this->assertEquals('en', session('locale'));
        $this->assertDatabaseCount('user_settings', 0);
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $user = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post('/locale', ['locale' => 'fr'])
            ->assertSessionHasErrors('locale');
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

```bash
php artisan test tests/Feature/LocaleTest.php
```

Expected: 4 failures (route not found).

- [ ] **Step 3: Add the route to `routes/web.php`**

Add to the `use` imports at the top of the file:
```php
use App\Http\Controllers\LocaleController;
```

Add before the first `Route::middleware('auth')->group(...)` block:
```php
Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');
```

- [ ] **Step 4: Create `LocaleController`**

```php
// app/Http/Controllers/LocaleController.php
<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request)
    {
        $request->validate(['locale' => 'required|in:lt,en']);

        $locale = $request->input('locale');

        if ($request->user()) {
            UserSetting::where('user_id', $request->user()->id)
                ->update(['locale' => $locale]);
        }

        session(['locale' => $locale]);

        return redirect()->back();
    }
}
```

- [ ] **Step 5: Run the tests to verify they pass**

```bash
php artisan test tests/Feature/LocaleTest.php
```

Expected: 4 tests, 4 passed.

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/LocaleTest.php app/Http/Controllers/LocaleController.php routes/web.php
git commit -m "feat: add LocaleController and POST /locale route"
git push
```

---

### Task 3: SetLocale middleware + SessionController update + register

**Files:**
- Create: `app/Http/Middleware/SetLocale.php`
- Modify: `app/Http/Controllers/SessionController.php`
- Modify: `bootstrap/app.php`

- [ ] **Step 1: Create `SetLocale` middleware**

```php
// app/Http/Middleware/SetLocale.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(session('locale', 'lt'));
        return $next($request);
    }
}
```

- [ ] **Step 2: Register the middleware in `bootstrap/app.php`**

Change:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'admin'       => \App\Http\Middleware\AdminMiddleware::class,
        'superadmin'  => \App\Http\Middleware\SuperAdminMiddleware::class,
        'level9admin' => \App\Http\Middleware\EnsureIsLevel9Admin::class,
    ]);
})
```
To:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\SetLocale::class,
    ]);
    $middleware->alias([
        'admin'       => \App\Http\Middleware\AdminMiddleware::class,
        'superadmin'  => \App\Http\Middleware\SuperAdminMiddleware::class,
        'level9admin' => \App\Http\Middleware\EnsureIsLevel9Admin::class,
    ]);
})
```

- [ ] **Step 3: Update `SessionController::setSession()` to load locale into session**

In `app/Http/Controllers/SessionController.php`, add one line after the existing `Session::put(...)` block (after line 66):
```php
Session::put('survivalGame',   $tournament->survival_game ? 1 : 0);
Session::put('timeDifference', $timeDifference?->value ?? 0);
Session::put('locale',         $userSettings->locale ?? 'lt');  // ← add this
```

- [ ] **Step 4: Run all tests to verify nothing broke**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Middleware/SetLocale.php bootstrap/app.php app/Http/Controllers/SessionController.php
git commit -m "feat: add SetLocale middleware, load locale from user_settings into session"
git push
```

---

### Task 4: Create `lang/lt.json` and `lang/en.json`

**Files:**
- Create: `lang/lt.json`
- Create: `lang/en.json`

These files are seeded with all strings identified so far. Every subsequent blade task adds new keys it discovers to both files.

- [ ] **Step 1: Create `lang/lt.json`** (identity map — keys equal values)

```json
{
    "Pradžia": "Pradžia",
    "Spėjimai": "Spėjimai",
    "Eiga": "Eiga",
    "Išlikimas": "Išlikimas",
    "Suvestinė": "Suvestinė",
    "Prognozės": "Prognozės",
    "Grafikas": "Grafikas",
    "Informacija": "Informacija",
    "Taisyklės": "Taisyklės",
    "Pagalba": "Pagalba",
    "Jaunimo linija": "Jaunimo linija",
    "Privatumas": "Privatumas",
    "Lygos": "Lygos",
    "Turnyrai": "Turnyrai",
    "Profilis": "Profilis",
    "Admin": "Admin",
    "Atsijungti": "Atsijungti",
    "Lyga": "Lyga",
    "Prisijungti": "Prisijungti",
    "Lyderiai": "Lyderiai",
    "Keisti temą": "Keisti temą",
    "Atidaryti meniu": "Atidaryti meniu",
    "Rungtynių spėjimai": "Rungtynių spėjimai",
    "Turnyro eiga": "Turnyro eiga",
    "Tvarkyti lygą": "Tvarkyti lygą",
    "Paskyra": "Paskyra",
    "← Turnyrai": "← Turnyrai",
    "Lyderių lentelė": "Lyderių lentelė",
    "Žaidžiame nuo 2016 metų — kiekvienas turnyras prideda naujų iššūkių ir intrigų.": "Žaidžiame nuo 2016 metų — kiekvienas turnyras prideda naujų iššūkių ir intrigų.",
    "Prisijunk": "Prisijunk",
    "Žaidėjas": "Žaidėjas",
    "Taškai": "Taškai",
    "Tikslūs": "Tikslūs",
    "Nugalėtojai": "Nugalėtojai",
    "Žaidimai": "Žaidimai",
    "Narys nuo": "Narys nuo",
    "Paskyros informacija": "Paskyros informacija",
    "Vartotojo vardas": "Vartotojo vardas",
    "Vardas": "Vardas",
    "Pavardė": "Pavardė",
    "El. paštas": "El. paštas",
    "Išsaugoti": "Išsaugoti",
    "Nustatyti slaptažodį": "Nustatyti slaptažodį",
    "Jūs prisijungėte per Google. Nustatykite slaptažodį, kad galėtumėte prisijungti ir be Google.": "Jūs prisijungėte per Google. Nustatykite slaptažodį, kad galėtumėte prisijungti ir be Google.",
    "Naujas slaptažodis": "Naujas slaptažodis",
    "Pakartoti slaptažodį": "Pakartoti slaptažodį",
    "Slaptažodžio keitimas": "Slaptažodžio keitimas",
    "Dabartinis slaptažodis": "Dabartinis slaptažodis",
    "Keisti slaptažodį": "Keisti slaptažodį",
    "Pranešimai": "Pranešimai",
    "Gauti priminimus el. paštu apie artėjančias rungtynes prieš pat žaidimo pradžią.": "Gauti priminimus el. paštu apie artėjančias rungtynes prieš pat žaidimo pradžią.",
    "Įjungti priminimus": "Įjungti priminimus",
    "Pavojaus zona": "Pavojaus zona",
    "Paskyros ištrynimas yra negrįžtamas veiksmas.": "Paskyros ištrynimas yra negrįžtamas veiksmas.",
    "Ištrinti paskyrą": "Ištrinti paskyrą",
    "Atšaukti": "Atšaukti",
    "Visi jūsų duomenys bus ištrinti. Šio veiksmo negalima atšaukti.": "Visi jūsų duomenys bus ištrinti. Šio veiksmo negalima atšaukti.",
    "Norėdami patvirtinti, įveskite savo slaptažodį.": "Norėdami patvirtinti, įveskite savo slaptažodį.",
    "Slaptažodis": "Slaptažodis",
    "Ištrinti paskyrą visam laikui": "Ištrinti paskyrą visam laikui",
    "Slaptažodis sėkmingai pakeistas.": "Slaptažodis sėkmingai pakeistas.",
    "Kalba": "Kalba",
    "Lietuvių": "Lietuvių",
    "English": "English",
    "Lyga sukurta": "Lyga sukurta",
    "Lyga atnaujinta": "Lyga atnaujinta",
    "Vartotojas jau narys arba pakviestas": "Vartotojas jau narys arba pakviestas",
    "Kvietimas išsiųstas": "Kvietimas išsiųstas",
    "Prisijungta prie lygos": "Prisijungta prie lygos",
    "Kvietimas atmestas": "Kvietimas atmestas",
    "Lyga pakeista": "Lyga pakeista",
    "Lygos koeficientai atnaujinti": "Lygos koeficientai atnaujinti",
    "Viešos lygos ištrinti negalima": "Viešos lygos ištrinti negalima",
    "Lyga ištrinta": "Lyga ištrinta",
    "Negalima palikti Bendros lygos": "Negalima palikti Bendros lygos",
    "Perduokite lygos valdymą kitam nariui prieš išeidami": "Perduokite lygos valdymą kitam nariui prieš išeidami",
    "Negalima palikti vienintelės lygos": "Negalima palikti vienintelės lygos",
    "Palikote lygą": "Palikote lygą",
    "Profilis atnaujintas.": "Profilis atnaujintas.",
    "Pranešimų nustatymai atnaujinti.": "Pranešimų nustatymai atnaujinti.",
    "Pranešimai išjungti sėkmingai.": "Pranešimai išjungti sėkmingai.",
    "Vartotojo nustatymai pakeisti": "Vartotojo nustatymai pakeisti",
    "Google prisijungimas nepavyko. Bandykite dar kartą.": "Google prisijungimas nepavyko. Bandykite dar kartą."
}
```

- [ ] **Step 2: Create `lang/en.json`**

```json
{
    "Pradžia": "Home",
    "Spėjimai": "Predictions",
    "Eiga": "Standings",
    "Išlikimas": "Survival",
    "Suvestinė": "Summary",
    "Prognozės": "Forecasts",
    "Grafikas": "Chart",
    "Informacija": "Info",
    "Taisyklės": "Rules",
    "Pagalba": "Help",
    "Jaunimo linija": "Youth Crisis Line",
    "Privatumas": "Privacy",
    "Lygos": "Leagues",
    "Turnyrai": "Tournaments",
    "Profilis": "Profile",
    "Admin": "Admin",
    "Atsijungti": "Log out",
    "Lyga": "League",
    "Prisijungti": "Log in",
    "Lyderiai": "Leaderboard",
    "Keisti temą": "Toggle theme",
    "Atidaryti meniu": "Open menu",
    "Rungtynių spėjimai": "Match predictions",
    "Turnyro eiga": "Tournament standings",
    "Tvarkyti lygą": "Manage league",
    "Paskyra": "Account",
    "← Turnyrai": "← Tournaments",
    "Lyderių lentelė": "Leaderboard",
    "Žaidžiame nuo 2016 metų — kiekvienas turnyras prideda naujų iššūkių ir intrigų.": "We've been playing since 2016 — every tournament adds new challenges and surprises.",
    "Prisijunk": "Join",
    "Žaidėjas": "Player",
    "Taškai": "Points",
    "Tikslūs": "Exact",
    "Nugalėtojai": "Winners",
    "Žaidimai": "Games",
    "Narys nuo": "Member since",
    "Paskyros informacija": "Account details",
    "Vartotojo vardas": "Username",
    "Vardas": "First name",
    "Pavardė": "Last name",
    "El. paštas": "Email",
    "Išsaugoti": "Save",
    "Nustatyti slaptažodį": "Set password",
    "Jūs prisijungėte per Google. Nustatykite slaptažodį, kad galėtumėte prisijungti ir be Google.": "You signed in with Google. Set a password to also log in without Google.",
    "Naujas slaptažodis": "New password",
    "Pakartoti slaptažodį": "Confirm password",
    "Slaptažodžio keitimas": "Change password",
    "Dabartinis slaptažodis": "Current password",
    "Keisti slaptažodį": "Change password",
    "Pranešimai": "Notifications",
    "Gauti priminimus el. paštu apie artėjančias rungtynes prieš pat žaidimo pradžią.": "Receive email reminders about upcoming matches just before kick-off.",
    "Įjungti priminimus": "Enable reminders",
    "Pavojaus zona": "Danger zone",
    "Paskyros ištrynimas yra negrįžtamas veiksmas.": "Account deletion is irreversible.",
    "Ištrinti paskyrą": "Delete account",
    "Atšaukti": "Cancel",
    "Visi jūsų duomenys bus ištrinti. Šio veiksmo negalima atšaukti.": "All your data will be deleted. This action cannot be undone.",
    "Norėdami patvirtinti, įveskite savo slaptažodį.": "To confirm, enter your password.",
    "Slaptažodis": "Password",
    "Ištrinti paskyrą visam laikui": "Delete account permanently",
    "Slaptažodis sėkmingai pakeistas.": "Password successfully changed.",
    "Kalba": "Language",
    "Lietuvių": "Lithuanian",
    "English": "English",
    "Lyga sukurta": "League created",
    "Lyga atnaujinta": "League updated",
    "Vartotojas jau narys arba pakviestas": "User is already a member or has been invited",
    "Kvietimas išsiųstas": "Invitation sent",
    "Prisijungta prie lygos": "Joined the league",
    "Kvietimas atmestas": "Invitation declined",
    "Lyga pakeista": "League changed",
    "Lygos koeficientai atnaujinti": "League odds updated",
    "Viešos lygos ištrinti negalima": "Cannot delete a public league",
    "Lyga ištrinta": "League deleted",
    "Negalima palikti Bendros lygos": "Cannot leave the General league",
    "Perduokite lygos valdymą kitam nariui prieš išeidami": "Transfer league management to another member before leaving",
    "Negalima palikti vienintelės lygos": "Cannot leave your only league",
    "Palikote lygą": "You left the league",
    "Profilis atnaujintas.": "Profile updated.",
    "Pranešimų nustatymai atnaujinti.": "Notification settings updated.",
    "Pranešimai išjungti sėkmingai.": "Notifications disabled successfully.",
    "Vartotojo nustatymai pakeisti": "User settings changed",
    "Google prisijungimas nepavyko. Bandykite dar kartą.": "Google sign-in failed. Please try again."
}
```

- [ ] **Step 3: Commit**

```bash
git add lang/lt.json lang/en.json
git commit -m "feat: add initial lt and en translation files"
git push
```

---

### Task 5: Add LT/EN toggle to `header.blade.php` + wrap all its strings

**Files:**
- Modify: `resources/views/partials/header.blade.php`
- Modify: `resources/css/app.css` (or equivalent stylesheet)

**Wrapping pattern:**
- Text nodes: `Pradžia` → `{{ __('Pradžia') }}`
- Attributes: `title="Keisti temą"` → `title="{{ __('Keisti temą') }}"`
- `aria-label="..."` → `aria-label="{{ __('...') }}"`

- [ ] **Step 1: Wrap all Lithuanian strings in `header.blade.php`**

Apply these substitutions throughout the file:

| Find | Replace with |
|---|---|
| `>Pradžia<` | `>{{ __('Pradžia') }}<` |
| `> Spėjimai<` | `> {{ __('Spėjimai') }}<` |
| `> Eiga<` | `> {{ __('Eiga') }}<` |
| `> Išlikimas<` | `> {{ __('Išlikimas') }}<` |
| `> Suvestinė<` | `> {{ __('Suvestinė') }}<` |
| `> Prognozės<` | `> {{ __('Prognozės') }}<` |
| `> Grafikas<` | `> {{ __('Grafikas') }}<` |
| `> Informacija<` | `> {{ __('Informacija') }}<` |
| `> Taisyklės<` | `> {{ __('Taisyklės') }}<` |
| `> Pagalba<` | `> {{ __('Pagalba') }}<` |
| `> Jaunimo linija<` | `> {{ __('Jaunimo linija') }}<` |
| `> Privatumas<` | `> {{ __('Privatumas') }}<` |
| `> Lygos<` | `> {{ __('Lygos') }}<` |
| `title="Keisti temą"` | `title="{{ __('Keisti temą') }}"` |
| `aria-label="Keisti temą"` | `aria-label="{{ __('Keisti temą') }}"` |
| `aria-label="Atidaryti meniu"` | `aria-label="{{ __('Atidaryti meniu') }}"` |
| `<span class="sb-nav-label">Lyga</span>` | `<span class="sb-nav-label">{{ __('Lyga') }}</span>` |
| `>Profilis<` | `>{{ __('Profilis') }}<` |
| `>Admin<` | `>{{ __('Admin') }}<` |
| `>Atsijungti<` | `>{{ __('Atsijungti') }}<` |
| `← Turnyrai` (link text) | `{{ __('← Turnyrai') }}` |
| `> Lyderiai<` | `> {{ __('Lyderiai') }}<` |
| `> Prisijungti<` | `> {{ __('Prisijungti') }}<` |
| Mobile `>Spėjimai</div>` (sb-mobile-label) | `>{{ __('Spėjimai') }}</div>` |
| `Rungtynių spėjimai` | `{{ __('Rungtynių spėjimai') }}` |
| `Turnyro eiga` | `{{ __('Turnyro eiga') }}` |
| Mobile `>Suvestinė</div>` | `>{{ __('Suvestinė') }}</div>` |
| Mobile `> Prognozės<` | `> {{ __('Prognozės') }}<` |
| Mobile `>Informacija</div>` | `>{{ __('Informacija') }}</div>` |
| Mobile `> Taisyklės<` | `> {{ __('Taisyklės') }}<` |
| Mobile `> Pagalba<` | `> {{ __('Pagalba') }}<` |
| Mobile `> Privatumas<` | `> {{ __('Privatumas') }}<` |
| Mobile `>Lyga</div>` (sb-mobile-label) | `>{{ __('Lyga') }}</div>` |
| Mobile `> Tvarkyti lygą<` | `> {{ __('Tvarkyti lygą') }}<` |
| Mobile `>Paskyra</div>` | `>{{ __('Paskyra') }}</div>` |
| Mobile `> Profilis<` | `> {{ __('Profilis') }}<` |
| Mobile `> Admin<` | `> {{ __('Admin') }}<` |
| Mobile `> Atsijungti<` | `> {{ __('Atsijungti') }}<` |

- [ ] **Step 2: Add LT/EN toggle to desktop right nav**

In the desktop right nav section (`d-none d-lg-flex`), find the theme toggle button and insert the locale toggle immediately after it (before the avatar dropdown):

```blade
{{-- Theme toggle --}}
<button class="sb-theme-btn" onclick="sbToggleTheme()" title="{{ __('Keisti temą') }}" aria-label="{{ __('Keisti temą') }}">
    <i class="bi bi-sun-fill sb-theme-sun"></i>
    <i class="bi bi-moon-fill sb-theme-moon"></i>
</button>

{{-- Locale toggle --}}
<div class="d-flex align-items-center" style="gap:2px">
    <form method="POST" action="{{ route('locale.update') }}" class="d-inline">
        @csrf
        <input type="hidden" name="locale" value="lt">
        <button type="submit" class="sb-locale-btn {{ app()->getLocale() === 'lt' ? 'active' : '' }}">LT</button>
    </form>
    <form method="POST" action="{{ route('locale.update') }}" class="d-inline">
        @csrf
        <input type="hidden" name="locale" value="en">
        <button type="submit" class="sb-locale-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</button>
    </form>
</div>
```

- [ ] **Step 3: Add LT/EN toggle to guest nav**

In the guest nav section (`@else` branch at the bottom), add the locale toggle after the theme button:

```blade
{{-- Locale toggle --}}
<div class="d-flex align-items-center" style="gap:2px">
    <form method="POST" action="{{ route('locale.update') }}" class="d-inline">
        @csrf
        <input type="hidden" name="locale" value="lt">
        <button type="submit" class="sb-locale-btn {{ app()->getLocale() === 'lt' ? 'active' : '' }}">LT</button>
    </form>
    <form method="POST" action="{{ route('locale.update') }}" class="d-inline">
        @csrf
        <input type="hidden" name="locale" value="en">
        <button type="submit" class="sb-locale-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</button>
    </form>
</div>
```

- [ ] **Step 4: Add LT/EN toggle to mobile collapse panel**

Inside `#sbNavMobile`, in the account section (`<div class="sb-mobile-label">Paskyra</div>`), add the toggle as the first item in that group:

```blade
<div class="sb-mobile-group">
    <div class="sb-mobile-label">{{ __('Paskyra') }}</div>

    {{-- Locale toggle (mobile) --}}
    <div class="d-flex gap-2 px-2 py-2">
        <form method="POST" action="{{ route('locale.update') }}">
            @csrf
            <input type="hidden" name="locale" value="lt">
            <button type="submit" class="sb-locale-btn {{ app()->getLocale() === 'lt' ? 'active' : '' }}">LT</button>
        </form>
        <form method="POST" action="{{ route('locale.update') }}">
            @csrf
            <input type="hidden" name="locale" value="en">
            <button type="submit" class="sb-locale-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</button>
        </form>
    </div>
```

- [ ] **Step 5: Add CSS for `.sb-locale-btn`**

Find the main CSS file (check `resources/css/app.css` and custom CSS files linked in `resources/views/layouts/master.blade.php`) and add:

```css
.sb-locale-btn {
    background: none;
    border: 1px solid var(--sb-border, #dee2e6);
    border-radius: 4px;
    padding: 2px 7px;
    font-size: .75rem;
    font-weight: 600;
    color: var(--sb-muted, #6c757d);
    cursor: pointer;
    transition: all .15s;
}
.sb-locale-btn.active {
    background: var(--sb-accent, #0d6efd);
    border-color: var(--sb-accent, #0d6efd);
    color: #fff;
}
.sb-locale-btn:hover:not(.active) {
    border-color: var(--sb-accent, #0d6efd);
    color: var(--sb-accent, #0d6efd);
}
```

- [ ] **Step 6: Rebuild assets and verify in browser**

```bash
npm run dev
```

Open the app. Verify:
- Desktop: LT/EN toggle appears next to the theme button, active locale is highlighted
- Mobile: LT/EN toggle appears in the Account section of the mobile menu
- Guest nav: LT/EN toggle is visible
- Clicking EN switches the display language; clicking LT switches back

- [ ] **Step 7: Commit**

```bash
git add resources/views/partials/header.blade.php resources/css/
git commit -m "feat: add LT/EN toggle to navbar and wrap header strings"
git push
```

---

### Task 6: Wrap shared partials

**Files:**
- Modify: `resources/views/partials/bottom-nav.blade.php`
- Modify: `resources/views/partials/messages.blade.php`
- Modify: `resources/views/partials/warnings.blade.php`
- Modify: `resources/views/partials/fee.blade.php`
- Modify: `resources/views/partials/standings.blade.php`
- Modify: `resources/views/partials/pointsStandings.blade.php`
- Modify: `resources/views/partials/points.blade.php`
- Modify: `resources/views/partials/games.blade.php`
- Modify: `resources/views/partials/progress-bar.blade.php`
- Modify: `resources/views/partials/snapshot-card.blade.php`
- Modify: `resources/views/partials/activity-feed.blade.php`
- Modify: `resources/views/partials/positionTrend.blade.php`
- Modify: `resources/views/partials/cookie-consent.blade.php`
- Modify: `resources/views/partials/league-switcher.blade.php`
- Modify: `resources/views/partials/rules.blade.php`
- Modify: `resources/views/partials/teams.blade.php`
- Modify: `lang/lt.json` — add new keys
- Modify: `lang/en.json` — add new keys

**Rules for every file in this task:**
1. Wrap every hardcoded Lithuanian text node: `Foo` → `{{ __('Foo') }}`
2. Wrap Lithuanian attribute values: `title="Foo"` → `title="{{ __('Foo') }}"`
3. For Alpine.js `x-text` with hardcoded strings, move the strings into `x-data` and reference them by variable:
   ```blade
   {{-- Before --}}
   <div x-data="{ open: false }">
       <span x-text="open ? 'Atšaukti' : 'Ištrinti'"></span>
   
   {{-- After --}}
   <div x-data="{ open: false, lblCancel: '{{ __('Atšaukti') }}', lblDelete: '{{ __('Ištrinti') }}' }">
       <span x-text="open ? lblCancel : lblDelete"></span>
   ```
4. For any new string not in the lang files yet, add it immediately to both `lang/lt.json` (identity) and `lang/en.json` (English translation).
5. Database-sourced values (team names, league names, user names) are **not** wrapped — they stay as `{{ $var }}`.

- [ ] **Step 1: Wrap `bottom-nav.blade.php`**

Find and replace:
```blade
<span class="sb-tab-label">Spėjimai</span>
```
with:
```blade
<span class="sb-tab-label">{{ __('Spėjimai') }}</span>
```

Do the same for `Eiga` and `Išlikimas`. The dynamic `$activeLeagueName` is database-sourced — leave it.

- [ ] **Step 2: Open and wrap each remaining partial in the list**

For each file: open it, apply the rules above to every hardcoded Lithuanian string, and add any new keys to both lang files immediately.

- [ ] **Step 3: Commit**

```bash
git add resources/views/partials/ lang/lt.json lang/en.json
git commit -m "feat: wrap shared partials in __() translation helper"
git push
```

---

### Task 7: Wrap layout files

**Files:**
- Modify: `resources/views/layouts/master.blade.php`
- Modify: `resources/views/layouts/master_blank.blade.php`
- Modify: `resources/views/admin/layouts/master.blade.php`
- Modify: `lang/lt.json` — add new keys
- Modify: `lang/en.json` — add new keys

- [ ] **Step 1: Open each layout file and wrap all hardcoded Lithuanian strings**

Apply the same rules as Task 6. Add any new keys to both lang files.

- [ ] **Step 2: Commit**

```bash
git add resources/views/layouts/ resources/views/admin/layouts/ lang/lt.json lang/en.json
git commit -m "feat: wrap layout files in __() translation helper"
git push
```

---

### Task 8: Wrap core page views

**Files:**
- Modify: `resources/views/leaderboard.blade.php`
- Modify: `resources/views/prediction/results.blade.php`
- Modify: `resources/views/prediction/standings.blade.php`
- Modify: `resources/views/prediction/survival.blade.php`
- Modify: `resources/views/prediction/game-single.blade.php`
- Modify: `resources/views/summary/games.blade.php`
- Modify: `resources/views/summary/standings.blade.php`
- Modify: `resources/views/summary/results.blade.php`
- Modify: `resources/views/summary/survivals.blade.php`
- Modify: `resources/views/summary/chart.blade.php`
- Modify: `resources/views/compare/show.blade.php`
- Modify: `resources/views/compare/_card.blade.php`
- Modify: `resources/views/statistics/predictions.blade.php`
- Modify: `resources/views/statistics/team.blade.php`
- Modify: `resources/views/statistics/teams.blade.php`
- Modify: `lang/lt.json` — add new keys
- Modify: `lang/en.json` — add new keys

**Key strings in `leaderboard.blade.php`:**

| Old | New |
|---|---|
| `Lyderių lentelė` | `{{ __('Lyderių lentelė') }}` |
| `Žaidžiame nuo 2016 metų — kiekvienas turnyras prideda naujų iššūkių ir intrigų.` | `{{ __('Žaidžiame nuo 2016 metų — kiekvienas turnyras prideda naujų iššūkių ir intrigų.') }}` |
| `Prisijunk` (link text) | `{{ __('Prisijunk') }}` |
| `Žaidėjas` (th) | `{{ __('Žaidėjas') }}` |
| `Taškai` (th) | `{{ __('Taškai') }}` |
| `Tikslūs` (th) | `{{ __('Tikslūs') }}` |
| `Nugalėtojai` (th) | `{{ __('Nugalėtojai') }}` |
| `Žaidimai` (th) | `{{ __('Žaidimai') }}` |

- [ ] **Step 1: Wrap `leaderboard.blade.php`** using the table above

- [ ] **Step 2: Open and wrap each remaining file in the list**

Apply the Task 6 rules. Add new keys to lang files as discovered.

- [ ] **Step 3: Commit**

```bash
git add resources/views/leaderboard.blade.php resources/views/prediction/ resources/views/summary/ resources/views/compare/ resources/views/statistics/ lang/lt.json lang/en.json
git commit -m "feat: wrap core page views in __() translation helper"
git push
```

---

### Task 9: Add language preference UI to `userProfile` + wrap profile & auth views

**Files:**
- Modify: `resources/views/userProfile.blade.php`
- Modify: `resources/views/login.blade.php`
- Modify: `resources/views/modals/login.blade.php`
- Modify: `resources/views/modals/register.blade.php`
- Modify: `resources/views/modals/main.blade.php`
- Modify: `resources/views/auth/modals/login.blade.php`
- Modify: `resources/views/auth/modals/register.blade.php`
- Modify: `resources/views/auth/modals/main.blade.php`
- Modify: `resources/views/auth/login.blade.php`
- Modify: `resources/views/auth/register.blade.php`
- Modify: `resources/views/auth/verify.blade.php`
- Modify: `resources/views/auth/verify-email.blade.php`
- Modify: `resources/views/modals/password.blade.php`
- Modify: `resources/views/userGroups.blade.php`
- Modify: `resources/views/users.blade.php`
- Modify: `resources/views/userPassword.blade.php`
- Modify: `lang/lt.json` — add new keys
- Modify: `lang/en.json` — add new keys

- [ ] **Step 1: Add language preference card to `userProfile.blade.php`**

Insert this card before the "Danger zone" card (find `{{-- Danger zone --}}`):

```blade
{{-- Language preference --}}
<div class="sb-card mb-3">
    <div class="sb-card-title"><i class="bi bi-translate me-1"></i>{{ __('Kalba') }}</div>
    <div class="d-flex gap-2">
        <form method="POST" action="{{ route('locale.update') }}">
            @csrf
            <input type="hidden" name="locale" value="lt">
            <button type="submit"
                class="btn btn-sm {{ app()->getLocale() === 'lt' ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ __('Lietuvių') }}
            </button>
        </form>
        <form method="POST" action="{{ route('locale.update') }}">
            @csrf
            <input type="hidden" name="locale" value="en">
            <button type="submit"
                class="btn btn-sm {{ app()->getLocale() === 'en' ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ __('English') }}
            </button>
        </form>
    </div>
</div>
```

- [ ] **Step 2: Wrap Lithuanian strings in `userProfile.blade.php`**

| Old | New |
|---|---|
| `Slaptažodis sėkmingai pakeistas.` | `{{ __('Slaptažodis sėkmingai pakeistas.') }}` |
| `Narys nuo` | `{{ __('Narys nuo') }}` |
| `Paskyros informacija` | `{{ __('Paskyros informacija') }}` |
| `Vartotojo vardas` (label) | `{{ __('Vartotojo vardas') }}` |
| `Vardas` (label) | `{{ __('Vardas') }}` |
| `Pavardė` (label) | `{{ __('Pavardė') }}` |
| `El. paštas` (label) | `{{ __('El. paštas') }}` |
| `Išsaugoti` (buttons) | `{{ __('Išsaugoti') }}` |
| `Nustatyti slaptažodį` (title + button) | `{{ __('Nustatyti slaptažodį') }}` |
| `Jūs prisijungėte per Google...` | `{{ __('Jūs prisijungėte per Google. Nustatykite slaptažodį, kad galėtumėte prisijungti ir be Google.') }}` |
| `Naujas slaptažodis` (labels) | `{{ __('Naujas slaptažodis') }}` |
| `Pakartoti slaptažodį` (labels) | `{{ __('Pakartoti slaptažodį') }}` |
| `Slaptažodžio keitimas` (title) | `{{ __('Slaptažodžio keitimas') }}` |
| `Dabartinis slaptažodis` (label) | `{{ __('Dabartinis slaptažodis') }}` |
| `Keisti slaptažodį` (button) | `{{ __('Keisti slaptažodį') }}` |
| `Pranešimai` (card title) | `{{ __('Pranešimai') }}` |
| `Gauti priminimus el. paštu...` | `{{ __('Gauti priminimus el. paštu apie artėjančias rungtynes prieš pat žaidimo pradžią.') }}` |
| `Įjungti priminimus` (label) | `{{ __('Įjungti priminimus') }}` |
| `Pavojaus zona` | `{{ __('Pavojaus zona') }}` |
| `Paskyros ištrynimas yra negrįžtamas veiksmas.` | `{{ __('Paskyros ištrynimas yra negrįžtamas veiksmas.') }}` |
| `Visi jūsų duomenys bus ištrinti. Šio veiksmo negalima atšaukti.` | `{{ __('Visi jūsų duomenys bus ištrinti. Šio veiksmo negalima atšaukti.') }}` |
| `Norėdami patvirtinti, įveskite savo slaptažodį.` | `{{ __('Norėdami patvirtinti, įveskite savo slaptažodį.') }}` |
| `Slaptažodis` (label) | `{{ __('Slaptažodis') }}` |
| `Ištrinti paskyrą visam laikui` | `{{ __('Ištrinti paskyrą visam laikui') }}` |

- [ ] **Step 3: Fix Alpine.js `x-text` in the danger zone**

Find the delete card:
```blade
<div class="sb-card" x-data="{ open: false }">
    ...
    <button class="btn btn-sm btn-outline-danger flex-shrink-0" @click="open = !open">
        <i class="bi me-1" :class="open ? 'bi-x' : 'bi-trash'"></i>
        <span x-text="open ? 'Atšaukti' : 'Ištrinti paskyrą'"></span>
    </button>
```

Replace with:
```blade
<div class="sb-card" x-data="{ open: false, lblCancel: '{{ __('Atšaukti') }}', lblDelete: '{{ __('Ištrinti paskyrą') }}' }">
    ...
    <button class="btn btn-sm btn-outline-danger flex-shrink-0" @click="open = !open">
        <i class="bi me-1" :class="open ? 'bi-x' : 'bi-trash'"></i>
        <span x-text="open ? lblCancel : lblDelete"></span>
    </button>
```

- [ ] **Step 4: Wrap all remaining files in the list**

Apply Task 6 rules to each file. Add new keys to lang files as encountered.

- [ ] **Step 5: Run tests**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add resources/views/userProfile.blade.php resources/views/login.blade.php resources/views/modals/ resources/views/auth/ resources/views/userGroups.blade.php resources/views/users.blade.php resources/views/userPassword.blade.php lang/lt.json lang/en.json
git commit -m "feat: add language UI to profile, wrap profile and auth views"
git push
```

---

### Task 10: Wrap admin views

**Files:**
- Modify: all files under `resources/views/admin/`
- Modify: `lang/lt.json` — add new keys
- Modify: `lang/en.json` — add new keys

- [ ] **Step 1: Open and wrap each admin view**

Files: `admin/index.blade.php`, `admin/games.blade.php`, `admin/results.blade.php`, `admin/users.blade.php`, `admin/teams.blade.php`, `admin/teaminsert.blade.php`, `admin/events.blade.php`, `admin/messages.blade.php`, `admin/settings.blade.php`, `admin/lms.blade.php`, `admin/audit.blade.php`, `admin/leagues.blade.php`, `admin/tournaments/index.blade.php`, `admin/tournaments/form.blade.php`, `admin/partials/header.blade.php`

Apply Task 6 rules. Add new keys to lang files.

- [ ] **Step 2: Commit**

```bash
git add resources/views/admin/ lang/lt.json lang/en.json
git commit -m "feat: wrap admin views in __() translation helper"
git push
```

---

### Task 11: Wrap remaining top-level views

**Files:**
- Modify: `resources/views/rules.blade.php`
- Modify: `resources/views/help.blade.php`
- Modify: `resources/views/privacy.blade.php`
- Modify: `resources/views/charity.blade.php`
- Modify: `resources/views/sponsors.blade.php`
- Modify: `resources/views/welcome.blade.php`
- Modify: `resources/views/support.blade.php`
- Modify: `resources/views/profiles.blade.php`
- Modify: `resources/views/leagues/index.blade.php`
- Modify: `resources/views/tournaments/show.blade.php`
- Modify: `resources/views/partials/rules.blade.php`
- Modify: `resources/views/emails/prediction-reminder.blade.php`
- Modify: `lang/lt.json` — add new keys
- Modify: `lang/en.json` — add new keys

- [ ] **Step 1: Wrap each view**

Apply Task 6 rules. Add new keys to lang files as discovered.

- [ ] **Step 2: Commit**

```bash
git add resources/views/rules.blade.php resources/views/help.blade.php resources/views/privacy.blade.php resources/views/charity.blade.php resources/views/sponsors.blade.php resources/views/welcome.blade.php resources/views/support.blade.php resources/views/profiles.blade.php resources/views/leagues/ resources/views/tournaments/ resources/views/emails/ lang/lt.json lang/en.json
git commit -m "feat: wrap remaining top-level views in __() translation helper"
git push
```

---

### Task 12: Wrap controller flash messages

**Files:**
- Modify: `app/Http/Controllers/LeagueController.php`
- Modify: `app/Http/Controllers/UserProfileController.php`
- Modify: `app/Http/Controllers/UserSettingController.php`
- Modify: `app/Http/Controllers/Auth/GoogleAuthController.php`

- [ ] **Step 1: Wrap flash messages in `LeagueController.php`**

Find each `->with('info', '...')` / `->with('error', '...')` and wrap the string value in `__()`:

| Find | Replace |
|---|---|
| `->with('info', 'Lyga sukurta')` | `->with('info', __('Lyga sukurta'))` |
| `->with('info', 'Lyga atnaujinta')` | `->with('info', __('Lyga atnaujinta'))` |
| `->with('error', 'Vartotojas jau narys arba pakviestas')` | `->with('error', __('Vartotojas jau narys arba pakviestas'))` |
| `->with('info', 'Kvietimas išsiųstas')` | `->with('info', __('Kvietimas išsiųstas'))` |
| `->with('info', 'Prisijungta prie lygos')` | `->with('info', __('Prisijungta prie lygos'))` |
| `->with('info', 'Kvietimas atmestas')` | `->with('info', __('Kvietimas atmestas'))` |
| `->with('info', 'Lyga pakeista')` | `->with('info', __('Lyga pakeista'))` |
| `->with('info', 'Lygos koeficientai atnaujinti')` | `->with('info', __('Lygos koeficientai atnaujinti'))` |
| `->with('error', 'Viešos lygos ištrinti negalima')` (×2) | `->with('error', __('Viešos lygos ištrinti negalima'))` |
| `->with('info', 'Lyga ištrinta')` (×2) | `->with('info', __('Lyga ištrinta'))` |
| `->with('error', 'Negalima palikti Bendros lygos')` | `->with('error', __('Negalima palikti Bendros lygos'))` |
| `->with('error', 'Perduokite lygos valdymą kitam nariui prieš išeidami')` | `->with('error', __('Perduokite lygos valdymą kitam nariui prieš išeidami'))` |
| `->with('error', 'Negalima palikti vienintelės lygos')` | `->with('error', __('Negalima palikti vienintelės lygos'))` |
| `->with('info', 'Palikote lygą')` | `->with('info', __('Palikote lygą'))` |

- [ ] **Step 2: Wrap flash messages in `UserProfileController.php`**

| Find | Replace |
|---|---|
| `->with('info', 'Profilis atnaujintas.')` | `->with('info', __('Profilis atnaujintas.'))` |
| `->with('info', 'Pranešimų nustatymai atnaujinti.')` | `->with('info', __('Pranešimų nustatymai atnaujinti.'))` |
| `->with('info', 'Pranešimai išjungti sėkmingai.')` | `->with('info', __('Pranešimai išjungti sėkmingai.'))` |

- [ ] **Step 3: Wrap flash message in `UserSettingController.php`**

| Find | Replace |
|---|---|
| `->with('info','Vartotojo nustatymai pakeisti')` | `->with('info', __('Vartotojo nustatymai pakeisti'))` |

- [ ] **Step 4: Wrap flash message in `Auth/GoogleAuthController.php`**

| Find | Replace |
|---|---|
| `->with('error', 'Google prisijungimas nepavyko. Bandykite dar kartą.')` | `->with('error', __('Google prisijungimas nepavyko. Bandykite dar kartą.'))` |

- [ ] **Step 5: Run all tests**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/LeagueController.php app/Http/Controllers/UserProfileController.php app/Http/Controllers/UserSettingController.php app/Http/Controllers/Auth/GoogleAuthController.php
git commit -m "feat: wrap controller flash messages in __() translation helper"
git push
```
