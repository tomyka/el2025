# Admin Games Page Redesign

**Date:** 2026-06-09  
**Scope:** `resources/views/admin/games.blade.php` + `GameController.php` (insert/update methods)

## Goal

Bring `/admin/games` in line with the table-based pattern already applied to groups, teams, and users pages. Replace the old Bootstrap grid-row layout with a proper `<table>` using the `ag-table` CSS class family.

## Decisions

| Topic | Decision |
|---|---|
| Date/time input | Split into 3 fields: date text input + hour select (18–22) + minute select (00, 05, 15, 30, 45) |
| Edit rows | Also use the split date/hour/min fields (not a single raw datetime text input as before) |
| Insert row position | Bottom of table (consistent with groups and teams pages) |
| Action buttons | Icon-only: `bi-check-lg` to save, `bi-trash3` to delete |
| Card title | "Žaidimai" with `bi-calendar-event-fill` icon and game count badge |

## View Changes (`games.blade.php`)

Replace the entire file with a `<table class="table table-hover align-middle mb-0 ag-table">` structure:

**thead columns:**
`#` · `Data` · `Val.` · `Min.` · `Šeimininkai` · `Svečiai` · `Etapas` · _(actions, no header)_

**tbody — edit rows** (one `<form>` per `<tr>`, `formaction` pointing to `admin.updateGame` for save and `admin.deleteGame` for delete):
- Hidden `gameID` input
- `#` cell: game id (muted)
- `Data` cell: text input pre-filled with `substr($game->game_date, 0, 10)`, name `gameDate`
- `Val.` cell: select name `gameHour`, options 18–22, selected by matching `substr($game->game_date, 11, 2)`
- `Min.` cell: select name `gameMinute`, options 00/05/15/30/45, selected by matching `substr($game->game_date, 14, 2)`
- `Šeimininkai` cell: home team select (same `$teams` loop, pre-selected on `$game->home_team_id`)
- `Svečiai` cell: away team select (same `$teams` loop, pre-selected on `$game->away_team_id`)
- `Etapas` cell: event select (same `$events` loop, pre-selected on `$game->event_id`)
- Actions cell: save button (`name="update"`, `bi-check-lg` icon) + delete button (`name="delete"`, `bi-trash3` icon, superadmin-gated, confirm dialog)

**tbody — insert row** (separate `<form>` with `action="{{ route('admin.insertGame') }}"`, class `ag-insert-row`):
- `+` icon cell (`bi-plus-lg`, muted)
- `Data` cell: text input name `gameDate`, pre-filled with `substr($gameMaxDateTime, 0, 10)`
- `Val.` cell: select name `gameHour`, pre-selected from `$gameMaxDateTime`
- `Min.` cell: select name `gameMinute`, pre-selected from `$gameMaxDateTime`
- `Šeimininkai` cell: home team select
- `Svečiai` cell: away team select
- `Etapas` cell: event select, pre-selected on `$lastEnteredEventID`
- Actions cell: submit button with `bi-plus-lg` icon, `btn-primary`

## Controller Changes (`GameController.php`)

**`updateGame()`** — add reading of `gameHour` and `gameMinute` from request, then combine:
```php
$game->game_date = $request->input('gameDate') . ' ' . $request->input('gameHour') . ':' . $request->input('gameMinute') . ':00';
```
Remove the old single `$gameDate` assignment.

**`insertGame()`** — already combines correctly; no change needed.

## CSS

No new CSS classes needed. The existing `ag-table`, `ag-col-id`, `ag-action-btn`, `ag-action-delete`, `ag-insert-row` classes from `public/css/custom.css` cover the pattern.

## Out of Scope

- Pagination (all games shown, as before)
- Mobile responsiveness changes beyond what the table provides
- Any changes to routing or other controllers
