# Audit Logging Design

## Overview

Add a reliable audit/logging framework to track user logins and prediction changes. The existing infrastructure is partially broken (login controller has wrong namespace and is never called; prediction audit exists but lacks old-value capture). This design fixes both and adds an admin UI to browse the data.

## Scope

- Fix and extend login audit tracking (IP address + login method)
- Fix prediction change audit to capture old and new values
- Admin UI: global audit log page + per-user drill-down from users list
- Accessible to Editor (`admin >= 5`) and Superadmin (`admin >= 9`)

---

## Section 1: Database Changes

### Migration 1 — Extend `audit_logins`

Add two columns to the existing `audit_logins` table:

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `ip_address` | string | yes | Request IP at login time |
| `login_method` | string | yes | `'email'` or `'google'` |

The existing `user_id` (string) and `timestamps` columns are unchanged.

### Migration 2 — Extend `audit_prediction_games`

Add three columns to the existing `audit_prediction_games` table:

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `old_home_team_score` | smallInteger | yes | Score before this change; null on first prediction |
| `old_away_team_score` | smallInteger | yes | Score before this change; null on first prediction |
| `old_game_winner_id` | smallInteger | yes | Winner before this change; null on first prediction |

The existing `user_id`, `game_id`, `home_team_score`, `away_team_score`, `game_winner_id`, and `timestamps` columns are unchanged — they continue to record the **new** (submitted) values.

---

## Section 2: Controller Fixes and Wiring

### Fix `AuditLoginsController`

Current problems:
- Namespace is `App\Http\Controllers\Audit` — wrong, file lives in `App\Http\Controllers`
- Model import is `App\AuditLogin` — wrong, should be `App\Models\AuditLogin`
- Saves to `userID` field — should be `user_id`
- No IP address or login method parameters

Fixed signature:
```php
namespace App\Http\Controllers;

use App\Models\AuditLogin;

class AuditLoginsController extends Controller
{
    public function insertAuditLogin(int $userID, string $ipAddress, string $loginMethod): void
    {
        $auditLogin = new AuditLogin();
        $auditLogin->user_id = $userID;
        $auditLogin->ip_address = $ipAddress;
        $auditLogin->login_method = $loginMethod;
        $auditLogin->save();
    }
}
```

### Wire Login Tracking

**`AuthenticatedSessionController::store()`** — after `$request->authenticate()`:
```php
$user = Auth::user();
(new AuditLoginsController())->insertAuditLogin($user->id, $request->ip(), 'email');
```

**`GoogleAuthController::callback()`** — the method signature must be updated to accept `Request $request` (it currently has no parameters). After each `Auth::login($user)` call (all three code paths: existing google_id, email match, new registration):
```php
public function callback(Request $request)
{
    // ... existing code ...
    (new AuditLoginsController())->insertAuditLogin($user->id, $request->ip(), 'google');
    // ...
}
```

### Fix `AuditPredictionGameController`

Updated signature to accept old values:
```php
public function insertAuditPredictionGame(
    int $userID, int $gameID,
    $homeTeamScore, $awayTeamScore, $gameWinnerID,
    $oldHomeTeamScore, $oldAwayTeamScore, $oldGameWinnerId
): void
```

Saves all eight fields to the table.

### Update Call Site in `PredictionResultController`

In `updatePredictionResultUser()`, before calling `$predictionResult->save()`, capture current values:
```php
$oldHomeScore    = $predictionResult->home_team_score;
$oldAwayScore    = $predictionResult->away_team_score;
$oldGameWinnerId = $predictionResult->game_winner_id;
```

Then pass them to `insertAuditPredictionGame()`.

---

## Section 3: Admin UI

### Global Audit Page

**Route:** `GET /admin/audit` → `admin.audit`
**Middleware:** `SuperAdminMiddleware` (requires `admin >= 5`)
**Controller:** `AuditController::index()`

The page has two sections:

**Login History table** — columns: Username, Method (email/Google badge), IP Address, Date/Time. Paginated (25/page). Optional `?user=X` query string filters to one user.

**Prediction Changes table** — columns: Username, Game (Home vs Away), Old Score, New Score, Date/Time. Paginated (25/page). Same `?user=X` filter applies to both sections.

Both sections join to the `users` table to resolve usernames.

### Per-User Link from Users Admin Page

On `admin/users.blade.php`, each user row gets an "Audit" icon link (Bootstrap icon `bi-clock-history`) pointing to `/admin/audit?user={id}`. The link is only rendered when `session('admin') >= 5`.

### Admin Dashboard Tile

New tile in `admin/index.blade.php` `$sections` array:
```php
['icon' => 'bi-clock-history', 'label' => 'Auditas', 'route' => 'admin.audit', 'super' => true]
```

`super: true` means it renders only for `admin >= 5`, consistent with the existing dashboard logic.

---

## Files Changed

| File | Action |
|---|---|
| `database/migrations/YYYY_MM_DD_audit_logins_add_columns.php` | Create |
| `database/migrations/YYYY_MM_DD_audit_prediction_games_add_old_columns.php` | Create |
| `app/Http/Controllers/AuditLoginsController.php` | Modify (fix namespace, import, field, add params) |
| `app/Http/Controllers/AuditPredictionGameController.php` | Modify (add old value params) |
| `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | Modify (wire login audit) |
| `app/Http/Controllers/Auth/GoogleAuthController.php` | Modify (wire login audit, add Request param) |
| `app/Http/Controllers/PredictionResultController.php` | Modify (pass old values to audit) |
| `app/Http/Controllers/AuditController.php` | Create |
| `app/Models/AuditLogin.php` | Modify (add fillable) |
| `app/Models/AuditPredictionGame.php` | Modify (add fillable) |
| `resources/views/admin/audit.blade.php` | Create |
| `resources/views/admin/users.blade.php` | Modify (add audit link) |
| `resources/views/admin/index.blade.php` | Modify (add tile) |
| `routes/web.php` | Modify (add admin.audit route) |

---

## Access Control

| Role | admin value | Can access `/admin/audit` |
|---|---|---|
| Regular user | 0 | No |
| Basic admin | 1 | No |
| Editor | 5 | Yes |
| Superadmin | 9 | Yes |

Enforced via `SuperAdminMiddleware` on the route, and `super: true` on the dashboard tile.

---

## Testing

- Login audit records are created on successful email and Google login
- Login audit captures correct IP and method
- Prediction audit records old + new values on change; old values are null on first prediction
- Admin audit page loads for `admin >= 5`
- Admin audit page returns 403/redirect for `admin < 5`
- `?user=X` filter correctly scopes both sections
- Audit link appears on users list for `admin >= 5`, hidden for `admin < 5`
