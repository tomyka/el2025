# English Language Support (LT/EN Switcher)

**Date:** 2026-07-03  
**Status:** Approved

## Summary

Add English language support to the SportBet app via a LT/EN toggle. Language preference is stored per user in `user_settings`, applied on every request via middleware, and switchable from both the navbar and the user settings page.

---

## 1. Data Layer & Locale Detection

### Migration
Add `locale` column to `user_settings`:
- Type: `varchar(5)`, default `'lt'`, nullable
- Values: `'lt'` or `'en'`

### SetLocale Middleware
- Runs on every request (authenticated + guest)
- Authenticated: reads `session('locale')` (populated by `SessionController::setSession()`, which already reads `user_settings` on every page load — no extra DB hit needed); falls back to `'lt'`
- Guest: reads `session('locale')`, defaults to `'lt'`
- Registered in `bootstrap/app.php` for the `web` middleware group, runs after session is initialised
- `SessionController::setSession()` must also be updated to load `locale` into the session from `user_settings.locale`

### LocaleController
- `POST /locale` — accepts `{locale: 'en'|'lt'}`
- Authenticated: updates `user_settings.locale` in DB + writes to `session('locale')`
- Guest: writes to `session('locale')` only
- Redirects back to previous page

---

## 2. Translation Files

### Structure
- `lang/lt.json` — identity map, every key equals its Lithuanian value (zero regression risk)
- `lang/en.json` — same keys, English translations

### Sample entries
```json
// lang/en.json
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
  "Privatumas": "Privacy",
  "Lygos": "Leagues",
  "Profilis": "Profile",
  "Atsijungti": "Log out",
  "Prisijungti": "Log in",
  "Lyga": "League",
  "Turnyrai": "Tournaments"
}
```

### Scope
- All hardcoded strings in 101 blade template files
- Controller flash/error messages (~15 strings across `LeagueController`, `GoogleAuthController`, `MessageController`, etc.)
- Auth views scaffolded by Laravel already use `__()` — no changes needed

### Out of scope
- Database content (team names, match data, user messages) — stays as-is
- Missing keys fall back to the key itself (Lithuanian), so partial migration is safe

---

## 3. Blade Template Migration

### Strategy
Wrap all hardcoded Lithuanian strings in `__()`:
- Text nodes: `Pradžia` → `{{ __('Pradžia') }}`
- Attributes: `title="Keisti temą"` → `title="{{ __('Keisti temą') }}"`
- Blade components: `:value="__('Password')"`

### Order of migration (maximise early coverage)
1. **Shared partials** (`resources/views/partials/`) — header, bottom-nav, warnings, messages, etc.
2. **Layouts** (`resources/views/layouts/`) — master, admin master
3. **Core page views** — main, leaderboard, prediction/*, summary/*, compare/*
4. **Auth & profile views**
5. **Admin views** (`resources/views/admin/`)
6. **Controller flash messages**

### Alpine.js / JS strings
Any text rendered purely in JavaScript will use `data-` attributes populated via `{{ __('...') }}` in the blade template, passed into Alpine components. Expected to be minimal.

---

## 4. UI — Language Switcher

### Navbar (desktop + mobile)
- A `LT | EN` toggle added to:
  - Desktop: right side of topnav, next to the theme toggle button
  - Mobile: inside the `#sbNavMobile` collapse panel (account section)
  - Guest nav: also visible for unauthenticated users
- Implementation: two small `<form method="POST" action="/locale">` buttons (one per locale), so it works without JavaScript
- Active locale is highlighted (bold or underlined)

### User Settings Page (`userSettings.blade.php`)
- New "Language / Kalba" row in the existing settings form
- Simple two-option toggle: `Lietuvių` / `English`
- Posts to `POST /locale` — same endpoint as the navbar toggle

---

## 5. Files Changed

| File | Change |
|---|---|
| `database/migrations/XXXX_add_locale_to_user_settings.php` | New migration |
| `app/Http/Middleware/SetLocale.php` | New middleware |
| `app/Http/Controllers/LocaleController.php` | New controller |
| `routes/web.php` | Add `POST /locale` route |
| `bootstrap/app.php` | Register middleware |
| `lang/lt.json` | New — Lithuanian identity map |
| `lang/en.json` | New — English translations |
| `resources/views/partials/header.blade.php` | Add LT/EN toggle + wrap strings |
| `resources/views/partials/bottom-nav.blade.php` | Wrap strings |
| `resources/views/partials/*.blade.php` | Wrap strings |
| `resources/views/layouts/master.blade.php` | Wrap strings |
| `resources/views/admin/layouts/master.blade.php` | Wrap strings |
| All 101 blade files | Wrap hardcoded strings in `__()` |
| `app/Http/Controllers/LeagueController.php` | Wrap flash messages |
| `app/Http/Controllers/GoogleAuthController.php` | Wrap flash messages |
| `app/Http/Controllers/MessageController.php` | Wrap flash messages |
| `resources/views/userSettings.blade.php` | Add language preference UI |
