# Layout Redesign — Design Spec
Date: 2026-06-05

## Summary

Rework the user-facing layout of SportBet from a plain Bootstrap navbar + full-width content to a modern sports-app design: dark navbar, dark gradient stats hero bar, light card-based content area, and a mobile bottom tab bar. The admin panel is out of scope and stays unchanged.

## Design Decisions

| Decision | Choice |
|---|---|
| Page structure | Top navbar + persistent stats bar + two-column content grid |
| Visual style | Dark navbar/stats bar + light content area (Sofascore-style) |
| Mobile navigation | Bottom tab bar on `< lg`, compact top bar (logo + profile icon only) |

---

## 1. Colour Palette

All colours defined as CSS custom properties in `public/css/custom.css`.

| Token | Value | Usage |
|---|---|---|
| `--sb-nav` | `#1e293b` | Navbar + bottom tab bar background |
| `--sb-hero` | `#0f172a` | Stats bar gradient end |
| `--sb-accent` | `#38bdf8` | Primary accent, active nav, points |
| `--sb-gold` | `#f59e0b` | Rank highlight |
| `--sb-green` | `#34d399` | Upcoming games count |
| `--sb-purple` | `#a78bfa` | Bingo count |
| `--sb-surface` | `#f1f5f9` | Page background |
| `--sb-card` | `#ffffff` | Card background |
| `--sb-border` | `#e2e8f0` | Card border |
| `--sb-text` | `#1e293b` | Primary text |
| `--sb-muted` | `#64748b` | Secondary/label text |

---

## 2. Layout Structure

### Master layout (`resources/views/layouts/master.blade.php`)

- `<body>` gets `class="sb-layout"` — flex column, min-height 100vh
- Structure:
  ```
  <body class="sb-layout">
    @include('partials.header')       ← navbar + stats bar
    <main class="sb-main">
      @yield('content')
    </main>
    @include('partials.bottom-nav')   ← mobile only, fixed bottom
  </main>
  ```
- Remove the old `div-content` wrapper div and its inline column classes
- Add `padding-bottom: 60px` on `<main>` for mobile (bottom tab bar clearance); remove on `lg+`

### Content pages

Pages that currently use `container-fluid` directly keep their structure. The two-column split on the main dashboard (`main.blade.php`) is already done via Bootstrap grid — the new layout just makes the background and cards look right.

---

## 3. Components

### 3.1 Navbar (`resources/views/partials/header.blade.php`)

**Desktop (`lg+`):** Full horizontal nav in the dark bar.
- Background: `var(--sb-nav)`
- Brand: `⚽ SportBet` in `var(--sb-accent)`
- Nav links: `Spėjimai`, `Eiga`, `Išlikimas` (conditional), `Suvestinė` dropdown — white at 70% opacity, active page gets full white + 2px bottom border in `var(--sb-accent)`
- Right side: info icon dropdown + profile icon dropdown + logout link
- Remove `navbar-expand` (always expanded on desktop); use `navbar-expand-lg`

**Mobile (`< lg`):** Compact bar — logo left, profile icon right. No nav links (they move to bottom tab bar). No hamburger.

**Stats hero bar** (below navbar, always visible when authenticated):
- Background: `linear-gradient(135deg, var(--sb-nav), var(--sb-hero))`
- Four stat pills in a flex row: **Taškai** (accent blue), **Vieta** (gold), **Rungtynės** (green), **Bingo** (purple)
- Each pill: large bold number + small uppercase label below
- Dividers between pills (1px, `#334155`)
- Hidden for guests (unauthenticated state shows the welcome page, no stats bar)
- Data comes from session values already available: `session('userGamePoints')`, rank computed in controller

### 3.2 Bottom tab bar (`resources/views/partials/bottom-nav.blade.php`) — NEW FILE

- Visible only on `< lg` via `d-lg-none`
- Fixed to bottom of viewport: `position: fixed; bottom: 0; left: 0; right: 0; z-index: 1030`
- Background: `var(--sb-nav)`, top border `1px solid #334155`
- Four tabs: **Spėjimai** (⚽), **Eiga** (📊), **Išlikimas** (🎯, conditional on `session('survivalGame')`), **Suvestinė** (📄)
- Active tab: `var(--sb-accent)` colour; inactive: `#64748b`
- Active detection: compare current route name with tab's target route
- Only shown when authenticated (`@auth`)

### 3.3 Content cards

All content sections get wrapped in a white card:

```html
<div class="sb-card">
  <div class="sb-card-title">Section title</div>
  <!-- content -->
</div>
```

CSS:
```css
.sb-card {
  background: var(--sb-card);
  border: 1px solid var(--sb-border);
  border-radius: 10px;
  padding: 16px;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
  margin-bottom: 16px;
}
.sb-card-title {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: var(--sb-text);
  margin-bottom: 12px;
}
```

Applied to: games table partial, points leaderboard partial, standings partial, previous results partial.

### 3.4 Page background

`<main class="sb-main">` gets `background: var(--sb-surface)` and `padding: 16px`.

On mobile, `padding: 12px 10px`.

---

## 4. Responsive Behaviour

| Breakpoint | Navbar | Stats bar | Content | Bottom bar |
|---|---|---|---|---|
| `< lg` (mobile) | Logo + profile icon only | Full width, 4 stats | Single column, full width | Fixed bottom, 4 tabs |
| `lg+` (desktop) | Full nav links + dropdowns | Full width, 4 stats | Two-column grid (existing) | Hidden |

The two-column grid on `main.blade.php` already uses Bootstrap's `col-xl-5/6` + `col-xl-4/6` pattern — this stays unchanged. The new layout makes the background and cards look right around it.

---

## 5. Files Changed

| File | Change |
|---|---|
| `public/css/custom.css` | Add CSS custom properties + `.sb-*` utility classes; remove legacy `.body_side`, `.div-left`, `.div-right`, `.div-content` |
| `resources/views/layouts/master.blade.php` | New body structure with `sb-layout`, `sb-main`, include bottom-nav |
| `resources/views/partials/header.blade.php` | Restyle navbar dark; add stats hero bar below; remove hamburger on mobile |
| `resources/views/partials/bottom-nav.blade.php` | **New file** — mobile bottom tab bar |
| `resources/views/partials/games.blade.php` | Wrap in `.sb-card` |
| `resources/views/partials/points.blade.php` | Wrap in `.sb-card`; highlight current user row |
| `resources/views/partials/standings.blade.php` | Wrap in `.sb-card` |
| `resources/views/partials/previous.blade.php` | Wrap in `.sb-card` |
| `resources/views/main.blade.php` | Remove `<BR>` tags; ensure outer container uses `sb-surface` background |

---

## 6. Files NOT Changed

- `resources/views/admin/**` — admin panel stays as-is
- `resources/views/admin/layouts/master.blade.php` — admin layout unchanged
- All prediction logic, controllers, routes, models — zero backend changes
- `resources/views/layouts/master_blank.blade.php` — used for registration/login blank pages; stays unchanged
- Chart, summary, survival pages — content stays; only gets `.sb-card` wrapping where applicable

---

## 7. Stats Bar Data

The stats bar is part of `partials/header.blade.php`, which is included on every authenticated page — not just `main.blade.php`. The data must therefore be computed directly in the partial via a `@php` block, following the same established pattern used in `modals/main.blade.php`.

| Stat | Query / source |
|---|---|
| Taškai | `PointResult::where('user_id', session('userID'))->where('group_id', session('groupID'))->sum('full_points')` + standing points total from `PredictionStanding` |
| Vieta | Rank of current user in the group: count users in same group whose total points exceed the current user's total + 1 |
| Rungtynės | `Game::whereNull('home_team_score')->whereNull('away_team_score')->count()` — games not yet played |
| Bingo | `PointResult::where('user_id', session('userID'))->where('group_id', session('groupID'))->sum('bingo_points')->count()` — rows where bingo_points > 0 |

All four queries run inside a `@php` block at the top of `partials/header.blade.php`, guarded by `@auth` so they only execute for logged-in users. This is a read-only addition with no backend controller changes required.

---

## 8. Welcome Page (unauthenticated)

Guests see no stats bar. The navbar shows only the logo and a "Prisijungti" button (which opens the existing login modal). Content area shows the existing `welcome.blade.php` text, now wrapped in a `.sb-card` for visual consistency.

---

## 9. Out of Scope

- Dark mode toggle (future)
- Animations / transitions (future)
- OAuth / auth rework (separate project)
- Admin panel redesign (separate project)
- Chart page restyling (minor; can be done opportunistically)
