# Design: Repository Restructure & Local Environment Setup

**Date:** 2026-06-04
**Scope:** Laravel Sail migration + targeted security fixes (SQL injection, admin middleware, Form Requests)
**Approach:** Infrastructure first, then code fixes

---

## Goals

1. Replace the current custom Docker setup with Laravel Sail (MySQL + Redis).
2. Delete committed binary MySQL data files from version control.
3. Fix SQL injection vulnerabilities in raw query methods.
4. Add admin route protection via middleware.
5. Replace inline validation with Form Request classes on prediction and result endpoints.

## Out of Scope

- Service layer extraction or architectural refactoring
- Replacing raw SQL with Eloquent/Query Builder
- Frontend changes
- Deployment/production infrastructure

---

## Phase 1 — Infrastructure (Laravel Sail)

### What is removed

- `docker/` directory deleted from the repository entirely (includes committed binary MySQL data files in `docker/mysql/data/`, Nginx configs, PHP Dockerfile, `www.conf`)
- `docker-compose - Copy.yml` deleted
- `docker/` added to `.gitignore`

### What is added

- Sail installed via `php artisan sail:install` selecting **mysql** and **redis**
- Sail generates a new `docker-compose.yml` at the project root

### Environment configuration

`.env` updated for Sail defaults:

```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
REDIS_HOST=redis
APP_URL=http://localhost
```

`.env.testing` created for test isolation:

```
APP_ENV=testing
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=testing
DB_USERNAME=sail
DB_PASSWORD=password
```

The `testing` database must be created inside the MySQL container once Sail is running:

```bash
./vendor/bin/sail mysql -e "CREATE DATABASE IF NOT EXISTS testing;"
```

### Daily workflow

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan migrate --env=testing
./vendor/bin/sail test
./vendor/bin/sail down
```

---

## Phase 2 — Targeted Code Fixes

### 2a. SQL Injection — parameterize raw queries

All affected methods use string concatenation to embed session values and parameters into `DB::select()` and `DB::statement()` calls. Each is changed to use positional `?` PDO bindings.

**Affected methods:**

| Controller | Method |
|---|---|
| `PredictionResultController` | `getPredictionResultsUserGroupEventDay` |
| `PredictionResultController` | `getPredictionGamesUserHistory` |
| `PredictionResultController` | `getPredictionGamesProfile` |
| `PredictionResultController` | `getPredictionGamesUserResultAmount` |
| `PredictionStandingController` | `getPredictionStandingProfile` |
| `PredictionStandingController` | `getPredictionStandingTop4` |
| `PointController` | `getPredictionStandingsUserPoints` |
| `PointResultController` | `deletePointResultGamePoints` |

**Pattern:**

```php
// Before
DB::select('... WHERE user_id=' . $userID . ' AND group_id=' . $groupID);

// After
DB::select('... WHERE user_id = ? AND group_id = ?', [$userID, $groupID]);
```

`session('timeDifference')` embedded in INTERVAL expressions is also moved to a binding. `LIMIT` clauses that embed `$resultAmount` are replaced with a binding as well.

### 2b. Admin Middleware

**New file:** `app/Http/Middleware/AdminMiddleware.php`

```php
public function handle(Request $request, Closure $next): Response
{
    if (!session('admin')) {
        return redirect('/');
    }
    return $next($request);
}
```

**Registration** in `bootstrap/app.php` using Laravel 11's `withMiddleware`:

```php
$middleware->alias(['admin' => \App\Http\Middleware\AdminMiddleware::class]);
```

**Applied** to the `admin` route group in `routes/web.php`:

```php
Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'admin']], function () { ... });
```

Both `auth` and `admin` middleware are applied — `auth` ensures the user is logged in, `admin` ensures they have admin privileges.

### 2c. Form Request Classes

Three new Form Request classes replace inline validation.

**`app/Http/Requests/UpdatePredictionResultRequest.php`**
- `homeTeamScore`: `nullable|integer|min:50|max:150`
- `awayTeamScore`: `nullable|integer|min:50|max:150`
- `gameWinnerID`: `nullable|integer`
- `gameID`: `required|integer`
- `prediction_gameID`: `required|integer`

Replaces the `Factory $validator` pattern in `PredictionResultController::updatePredictionResultUser()`.

**`app/Http/Requests/UpdatePredictionStandingRequest.php`**
- `prediction_standingID`: `required|integer`
- `groupPosition`: `nullable|integer|min:1`
- `quarterfinal`: `nullable|integer|min:1`
- `semifinal`: `nullable|integer|min:1`
- `final`: `nullable|integer|min:1|max:4`

Replaces the unvalidated direct model assignment in `PredictionStandingController::updatePredictionStandingsUser()`.

**`app/Http/Requests/UpdateResultRequest.php`**
- `gameID`: `required|integer`
- `homeTeamScore`: `nullable|integer|min:0`
- `awayTeamScore`: `nullable|integer|min:0`

Replaces direct `$request->input()` usage in `ResultController::updateResult()`.

---

## Files Changed Summary

| Action | Path |
|---|---|
| Delete | `docker/` (entire directory) |
| Delete | `docker-compose - Copy.yml` |
| Modify | `.gitignore` |
| Add | `docker-compose.yml` (Sail-generated) |
| Modify | `.env` |
| Add | `.env.testing` |
| Modify | `app/Http/Controllers/PredictionResultController.php` |
| Modify | `app/Http/Controllers/PredictionStandingController.php` |
| Modify | `app/Http/Controllers/PointController.php` |
| Modify | `app/Http/Controllers/PointResultController.php` |
| Add | `app/Http/Middleware/AdminMiddleware.php` |
| Modify | `bootstrap/app.php` |
| Modify | `routes/web.php` |
| Add | `app/Http/Requests/UpdatePredictionResultRequest.php` |
| Add | `app/Http/Requests/UpdatePredictionStandingRequest.php` |
| Add | `app/Http/Requests/UpdateResultRequest.php` |
