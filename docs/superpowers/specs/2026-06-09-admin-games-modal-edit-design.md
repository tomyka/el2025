# Admin Games Modal Edit — Design Spec

**Date:** 2026-06-09
**Status:** Approved

## Goal

Replace the inline-editing table on `/admin/games` with a compact read-only fixture list. Superadmins edit via a Bootstrap 5 modal. Editors see the list read-only with no edit controls.

---

## Row Layout

Each row shows five cells:

| Column | Content |
|---|---|
| `#` | Game ID, muted small text |
| Date / Time | `28 Jun · 19:00`, compact single cell |
| Match | `Bosnia and Herzegovina vs Qatar`, full names, no truncation |
| Stage | Event name as a small badge |
| Actions | Edit button (superadmin only) |

No inline form fields. Selects and date inputs are gone from the table entirely.

---

## Modal

Triggered by the Edit button on a row. Bootstrap 5 `modal` component, Alpine.js wires the open/close and pre-fills fields.

**Header:** "Edit game #72"
**Body fields (full-width, stacked):**
1. Date & time — `<input type="datetime-local">`
2. Home team — `<select>`
3. Away team — `<select>`
4. Stage — `<select>`

**Footer layout:**
- Left: Delete button (red outline, superadmin `session('admin') >= 9` only) — submits to existing `admin.deleteGame` route with a JS `confirm()` guard
- Right: Cancel (closes modal) + Save (submits to existing `admin.updateGame` route)

The modal form posts to the same `admin.updateGame` and `admin.deleteGame` routes already in use. No new routes needed.

---

## Insert Row

Keep the existing insert row at the bottom of the table, unchanged in behaviour. Switch its date/time field to `datetime-local` (already done). It is always visible and always submits to `admin.insertGame`.

---

## Access Control

| Role | Table | Edit button | Modal | Insert row |
|---|---|---|---|---|
| Superadmin (`>= 9`) | visible | visible | full | visible |
| Editor (`< 9`) | visible | hidden | not accessible | hidden |

No new middleware or route guards needed — existing `session('admin')` checks in the view cover this.

---

## Implementation Scope

**Files to change:**

| File | Change |
|---|---|
| `resources/views/admin/games.blade.php` | Full rewrite — read-only rows, modal markup, Alpine data, insert row |
| `public/css/custom.css` | Remove/update `agm-col-*` fixed widths; table goes full-width auto-layout |

**No controller changes.** `updateGame()`, `insertGame()`, `deleteGame()` stay as-is.

---

## Testing

No new automated tests needed — controller behaviour is already covered by `GameControllerTest`. Visual verification: open `/admin/games` as superadmin and as editor, confirm row display, modal open/close, save, delete confirm, and insert.
