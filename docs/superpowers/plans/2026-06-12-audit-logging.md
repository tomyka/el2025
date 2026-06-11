# Audit Logging Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix the broken audit logging infrastructure, extend it to capture IP/method on login and old+new values on prediction changes, and expose a paginated admin UI accessible to Editor (admin ≥ 5) and Superadmin (admin = 9).

**Architecture:** Fix two broken audit controllers (wrong namespace, missing fields), add new columns via two additive migrations, wire tracking into auth controllers and the prediction update flow, then build a single `AuditController` serving a tabbed admin view with `?user=X` filtering. All existing patterns are followed — controllers instantiate other controllers directly.

**Tech Stack:** Laravel 11, PHP 8.2, Blade/Bootstrap 5, Carbon, SQLite (tests), MySQL (production)

---

## File Map

| File | Action |
|---|---|
| `database/migrations/2026_06_12_000000_add_columns_to_audit_logins.php` | Create |
| `database/migrations/2026_06_12_000001_add_old_scores_to_audit_prediction_games.php` | Create |
| `app/Models/AuditLogin.php` | Modify — fix fillable |
| `app/Http/Controllers/AuditLoginsController.php` | Modify — fix namespace, import, field, add params |
| `app/Models/AuditPredictionGame.php` | Modify — fix fillable |
| `app/Http/Controllers/AuditPredictionGameController.php` | Modify — add old value params |
| `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | Modify — call login audit |
| `app/Http/Controllers/Auth/GoogleAuthController.php` | Modify — call login audit, add Request param |
| `app/Http/Controllers/PredictionResultController.php` | Modify — capture and pass old values |
| `app/Http/Controllers/AuditController.php` | Create |
| `resources/views/admin/audit.blade.php` | Create |
| `routes/web.php` | Modify — add admin.audit route |
| `resources/views/admin/index.blade.php` | Modify — add Auditas tile |
| `resources/views/admin/users.blade.php` | Modify — add audit link per row |
| `tests/Feature/AuditLoginTest.php` | Create |
| `tests/Feature/AuditPredictionTest.php` | Create |
| `tests/Feature/AuditAccessTest.php` | Create |

---

### Task 1: Database migrations

**Files:**
- Create: `database/migrations/2026_06_12_000000_add_columns_to_audit_logins.php`
- Create: `database/migrations/2026_06_12_000001_add_old_scores_to_audit_prediction_games.php`

**Context:** The `audit_logins` table currently has only `id`, `user_id` (string), `timestamps`. The `audit_prediction_games` table has `id`, `user_id`, `game_id`, `home_team_score`, `away_team_score`, `game_winner_id`, `timestamps`. Both tables exist — these are additive column additions only.

- [ ] **Step 1: Create audit_logins migration**

Create `database/migrations/2026_06_12_000000_add_columns_to_audit_logins.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logins', function (Blueprint $table) {
            $table->string('ip_address')->nullable()->after('user_id');
            $table->string('login_method')->nullable()->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logins', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'login_method']);
        });
    }
};
```

- [ ] **Step 2: Create audit_prediction_games migration**

Create `database/migrations/2026_06_12_000001_add_old_scores_to_audit_prediction_games.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_prediction_games', function (Blueprint $table) {
            $table->smallInteger('old_home_team_score')->nullable()->after('game_winner_id');
            $table->smallInteger('old_away_team_score')->nullable()->after('old_home_team_score');
            $table->smallInteger('old_game_winner_id')->nullable()->after('old_away_team_score');
        });
    }

    public function down(): void
    {
        Schema::table('audit_prediction_games', function (Blueprint $table) {
            $table->dropColumn(['old_home_team_score', 'old_away_team_score', 'old_game_winner_id']);
        });
    }
};
```

- [ ] **Step 3: Run migrations**

```bash
php artisan migrate
```

Expected output includes two `Migrating:` then `Migrated:` lines for the two new files.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_06_12_000000_add_columns_to_audit_logins.php database/migrations/2026_06_12_000001_add_old_scores_to_audit_prediction_games.php
git commit -m "feat: add ip_address/login_method to audit_logins and old score columns to audit_prediction_games"
```

---

### Task 2: Fix AuditLoginsController + AuditLogin model

**Files:**
- Modify: `app/Http/Controllers/AuditLoginsController.php`
- Modify: `app/Models/AuditLogin.php`

**Context:** The current controller is broken in three ways — namespace is `App\Http\Controllers\Audit` (wrong), model import is `App\AuditLogin` (should be `App\Models\AuditLogin`), and it saves to `userID` instead of `user_id`. It is never called anywhere, so fixing it won't break existing tests.

- [ ] **Step 1: Replace AuditLoginsController**

Overwrite `app/Http/Controllers/AuditLoginsController.php` entirely:

```php
<?php

namespace App\Http\Controllers;

use App\Models\AuditLogin;

class AuditLoginsController extends Controller
{
    public function insertAuditLogin(int $userID, string $ipAddress, string $loginMethod): void
    {
        $auditLogin = new AuditLogin();
        $auditLogin->user_id      = $userID;
        $auditLogin->ip_address   = $ipAddress;
        $auditLogin->login_method = $loginMethod;
        $auditLogin->save();
    }
}
```

- [ ] **Step 2: Update AuditLogin model**

Overwrite `app/Models/AuditLogin.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLogin extends Model
{
    protected $fillable = ['user_id', 'ip_address', 'login_method'];
}
```

- [ ] **Step 3: Run full test suite**

```bash
php artisan test
```

Expected: all existing tests pass (nothing called the old broken controller).

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/AuditLoginsController.php app/Models/AuditLogin.php
git commit -m "fix: correct AuditLoginsController namespace/import and add ip/method fields"
```

---

### Task 3: Fix AuditPredictionGameController + AuditPredictionGame model

**Files:**
- Modify: `app/Http/Controllers/AuditPredictionGameController.php`
- Modify: `app/Models/AuditPredictionGame.php`

**Context:** This controller IS actively called from `PredictionResultController::updatePredictionResultUser()` at line 95 with 5 arguments. After this task it will expect 8. The call site passes the wrong number of arguments until Task 6 fixes it — any test that exercises that code path will throw `ArgumentCountError`. Check whether existing tests hit that path; if so, they will fail until Task 6.

- [ ] **Step 1: Update AuditPredictionGameController**

Overwrite `app/Http/Controllers/AuditPredictionGameController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\AuditPredictionGame;

class AuditPredictionGameController extends Controller
{
    public function insertAuditPredictionGame(
        int $userID,
        int $gameID,
        $homeTeamScore,
        $awayTeamScore,
        $gameWinnerID,
        $oldHomeTeamScore,
        $oldAwayTeamScore,
        $oldGameWinnerId
    ): void {
        $audit = new AuditPredictionGame();
        $audit->user_id             = $userID;
        $audit->game_id             = $gameID;
        $audit->home_team_score     = $homeTeamScore;
        $audit->away_team_score     = $awayTeamScore;
        $audit->game_winner_id      = $gameWinnerID;
        $audit->old_home_team_score = $oldHomeTeamScore;
        $audit->old_away_team_score = $oldAwayTeamScore;
        $audit->old_game_winner_id  = $oldGameWinnerId;
        $audit->save();
    }
}
```

- [ ] **Step 2: Update AuditPredictionGame model**

Overwrite `app/Models/AuditPredictionGame.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditPredictionGame extends Model
{
    protected $fillable = [
        'user_id', 'game_id',
        'home_team_score', 'away_team_score', 'game_winner_id',
        'old_home_team_score', 'old_away_team_score', 'old_game_winner_id',
    ];
}
```

- [ ] **Step 3: Run full test suite**

```bash
php artisan test
```

Expected: all tests pass. The broken call at `PredictionResultController:95` is only reached via POST to `prediction/results` — verify no existing test hits that path. If a test fails with `ArgumentCountError`, it means an existing test exercises the prediction update flow and must be temporarily noted as expected until Task 6.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/AuditPredictionGameController.php app/Models/AuditPredictionGame.php
git commit -m "feat: extend AuditPredictionGameController to accept and store old prediction values"
```

---

### Task 4: Wire email login audit + test

**Files:**
- Modify: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- Create: `tests/Feature/AuditLoginTest.php`

**Context:** `AuthenticatedSessionController::store()` currently lives at `app/Http/Controllers/Auth/AuthenticatedSessionController.php`. After `$request->authenticate()` succeeds, `Auth::id()` returns the authenticated user's ID. `$request->ip()` returns `'127.0.0.1'` in tests.

The `audit_logins.user_id` column is type `string` in the database (defined in the original migration as `$table->string('user_id')`). When asserting in tests, cast the user ID to string: `(string) $user->id`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/AuditLoginTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_login_creates_audit_record(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $this->post('/login', [
            'email'    => $user->email,
            'password' => 'secret123',
        ]);

        $this->assertDatabaseHas('audit_logins', [
            'user_id'      => (string) $user->id,
            'login_method' => 'email',
            'ip_address'   => '127.0.0.1',
        ]);
    }

    public function test_failed_login_does_not_create_audit_record(): void
    {
        User::factory()->create([
            'email'    => 'test@example.com',
            'password' => bcrypt('correct'),
        ]);

        $this->post('/login', [
            'email'    => 'test@example.com',
            'password' => 'wrong',
        ]);

        $this->assertDatabaseCount('audit_logins', 0);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Feature/AuditLoginTest.php
```

Expected: FAIL — `test_email_login_creates_audit_record` fails with "Failed asserting that a row in the table [audit_logins] matches the attributes" (no row created yet).

- [ ] **Step 3: Update AuthenticatedSessionController**

Overwrite `app/Http/Controllers/Auth/AuthenticatedSessionController.php`:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\AuditLoginsController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        (new AuditLoginsController())->insertAuditLogin(
            Auth::id(),
            $request->ip(),
            'email'
        );

        return redirect()->intended(route('main', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test tests/Feature/AuditLoginTest.php
```

Expected: PASS (2 tests, 2 assertions).

- [ ] **Step 5: Run full test suite**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Auth/AuthenticatedSessionController.php tests/Feature/AuditLoginTest.php
git commit -m "feat: record audit entry on email login"
```

---

### Task 5: Wire Google login audit

**Files:**
- Modify: `app/Http/Controllers/Auth/GoogleAuthController.php`

**Context:** The callback method has three code paths that each call `Auth::login($user)` — existing google_id match, email match (updates google_id), and new registration. All three get an audit entry. The method currently has no `Request` parameter; it must be added. No automated test is written here because Socialite requires complex mocking — the `AuditLoginsController` mechanism is already verified in Task 4.

- [ ] **Step 1: Update GoogleAuthController**

Overwrite `app/Http/Controllers/Auth/GoogleAuthController.php`:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\AuditLoginsController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PostRegisterController;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    use ChecksRegistrationDeadline;

    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('/')->with('error', 'Google prisijungimas nepavyko. Bandykite dar kartą.');
        }

        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            Auth::login($user);
            (new AuditLoginsController())->insertAuditLogin($user->id, $request->ip(), 'google');
            return redirect()->route('main');
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            $user->update(['google_id' => $googleUser->getId()]);
            Auth::login($user);
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

        Auth::login($user);
        (new AuditLoginsController())->insertAuditLogin($user->id, $request->ip(), 'google');
        return redirect()->route('main');
    }
}
```

- [ ] **Step 2: Run full test suite**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Auth/GoogleAuthController.php
git commit -m "feat: record audit entry on Google login"
```

---

### Task 6: Capture old prediction values + test

**Files:**
- Modify: `app/Http/Controllers/PredictionResultController.php`
- Create: `tests/Feature/AuditPredictionTest.php`

**Context:** `updatePredictionResultUser()` is at lines 72–100 of `PredictionResultController.php`. The `PredictionResult` record is fetched before saving (`firstOrFail()`), so its current field values are the "old" values. The audit call currently passes 5 args — this task extends it to 8. The POST route for predictions is `/prediction/results` — it has no name, but `route('prediction.results')` resolves to the same URL path and POSTing to it hits `updatePredictionResultUser`.

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/AuditPredictionTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\League;
use App\Models\LeagueMember;
use App\Models\PredictionResult;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditPredictionTest extends TestCase
{
    use RefreshDatabase;

    private function setupGame(): array
    {
        $user   = User::factory()->create();
        $league = League::factory()->create();
        LeagueMember::factory()->create(['user_id' => $user->id, 'league_id' => $league->id]);

        $event = Event::create([
            'event'          => 'Test Event',
            'event_day'      => 1,
            'event_survival' => 0,
            'rate'           => 1,
        ]);
        $home = Team::create(['team' => 'Home FC', 'group_name' => 'A']);
        $away = Team::create(['team' => 'Away FC', 'group_name' => 'A']);
        $game = Game::create([
            'game_date'    => now()->addDay(),
            'event_id'     => $event->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
        ]);

        return compact('user', 'game', 'home');
    }

    public function test_prediction_change_records_old_and_new_values(): void
    {
        ['user' => $user, 'game' => $game, 'home' => $home] = $this->setupGame();

        $prediction = PredictionResult::create([
            'user_id'         => $user->id,
            'game_id'         => $game->id,
            'home_team_score' => 1,
            'away_team_score' => 0,
            'game_winner_id'  => $home->id,
        ]);

        $this->actingAs($user)
            ->withSession(['userID' => $user->id])
            ->postJson(route('prediction.results'), [
                'gameID'            => $game->id,
                'prediction_gameID' => $prediction->id,
                'homeTeamScore'     => 3,
                'awayTeamScore'     => 1,
                'gameWinnerID'      => $home->id,
            ]);

        $this->assertDatabaseHas('audit_prediction_games', [
            'user_id'             => $user->id,
            'game_id'             => $game->id,
            'old_home_team_score' => 1,
            'old_away_team_score' => 0,
            'home_team_score'     => 3,
            'away_team_score'     => 1,
        ]);
    }

    public function test_first_prediction_has_null_old_values(): void
    {
        ['user' => $user, 'game' => $game, 'home' => $home] = $this->setupGame();

        $prediction = PredictionResult::create([
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);

        $this->actingAs($user)
            ->withSession(['userID' => $user->id])
            ->postJson(route('prediction.results'), [
                'gameID'            => $game->id,
                'prediction_gameID' => $prediction->id,
                'homeTeamScore'     => 2,
                'awayTeamScore'     => 1,
                'gameWinnerID'      => $home->id,
            ]);

        $this->assertDatabaseHas('audit_prediction_games', [
            'user_id'             => $user->id,
            'game_id'             => $game->id,
            'old_home_team_score' => null,
            'old_away_team_score' => null,
            'home_team_score'     => 2,
            'away_team_score'     => 1,
        ]);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test tests/Feature/AuditPredictionTest.php
```

Expected: FAIL — `ArgumentCountError` because `insertAuditPredictionGame` now expects 8 arguments but the call site still passes 5.

- [ ] **Step 3: Update updatePredictionResultUser in PredictionResultController**

In `app/Http/Controllers/PredictionResultController.php`, replace the `updatePredictionResultUser` method (lines 72–100):

```php
public function updatePredictionResultUser(UpdatePredictionResultRequest $request)
{
    $userID = session('userID');
    $gameID = $request->input('gameID');
    $homeTeamScore = $request->input('homeTeamScore');
    $awayTeamScore = $request->input('awayTeamScore');
    $gameWinnerID = $request->input('gameWinnerID');

    $game = Game::where('id', $gameID)->first();
    $now = Carbon::now('UTC')->format('Y-m-d H:i:s');

    if ($game->game_date > $now) {
        $predictionResult = PredictionResult::where('id', $request->input('prediction_gameID'))
            ->where('user_id', $userID)
            ->firstOrFail();

        $oldHomeScore    = $predictionResult->home_team_score;
        $oldAwayScore    = $predictionResult->away_team_score;
        $oldGameWinnerId = $predictionResult->game_winner_id;

        $predictionResult->home_team_score = (($homeTeamScore == "") ? null : $homeTeamScore);
        $predictionResult->away_team_score = (($awayTeamScore == "") ? null : $awayTeamScore);
        $predictionResult->game_winner_id = (($gameWinnerID == "") ? null : $gameWinnerID);
        $predictionResult->generated = 0;
        $predictionResult->save();

        if ($homeTeamScore != "" && $awayTeamScore != "") {
            $auditPredictionGameController = new AuditPredictionGameController();
            $auditPredictionGameController->insertAuditPredictionGame(
                $userID, $gameID,
                $homeTeamScore, $awayTeamScore, $gameWinnerID,
                $oldHomeScore, $oldAwayScore, $oldGameWinnerId
            );
        }
    }

    return response()->json(['success' => true]);
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
php artisan test tests/Feature/AuditPredictionTest.php
```

Expected: PASS (2 tests, 2 assertions).

- [ ] **Step 5: Run full test suite**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/PredictionResultController.php tests/Feature/AuditPredictionTest.php
git commit -m "feat: capture old prediction values in audit log on each change"
```

---

### Task 7: AuditController + route + dashboard tile + access control test

**Files:**
- Create: `app/Http/Controllers/AuditController.php`
- Create: `resources/views/admin/audit.blade.php` (stub — full view in Task 8)
- Modify: `routes/web.php`
- Modify: `resources/views/admin/index.blade.php`
- Create: `tests/Feature/AuditAccessTest.php`

**Context:** The `superadmin` middleware (`app/Http/Middleware/SuperAdminMiddleware.php`) checks `UserSetting::where('admin', '>=', 5)` — this covers Editor (5) and Superadmin (9). The route belongs in the existing `['prefix' => 'admin', 'middleware' => ['auth', 'superadmin']]` group in `routes/web.php`. The `audit_logins.user_id` column is a string; use `leftJoin` so orphaned rows (deleted users) don't disappear from the log.

- [ ] **Step 1: Write access control tests**

Create `tests/Feature/AuditAccessTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(int $adminLevel): User
    {
        $user = User::factory()->create();
        $setting = new UserSetting();
        $setting->user_id = $user->id;
        $setting->admin = $adminLevel;
        $setting->save();
        return $user;
    }

    public function test_unauthenticated_blocked_from_audit(): void
    {
        $this->get(route('admin.audit'))->assertRedirect(route('login'));
    }

    public function test_level1_blocked_from_audit(): void
    {
        $user = $this->makeUser(1);
        $this->actingAs($user)
            ->withSession(['userID' => $user->id])
            ->get(route('admin.audit'))
            ->assertRedirect(route('admin.index'));
    }

    public function test_level5_allowed_on_audit(): void
    {
        $user = $this->makeUser(5);
        $this->actingAs($user)
            ->withSession(['userID' => $user->id])
            ->get(route('admin.audit'))
            ->assertOk();
    }

    public function test_level9_allowed_on_audit(): void
    {
        $user = $this->makeUser(9);
        $this->actingAs($user)
            ->withSession(['userID' => $user->id])
            ->get(route('admin.audit'))
            ->assertOk();
    }

    public function test_user_filter_returns_ok(): void
    {
        $admin  = $this->makeUser(5);
        $target = $this->makeUser(0);

        $this->actingAs($admin)
            ->withSession(['userID' => $admin->id])
            ->get(route('admin.audit', ['user' => $target->id]))
            ->assertOk();
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test tests/Feature/AuditAccessTest.php
```

Expected: FAIL — route `admin.audit` does not exist yet.

- [ ] **Step 3: Create AuditController**

Create `app/Http/Controllers/AuditController.php`:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(): View
    {
        $userFilter = request()->integer('user', 0) ?: null;

        $loginsQuery = DB::table('audit_logins')
            ->leftJoin('users', 'audit_logins.user_id', '=', 'users.id')
            ->select('audit_logins.*', 'users.username')
            ->orderBy('audit_logins.created_at', 'desc');

        if ($userFilter) {
            $loginsQuery->where('audit_logins.user_id', (string) $userFilter);
        }

        $logins = $loginsQuery->paginate(25, ['*'], 'logins_page')
            ->appends(array_filter(['user' => $userFilter]));

        $predictionsQuery = DB::table('audit_prediction_games')
            ->leftJoin('users', 'audit_prediction_games.user_id', '=', 'users.id')
            ->leftJoin('games', 'audit_prediction_games.game_id', '=', 'games.id')
            ->leftJoin('teams as ht', 'games.home_team_id', '=', 'ht.id')
            ->leftJoin('teams as at', 'games.away_team_id', '=', 'at.id')
            ->select(
                'audit_prediction_games.*',
                'users.username',
                'ht.team as home_team',
                'at.team as away_team'
            )
            ->orderBy('audit_prediction_games.created_at', 'desc');

        if ($userFilter) {
            $predictionsQuery->where('audit_prediction_games.user_id', $userFilter);
        }

        $predictions = $predictionsQuery->paginate(25, ['*'], 'predictions_page')
            ->appends(array_filter(['user' => $userFilter]));

        $users = DB::table('users')->orderBy('username')->get(['id', 'username']);

        return view('admin.audit', compact('logins', 'predictions', 'users', 'userFilter'));
    }
}
```

- [ ] **Step 4: Create stub view**

Create `resources/views/admin/audit.blade.php`:

```blade
@extends('admin.layouts.master')
@section('content')
<div class="sb-card">
    <div class="sb-card-title"><i class="bi bi-clock-history sb-card-icon"></i> Auditas</div>
    <p class="text-muted">Įkeliama…</p>
</div>
@endsection
```

- [ ] **Step 5: Add route to web.php**

At the top of `routes/web.php`, add the import with the other `use` statements:

```php
use App\Http\Controllers\AuditController;
```

Inside the superadmin route group (the `['prefix' => 'admin', 'middleware' => ['auth', 'superadmin']]` block), add before the `@deprecated` settings routes:

```php
Route::get('audit', [AuditController::class, 'index'])->name('admin.audit');
```

- [ ] **Step 6: Add Auditas tile to admin dashboard**

In `resources/views/admin/index.blade.php`, add to the end of the `$sections` array (after the `Eigos taškai` entry):

```php
['icon' => 'bi-clock-history', 'label' => 'Auditas', 'route' => 'admin.audit', 'super' => true],
```

The full array after edit:

```php
$sections = [
    ['icon' => 'bi-trophy',          'label' => 'Rezultatai (turas)',  'route' => 'admin.results',               'super' => false],
    ['icon' => 'bi-trophy-fill',     'label' => 'Visi rezultatai',     'route' => 'admin.resultsAll',            'super' => false],
    ['icon' => 'bi-calendar3',       'label' => 'Rungtynės',           'route' => 'admin.games',                 'super' => false],
    ['icon' => 'bi-people-fill',     'label' => 'Vartotojai',          'route' => 'admin.users',                 'super' => true],
    ['icon' => 'bi-flag-fill',       'label' => 'Komandos',            'route' => 'admin.teams',                 'super' => true],
    ['icon' => 'bi-calendar-event',  'label' => 'Turai',               'route' => 'admin.events',                'super' => true],
    ['icon' => 'bi-chat-left-text',  'label' => 'Žinutės',             'route' => 'admin.messages',              'super' => true],
    ['icon' => 'bi-trophy-fill',     'label' => 'Lygos',               'route' => 'admin.leagues',               'super' => false],
    ['icon' => 'bi-bar-chart-fill',  'label' => 'Eigos taškai',        'route' => 'admin.updateStandingPoints',  'super' => true],
    ['icon' => 'bi-clock-history',   'label' => 'Auditas',             'route' => 'admin.audit',                 'super' => true],
];
```

- [ ] **Step 7: Run access control tests**

```bash
php artisan test tests/Feature/AuditAccessTest.php
```

Expected: PASS (5 tests).

- [ ] **Step 8: Run full test suite**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/AuditController.php resources/views/admin/audit.blade.php routes/web.php resources/views/admin/index.blade.php tests/Feature/AuditAccessTest.php
git commit -m "feat: add AuditController, route, dashboard tile, and access control tests"
```

---

### Task 8: Full admin audit view

**Files:**
- Modify: `resources/views/admin/audit.blade.php`

**Context:** Replace the stub from Task 7. The controller passes: `$logins` (LengthAwarePaginator, each row has `username`, `ip_address`, `login_method`, `created_at`), `$predictions` (LengthAwarePaginator, each row has `username`, `home_team`, `away_team`, `old_home_team_score`, `old_away_team_score`, `home_team_score`, `away_team_score`, `created_at`), `$users` (collection of `{id, username}` objects for the filter dropdown), `$userFilter` (int|null). Pagination links are rendered with `{{ $logins->links() }}` — Bootstrap 5 pagination is configured globally in Laravel. Use Bootstrap 5 tabs to separate the two sections.

- [ ] **Step 1: Replace stub with full view**

Overwrite `resources/views/admin/audit.blade.php`:

```blade
@extends('admin.layouts.master')
@section('content')

<div class="sb-card">
    <div class="sb-card-title d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span><i class="bi bi-clock-history sb-card-icon"></i> Auditas</span>
        <form method="GET" action="{{ route('admin.audit') }}" class="d-flex align-items-center gap-2">
            <select name="user" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                <option value="">— Visi vartotojai —</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ $userFilter == $u->id ? 'selected' : '' }}>
                        {{ $u->username }}
                    </option>
                @endforeach
            </select>
            @if($userFilter)
                <a href="{{ route('admin.audit') }}" class="btn btn-sm btn-outline-secondary">✕</a>
            @endif
        </form>
    </div>

    <ul class="nav nav-tabs mb-3" id="auditTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-logins-btn" data-bs-toggle="tab"
                    data-bs-target="#tab-logins" type="button" role="tab">
                <i class="bi bi-box-arrow-in-right"></i> Prisijungimai
                <span class="badge bg-secondary fw-normal ms-1">{{ $logins->total() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-predictions-btn" data-bs-toggle="tab"
                    data-bs-target="#tab-predictions" type="button" role="tab">
                <i class="bi bi-pencil-square"></i> Prognozės
                <span class="badge bg-secondary fw-normal ms-1">{{ $predictions->total() }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="auditTabContent">

        {{-- Login history --}}
        <div class="tab-pane fade show active" id="tab-logins" role="tabpanel">
            @if($logins->isEmpty())
                <p class="text-muted py-3">Prisijungimų įrašų nėra.</p>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-2">
                    <thead class="table-light">
                        <tr>
                            <th>Vartotojas</th>
                            <th>Metodas</th>
                            <th class="d-none d-md-table-cell">IP adresas</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logins as $login)
                        <tr>
                            <td>{{ $login->username ?? '—' }}</td>
                            <td>
                                @if($login->login_method === 'google')
                                    <span class="badge bg-danger">
                                        <i class="bi bi-google"></i> Google
                                    </span>
                                @else
                                    <span class="badge bg-primary">
                                        <i class="bi bi-envelope-fill"></i> El. paštas
                                    </span>
                                @endif
                            </td>
                            <td class="d-none d-md-table-cell text-muted"
                                style="font-family:monospace;font-size:.85rem;">
                                {{ $login->ip_address ?? '—' }}
                            </td>
                            <td style="white-space:nowrap;font-size:.85rem;">
                                {{ \Carbon\Carbon::parse($login->created_at)->format('Y-m-d H:i') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $logins->links() }}
            @endif
        </div>

        {{-- Prediction changes --}}
        <div class="tab-pane fade" id="tab-predictions" role="tabpanel">
            @if($predictions->isEmpty())
                <p class="text-muted py-3">Prognozių pakeitimų nėra.</p>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-2">
                    <thead class="table-light">
                        <tr>
                            <th>Vartotojas</th>
                            <th>Rungtynės</th>
                            <th class="text-end">Sena</th>
                            <th></th>
                            <th>Nauja</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($predictions as $p)
                        <tr>
                            <td>{{ $p->username ?? '—' }}</td>
                            <td style="font-size:.85rem;">
                                {{ $p->home_team ?? '?' }} — {{ $p->away_team ?? '?' }}
                            </td>
                            <td class="text-end text-muted" style="font-size:.85rem;">
                                @if($p->old_home_team_score !== null)
                                    {{ $p->old_home_team_score }} : {{ $p->old_away_team_score }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-muted px-1">→</td>
                            <td style="font-size:.85rem;font-weight:500;">
                                {{ $p->home_team_score }} : {{ $p->away_team_score }}
                            </td>
                            <td style="white-space:nowrap;font-size:.85rem;">
                                {{ \Carbon\Carbon::parse($p->created_at)->format('Y-m-d H:i') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $predictions->links() }}
            @endif
        </div>

    </div>
</div>

@endsection
```

- [ ] **Step 2: Run full test suite**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 3: Commit**

```bash
git add resources/views/admin/audit.blade.php
git commit -m "feat: implement full audit log view with login and prediction tabs"
```

---

### Task 9: Per-user audit link in users list

**Files:**
- Modify: `resources/views/admin/users.blade.php`

**Context:** The users table actions column (the last `<td>` in each `@foreach` row, starting at line 79) currently shows only the delete button for `admin >= 9`. Add an audit icon link for `admin >= 5` in the same cell. The audit link points to `route('admin.audit', ['user' => $user->id])`.

- [ ] **Step 1: Update the actions cell in users.blade.php**

In `resources/views/admin/users.blade.php`, find and replace the actions `<td>` block (lines 79–91):

Old:
```blade
                    <td class="text-center">
                        @if(session('admin') >= 9)
                        <form method="post" action="{{ route('admin.deleteUser') }}"
                              onsubmit="return confirm('Ištrinti vartotoją {{ addslashes($user->username) }}?')">
                            @csrf
                            <input type="hidden" name="userID"   value="{{ $user->id }}">
                            <input type="hidden" name="username" value="{{ $user->username }}">
                            <button type="submit" class="au-action-btn au-action-delete" title="Ištrinti">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        @endif
                    </td>
```

New:
```blade
                    <td class="text-center" style="white-space:nowrap;">
                        @if(session('admin') >= 5)
                        <a href="{{ route('admin.audit', ['user' => $user->id]) }}"
                           class="au-action-btn" title="Auditas" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">
                            <i class="bi bi-clock-history"></i>
                        </a>
                        @endif
                        @if(session('admin') >= 9)
                        <form method="post" action="{{ route('admin.deleteUser') }}"
                              onsubmit="return confirm('Ištrinti vartotoją {{ addslashes($user->username) }}?')"
                              style="display:inline;">
                            @csrf
                            <input type="hidden" name="userID"   value="{{ $user->id }}">
                            <input type="hidden" name="username" value="{{ $user->username }}">
                            <button type="submit" class="au-action-btn au-action-delete" title="Ištrinti">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        @endif
                    </td>
```

- [ ] **Step 2: Run full test suite**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 3: Commit**

```bash
git add resources/views/admin/users.blade.php
git commit -m "feat: add per-user audit history link in admin users list"
```
