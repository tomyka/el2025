# Security Quick Fixes — Design Spec

**Date:** 2026-06-11
**Scope:** Sub-project A of the broader security audit
**Status:** Approved

---

## Problem

Three concrete vulnerabilities identified in the security audit:

1. Unescaped JSON output in `chart.blade.php` allows XSS via user-controlled data.
2. Registration endpoint has no rate limiting — open to bot/spam registration.
3. `PointResultController::recalculateStreaks()` builds a raw SQL CASE statement via string concatenation instead of parameterized bindings.

---

## Fix 1 — XSS in chart.blade.php

### Root cause

`resources/views/summary/chart.blade.php` injects PHP variables directly into inline JavaScript using `{!! !!}` (unescaped Blade):

```blade
const gameLabels = {!! $gameLabels !!};
datasets: {!! $datasets !!}
```

The controller passes these as the output of `json_encode()` without any HTML-escaping flags. The default `json_encode()` passes `<`, `>`, `'`, `"`, and `&` through unescaped. A username or team name containing `</script><script>alert(1)` would execute arbitrary JavaScript in any viewer's browser.

### Fix

In the controller method that prepares `$gameLabels` and `$datasets` (or in a helper), encode with safe flags:

```php
json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
```

These flags replace `<` → `<`, `>` → `>`, `&` → `&`, `'` → `'`, `"` → `"`. The resulting string is safe to inject into inline JavaScript via `{!! !!}`.

The view itself does not change — `{!! !!}` is correct here since the value must be valid JS, not HTML-escaped.

### Files changed

- `app/Http/Controllers/PointTrendController.php` (or whichever controller renders the chart view) — add flags to `json_encode()` calls for `$gameLabels` and `$datasets`.

---

## Fix 2 — Rate limiting on auth routes

### Root cause

`routes/auth.php` has no throttle middleware on registration or password-reset routes. An attacker can submit unlimited requests to:

- `POST /register` — spam user creation
- `GET /register` — scrape the page in bulk
- `POST /forgot-password` — spam password-reset emails to any address
- `POST /reset-password` — brute-force token guessing

Laravel's built-in `throttle` middleware uses the request IP as the key and returns HTTP 429 with a `Retry-After` header when the limit is exceeded.

### Fix

Add throttle middleware to the guest route group in `routes/auth.php`:

| Route | Limit | Reason |
|---|---|---|
| `GET /register` | `throttle:10,1` | Stops page scraping |
| `POST /register` | `throttle:3,1` | 3 attempts/min — tight bot protection |
| `POST /forgot-password` | `throttle:5,1` | Prevents email spam |
| `POST /reset-password` | `throttle:5,1` | Prevents token brute-force |

The existing login route already benefits from `LoginRequest`'s built-in lockout logic; no change needed there.

### Files changed

- `routes/auth.php` — add `->middleware('throttle:3,1')` (etc.) to the relevant routes.

---

## Fix 3 — SQL concatenation in recalculateStreaks()

### Root cause

`app/Http/Controllers/PointResultController.php::recalculateStreaks()` builds a bulk UPDATE using string concatenation:

```php
foreach ($updates as $id => $bonus) {
    $cases .= " WHEN {$id} THEN {$bonus}";
    $ids[]  = $id;
}
$idList = implode(',', $ids);
DB::statement("UPDATE point_results SET streak_bonus = CASE id {$cases} END WHERE id IN ({$idList})");
```

`$id` and `$bonus` come from database query results (integers and floats), so the actual injection surface is minimal. However, the pattern violates parameterized-query discipline and would be dangerous if the value source ever changed.

### Fix

Rewrite using positional `?` bindings. The CASE WHEN structure maps naturally to repeated `WHEN ? THEN ?` placeholders:

```php
$whenClauses     = implode(' ', array_fill(0, count($updates), 'WHEN ? THEN ?'));
$inPlaceholders  = implode(',',  array_fill(0, count($ids),    '?'));

$bindings = [];
foreach ($updates as $id => $bonus) {
    $bindings[] = (int)   $id;
    $bindings[] = (float) $bonus;
}
foreach ($ids as $id) {
    $bindings[] = (int) $id;
}

DB::statement(
    "UPDATE point_results SET streak_bonus = CASE id {$whenClauses} END WHERE id IN ({$inPlaceholders})",
    $bindings
);
```

Behaviour is identical; all values are now passed through PDO's prepared-statement path.

### Files changed

- `app/Http/Controllers/PointResultController.php` — `recalculateStreaks()` method only.

---

## Testing

| Fix | Test approach |
|---|---|
| XSS | Unit: assert `json_encode` output for a name containing `<script>` produces `<script>`. Manual: load chart page with a test username containing angle brackets. |
| Rate limiting | Feature: assert `POST /register` returns HTTP 429 after 3 rapid requests from the same IP. |
| SQL parameterization | Existing `StreakBonusTest` suite must continue to pass unchanged — behaviour is identical. |

---

## Out of scope

- CAPTCHA / honeypot (Sub-project B)
- Admin endpoint validation (Sub-project C)
- Controller dependency injection refactor (Sub-project D)
