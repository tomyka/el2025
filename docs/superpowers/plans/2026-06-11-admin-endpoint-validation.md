# Admin Endpoint Validation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Delete dead group/user-group code, add comprehensive admin access tests, and replace scattered `session('admin')` checks with a DB-backed `EnsureIsLevel9Admin` middleware.

**Architecture:** Three sequential tasks — dead code removal first (safe, no behaviour change), then write failing tests for level-9 access (TDD anchor), then implement the new middleware + controller cleanup to make those tests pass. All three middleware classes follow the same DB-query pattern already established by `AdminMiddleware` and `SuperAdminMiddleware`.

**Tech Stack:** Laravel 11, PHP 8.2+, PHPUnit, SQLite (tests)

---

## File Map

| File | Change |
|---|---|
| `app/Http/Controllers/GroupController.php` | **Delete** — no routes, dead code |
| `app/Http/Controllers/UserGroupController.php` | **Delete** — no routes, dead code |
| `app/Models/Group.php` | **Delete** — only used by dead controllers |
| `app/Models/UserGroup.php` | **Delete** — only used by dead controllers |
| `resources/views/admin/groups.blade.php` | **Delete** — references non-existent route |
| `resources/views/admin/usergroups.blade.php` | **Delete** — unreachable, no route |
| `tests/Feature/AdminAccessTest.php` | **Create** — full access matrix tests |
| `app/Http/Middleware/EnsureIsLevel9Admin.php` | **Create** — DB-backed level-9 guard |
| `bootstrap/app.php` | **Modify** — register `level9admin` alias |
| `routes/web.php` | **Modify** — add `level9admin` to `deleteUser` and `admin.leagues.delete` routes |
| `app/Http/Controllers/UserController.php` | **Modify** — remove `session('admin') < 9` check from `deleteUser()` |
| `app/Http/Controllers/LeagueController.php` | **Modify** — remove `session('admin') < 9` check from `adminDelete()` |
| `app/Http/Controllers/MessageController.php` | **Modify** — replace `session('admin') < 9` with DB check in `updateMessage()` |
| `app/Http/Controllers/EventController.php` | **Modify** — replace `session('admin') < 9` with DB check in `updateEvent()` |

---

## Task 1: Delete dead group code

**Files:**
- Delete: `app/Http/Controllers/GroupController.php`
- Delete: `app/Http/Controllers/UserGroupController.php`
- Delete: `app/Models/Group.php`
- Delete: `app/Models/UserGroup.php`
- Delete: `resources/views/admin/groups.blade.php`
- Delete: `resources/views/admin/usergroups.blade.php`

These files are completely unreachable — `GroupController` and `UserGroupController` have no registered routes, the models are only used by those dead controllers, and the views reference a route (`admin.groups`) that does not exist. The app migrated fully to leagues; `session('leagueID')` is the live key.

- [ ] **Step 1: Delete the six files**

```bash
rm app/Http/Controllers/GroupController.php \
   app/Http/Controllers/UserGroupController.php \
   app/Models/Group.php \
   app/Models/UserGroup.php \
   resources/views/admin/groups.blade.php \
   resources/views/admin/usergroups.blade.php
```

- [ ] **Step 2: Run full test suite to confirm no regressions**

```bash
php artisan test
```

Expected: same pass count as before (no test referenced any of these files).

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "chore: delete dead group/user-group code (unrouted, migrated to leagues)"
```

---

## Task 2: Write admin access tests (TDD anchor)

**Files:**
- Create: `tests/Feature/AdminAccessTest.php`

Write the full access matrix test file. Tests 1–5 pass immediately (middleware already exists). Test 6 (`level_9_can_delete_user`) **fails** until Task 3 — this is intentional and drives the implementation.

- [ ] **Step 1: Create `tests/Feature/AdminAccessTest.php`**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
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

    // ── Unauthenticated ───────────────────────────────────────────────

    public function test_unauthenticated_blocked_from_admin(): void
    {
        $this->get(route('admin.index'))->assertRedirect(route('login'));
    }

    public function test_unauthenticated_blocked_from_superadmin(): void
    {
        $this->get(route('admin.users'))->assertRedirect(route('login'));
    }

    // ── Level 0: non-admin ───────────────────────────────────────────

    public function test_level0_blocked_from_admin(): void
    {
        $user = $this->makeUser(0);

        $this->actingAs($user)
            ->withSession(['userID' => $user->id])
            ->get(route('admin.index'))
            ->assertRedirect('/');
    }

    // ── Level 1: basic admin ─────────────────────────────────────────

    public function test_level1_allowed_on_admin_dashboard(): void
    {
        $user = $this->makeUser(1);

        $this->actingAs($user)
            ->withSession(['userID' => $user->id])
            ->get(route('admin.index'))
            ->assertOk();
    }

    public function test_level1_blocked_from_superadmin_routes(): void
    {
        $user = $this->makeUser(1);

        $this->actingAs($user)
            ->withSession(['userID' => $user->id])
            ->get(route('admin.users'))
            ->assertRedirect(route('admin.index'));
    }

    // ── Level 5: superadmin ──────────────────────────────────────────

    public function test_level5_allowed_on_superadmin_routes(): void
    {
        $user = $this->makeUser(5);

        $this->actingAs($user)
            ->withSession(['userID' => $user->id])
            ->get(route('admin.users'))
            ->assertOk();
    }

    public function test_level5_blocked_from_delete_user(): void
    {
        $admin = $this->makeUser(5);
        $target = $this->makeUser(0);

        $this->actingAs($admin)
            ->withSession(['userID' => $admin->id])
            ->post(route('admin.deleteUser'), ['userID' => $target->id])
            ->assertRedirect(route('admin.index'));

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    // ── Level 9: full access ─────────────────────────────────────────

    public function test_level9_can_delete_user(): void
    {
        $admin  = $this->makeUser(9);
        $target = $this->makeUser(0);

        $this->actingAs($admin)
            ->withSession(['userID' => $admin->id])
            ->post(route('admin.deleteUser'), ['userID' => $target->id])
            ->assertRedirect(route('admin.users'));

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }
}
```

- [ ] **Step 2: Run the tests**

```bash
php artisan test tests/Feature/AdminAccessTest.php
```

Expected results:
- `test_unauthenticated_blocked_from_admin` — PASS
- `test_unauthenticated_blocked_from_superadmin` — PASS
- `test_level0_blocked_from_admin` — PASS
- `test_level1_allowed_on_admin_dashboard` — PASS
- `test_level1_blocked_from_superadmin_routes` — PASS
- `test_level5_allowed_on_superadmin_routes` — PASS
- `test_level5_blocked_from_delete_user` — **FAIL** (currently redirects to `admin.users` not `admin.index` because the session check, not middleware, blocks it)
- `test_level9_can_delete_user` — **FAIL** (controller's `session('admin') < 9` blocks the level-9 user because session('admin') is null in tests → null < 9 is true in PHP)

The two failures confirm the implementation gap that Task 3 will fix.

- [ ] **Step 3: Commit the failing tests**

```bash
git add tests/Feature/AdminAccessTest.php
git commit -m "test: add admin access matrix tests (2 failing — drives Task 3)"
```

---

## Task 3: EnsureIsLevel9Admin middleware + controller cleanup

**Files:**
- Create: `app/Http/Middleware/EnsureIsLevel9Admin.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/UserController.php`
- Modify: `app/Http/Controllers/LeagueController.php`
- Modify: `app/Http/Controllers/MessageController.php`
- Modify: `app/Http/Controllers/EventController.php`

- [ ] **Step 1: Create `app/Http/Middleware/EnsureIsLevel9Admin.php`**

```php
<?php

namespace App\Http\Middleware;

use App\Models\UserSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsLevel9Admin
{
    public function handle(Request $request, Closure $next): Response
    {
        $userID = session('userID');

        if (!$userID || !UserSetting::where('user_id', $userID)->where('admin', '>=', 9)->exists()) {
            return redirect()->route('admin.index');
        }

        return $next($request);
    }
}
```

- [ ] **Step 2: Register the alias in `bootstrap/app.php`**

In `bootstrap/app.php`, add `level9admin` to the alias list:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'admin'      => \App\Http\Middleware\AdminMiddleware::class,
        'superadmin' => \App\Http\Middleware\SuperAdminMiddleware::class,
        'level9admin' => \App\Http\Middleware\EnsureIsLevel9Admin::class,
    ]);
})
```

- [ ] **Step 3: Add `level9admin` to the two dedicated delete routes in `routes/web.php`**

In the superadmin route group, update the two routes:

```php
// SuperAdmin (level 5+): everything else
Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'superadmin']], function () {

    Route::get('users', [UserController::class,'getAllUsersFull'])->name('admin.users');
    Route::post('updateUser', [UserController::class,'updateUser'])->name('admin.updateUser');
    Route::post('deleteUser', [UserController::class,'deleteUser'])
        ->middleware('level9admin')
        ->name('admin.deleteUser');

    // ... all other routes unchanged ...

    Route::post('leagues/delete', [LeagueController::class, 'adminDelete'])
        ->middleware('level9admin')
        ->name('admin.leagues.delete');

    // ... rest unchanged ...
});
```

Only `deleteUser` and `leagues/delete` get the extra middleware. All other routes in the group are unchanged.

- [ ] **Step 4: Remove the session check from `UserController::deleteUser()`**

In `app/Http/Controllers/UserController.php`, remove lines:

```php
if (session('admin') < 9) {
    return redirect()->route('admin.users')->with('error', 'Insufficient permissions to delete users.');
}
```

The method after removal starts directly with:

```php
public function deleteUser(Request $request): \Illuminate\Http\RedirectResponse
{
    $userID = (int) $request->input('userID');

    if ($userID === (int) session('userID')) {
        return redirect()->route('admin.users')->with('error', 'Cannot delete your own account.');
    }
    // ... rest of deletion logic unchanged
```

- [ ] **Step 5: Remove the session check from `LeagueController::adminDelete()`**

In `app/Http/Controllers/LeagueController.php`, remove lines:

```php
if (session('admin') < 9) {
    abort(403);
}
```

The method after removal starts directly with:

```php
public function adminDelete(Request $request): \Illuminate\Http\RedirectResponse
{
    $leagueId = (int) $request->input('leagueID');
    $league   = League::findOrFail($leagueId);
    // ... rest unchanged
```

- [ ] **Step 6: Replace session check in `MessageController::updateMessage()`**

In `app/Http/Controllers/MessageController.php`, add import at top:

```php
use App\Models\UserSetting;
```

Then replace:

```php
if (session('admin') < 9) {
    return redirect()->route('admin.messages')->with('error', 'Insufficient permissions to delete.');
}
```

With:

```php
if (!UserSetting::where('user_id', session('userID'))->where('admin', '>=', 9)->exists()) {
    return redirect()->route('admin.messages')->with('error', 'Insufficient permissions to delete.');
}
```

- [ ] **Step 7: Replace session check in `EventController::updateEvent()`**

In `app/Http/Controllers/EventController.php`, add import at top:

```php
use App\Models\UserSetting;
```

Then replace:

```php
if (session('admin') < 9) {
    return redirect()->route('admin.events')->with('error', 'Insufficient permissions to delete.');
}
```

With:

```php
if (!UserSetting::where('user_id', session('userID'))->where('admin', '>=', 9)->exists()) {
    return redirect()->route('admin.events')->with('error', 'Insufficient permissions to delete.');
}
```

- [ ] **Step 8: Run the failing tests to verify they now pass**

```bash
php artisan test tests/Feature/AdminAccessTest.php
```

Expected: all 8 tests pass.

- [ ] **Step 9: Run the full suite**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 10: Commit**

```bash
git add app/Http/Middleware/EnsureIsLevel9Admin.php \
        bootstrap/app.php \
        routes/web.php \
        app/Http/Controllers/UserController.php \
        app/Http/Controllers/LeagueController.php \
        app/Http/Controllers/MessageController.php \
        app/Http/Controllers/EventController.php
git commit -m "fix: add EnsureIsLevel9Admin middleware; replace session checks with DB-backed guards"
```

---

## Final verification

- [ ] **Run full test suite**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Push**

```bash
git push
```
