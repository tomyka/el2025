# Admin Endpoint Validation — Design Spec

**Date:** 2026-06-11
**Scope:** Sub-project C of the broader security audit
**Status:** Approved

---

## Problem

Three issues in the current admin access control:

1. **Dead code from deprecated groups feature** — `GroupController`, `UserGroupController`, `Group` model, `UserGroup` model, and two admin views are completely unrouted and reference each other only. The app migrated fully to leagues; these files are unreachable.

2. **No test coverage** for admin middleware — `AdminMiddleware` and `SuperAdminMiddleware` have no tests. There is no automated verification that the access levels are enforced correctly.

3. **Inconsistent level-9 checks** — Four controllers check `session('admin') < 9` directly, mixing session-trust into controller logic. Two of those routes (`deleteUser`, `adminDelete` for leagues) are dedicated delete endpoints that could be protected by middleware. Two others (`updateMessage`, `updateEvent`) handle mixed update/delete in one route and must stay in-controller, but should use a DB-backed check rather than trusting the session value.

---

## Part 0: Delete Dead Group Code

The following six files are dead code with no active routes, no callers outside each other, and no DB data dependency (the `groups` and `user_groups` tables remain in the DB but are no longer touched by live code):

| File | Reason |
|---|---|
| `app/Http/Controllers/GroupController.php` | No route → unreachable |
| `app/Http/Controllers/UserGroupController.php` | No route → unreachable |
| `app/Models/Group.php` | Only used by GroupController |
| `app/Models/UserGroup.php` | Only used by UserGroupController |
| `resources/views/admin/groups.blade.php` | References non-existent `admin.groups` route |
| `resources/views/admin/usergroups.blade.php` | Unreachable, no route |

Database migrations for `groups` and `user_groups` are **not** deleted — they represent historical schema and rolling them back would risk data loss.

---

## Part A: Admin Middleware Tests

**File:** `tests/Feature/AdminAccessTest.php`

Five scenarios covering the full access matrix:

| Scenario | Route tested | Expected |
|---|---|---|
| Unauthenticated | `GET /admin/index` | Redirect (auth middleware) |
| Level 0 (non-admin) | `GET /admin/index` | Redirect to `/` (AdminMiddleware) |
| Level 1 (admin) | `GET /admin/index` | 200 OK |
| Level 1 (admin) | `GET /admin/users` | Redirect to `admin.index` (SuperAdminMiddleware) |
| Level 5 (superadmin) | `GET /admin/users` | 200 OK |
| Level 9 (superadmin) | `POST /admin/deleteUser` | Not blocked by middleware (EnsureIsLevel9Admin — Part B) |
| Level 5 (superadmin) | `POST /admin/deleteUser` | Redirect to `admin.index` (EnsureIsLevel9Admin — Part B) |

Tests create users with specific `user_settings.admin` values via the `UserSetting` model directly, then call routes with `actingAs()`.

---

## Part B: Standardize Level-9 Checks

### New middleware: `EnsureIsLevel9Admin`

**File:** `app/Http/Middleware/EnsureIsLevel9Admin.php`

Same pattern as `SuperAdminMiddleware` — DB-backed check, no session dependency:

```php
public function handle(Request $request, Closure $next): Response
{
    $userID = session('userID');

    if (!$userID || !UserSetting::where('user_id', $userID)->where('admin', '>=', 9)->exists()) {
        return redirect()->route('admin.index');
    }

    return $next($request);
}
```

Register alias `level9admin` in `bootstrap/app.php`.

### Routes receiving the new middleware

Two dedicated delete routes currently inside the `superadmin` group move into a new `level9admin` group (still under `auth`):

| Route | Before | After |
|---|---|---|
| `POST /admin/deleteUser` | `['auth', 'superadmin']` | `['auth', 'superadmin', 'level9admin']` |
| `POST /admin/leagues/delete` | `['auth', 'superadmin']` | `['auth', 'superadmin', 'level9admin']` |

Both routes remain in the superadmin group; `level9admin` is added as an additional layer, not a replacement.

### Controller changes

`UserController::deleteUser()` (line 49) and `LeagueController::adminDelete()` (line 228) — remove the `session('admin') < 9` check entirely. The new middleware makes it redundant.

`MessageController::updateMessage()` (line 46) and `EventController::updateEvent()` (line 32) — replace `session('admin') < 9` with a direct DB check:

```php
if (!UserSetting::where('user_id', session('userID'))->where('admin', '>=', 9)->exists()) {
    return redirect()->route('admin.index');
}
```

Add `use App\Models\UserSetting;` import to both controllers.

---

## Access Level Matrix (after changes)

| Admin level | `/admin/*` | `/admin/users`, `/admin/teams` etc | DELETE actions |
|---|---|---|---|
| 0 | ❌ blocked | ❌ blocked | ❌ blocked |
| 1–4 | ✅ allowed | ❌ blocked | ❌ blocked |
| 5–8 | ✅ allowed | ✅ allowed | ❌ blocked |
| 9 | ✅ allowed | ✅ allowed | ✅ allowed |

All checks DB-backed at the middleware layer. No session-trust for access decisions.

---

## Out of Scope

- Admin UI/UX changes
- Audit logging of admin actions (Sub-project D)
- Code quality refactor (Sub-project D)
