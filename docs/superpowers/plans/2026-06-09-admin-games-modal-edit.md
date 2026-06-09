# Admin Games Modal Edit Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace inline-editing rows on `/admin/games` with a compact read-only fixture list; superadmins edit via a Bootstrap 5 modal driven by Alpine.js.

**Architecture:** Two coordinated changes — CSS cleanup switches the table to auto layout and adds display helpers for the new row style; the Blade view is fully rewritten with read-only rows, a single Alpine-powered modal, and a compact insert row. No controller or route changes.

**Tech Stack:** Laravel 11 · Blade · Bootstrap 5 · Alpine.js · PHPUnit (SQLite in-memory)

---

## Files

| Action | Path |
|---|---|
| Modify | `public/css/custom.css` — `agm-*` block only |
| Rewrite | `resources/views/admin/games.blade.php` |

---

## Task 1: Update `agm-*` CSS

The table switches from `table-layout: fixed` to `table-layout: auto` so the Match column sizes to content. Add display helpers for the new read-only row elements.

**Files:**
- Modify: `public/css/custom.css` (lines ~1660–1668)

- [ ] **Step 1: Replace the `agm-*` block**

Find the block starting with the `Admin games table (.agm-*)` comment and replace everything through `.agm-insert-row td` with:

```css
/* Admin games table (.agm-*) */
.agm-table          { font-size: .875rem; table-layout: auto; width: 100%; }
.agm-col-id         { width: 32px; }
.agm-col-date       { width: 148px; white-space: nowrap; }
.agm-col-stage      { width: 130px; }
.agm-col-actions    { width: 48px; }
.agm-id             { font-size: .75rem; color: var(--sb-muted); }
.agm-datetime       { color: var(--sb-muted); font-size: .8rem; }
.agm-match          { font-weight: 500; }
.agm-vs             { color: var(--sb-muted); font-size: .75rem; margin: 0 .35rem; font-weight: 400; }
.agm-badge-stage    { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: .72rem; background: var(--sb-surface); color: var(--sb-muted); border: 1px solid var(--sb-border); }
.agm-action-btn     { width: 30px; padding-left: 0; padding-right: 0; }
.agm-insert-row td  { border-top: 2px solid var(--sb-border); background: #f9fafb; }
```

- [ ] **Step 2: Verify page still loads without console errors**

Open `http://localhost:8000/admin/games` (Laravel must be running). No CSS errors in browser devtools.

- [ ] **Step 3: Commit**

```bash
git add public/css/custom.css
git commit -m "style: update agm-* CSS for read-only fixture list with modal edit"
```

---

## Task 2: Rewrite `games.blade.php`

Full replacement of the inline-editing view. Read-only rows show `# | Date/Time | Home vs Away | Stage | Edit`. The modal is a single Bootstrap 5 dialog pre-filled by Alpine.js. The insert row collapses home/away/stage into the Match and Stage cells.

**Files:**
- Rewrite: `resources/views/admin/games.blade.php`

- [ ] **Step 1: Replace the entire file** with the following:

```blade
@extends('admin.layouts.master')
@section('content')

<div class="sb-card" x-data="gameModal()">
    <div class="sb-card-title">
        <i class="bi bi-calendar-event-fill sb-card-icon"></i> Žaidimai
        <span class="badge bg-secondary fw-normal ms-1">{{ $games->count() }}</span>
    </div>

    @if(Session::has('info'))
    <div class="alert alert-success py-2 mb-3">{{ Session::get('info') }}</div>
    @endif

    {{-- Hidden delete form — inside x-data scope so :value="gameID" works --}}
    <form id="agmDeleteForm" method="post" action="{{ route('admin.deleteGame') }}" style="display:none">
        @csrf
        <input type="hidden" name="gameID" :value="gameID">
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 agm-table">
            <thead class="table-light">
                <tr>
                    <th class="agm-col-id text-muted">#</th>
                    <th class="agm-col-date">Data / laikas</th>
                    <th>Rungtynės</th>
                    <th class="agm-col-stage">Etapas</th>
                    @if(session('admin') >= 9)
                    <th class="agm-col-actions"></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($games as $game)
                <tr>
                    <td class="agm-id">{{ $game->id }}</td>
                    <td class="agm-datetime">
                        {{ \Carbon\Carbon::parse($game->game_date)->format('d M · H:i') }}
                    </td>
                    <td class="agm-match">
                        {{ $game->home_team->team ?? '—' }}<span class="agm-vs">vs</span>{{ $game->away_team->team ?? '—' }}
                    </td>
                    <td>
                        <span class="agm-badge-stage">{{ $game->event->event ?? '—' }}</span>
                    </td>
                    @if(session('admin') >= 9)
                    <td class="text-end">
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary agm-action-btn"
                                title="Redaguoti"
                                @click="openModal('{{ $game->id }}', '{{ substr(str_replace(' ', 'T', $game->game_date), 0, 16) }}', '{{ $game->home_team_id ?? '' }}', '{{ $game->away_team_id ?? '' }}', '{{ $game->event_id ?? '' }}')">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </td>
                    @endif
                </tr>
                @endforeach

                {{-- Insert row — superadmin only --}}
                @if(session('admin') >= 9)
                <tr class="agm-insert-row">
                    <form method="post" action="{{ route('admin.insertGame') }}">
                    @csrf
                    <td class="agm-id"><i class="bi bi-plus-lg text-muted"></i></td>
                    <td>
                        <input type="datetime-local" class="form-control form-control-sm"
                               name="gameDateTime"
                               value="{{ substr(str_replace(' ', 'T', $gameMaxDateTime), 0, 16) }}">
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            <select name="homeTeamID" class="form-select form-select-sm">
                                <option value="">— Šeimininkai —</option>
                                @foreach($teams as $teamID => $teamName)
                                <option value="{{ $teamID }}">{{ $teamName }}</option>
                                @endforeach
                            </select>
                            <select name="awayTeamID" class="form-select form-select-sm">
                                <option value="">— Svečiai —</option>
                                @foreach($teams as $teamID => $teamName)
                                <option value="{{ $teamID }}">{{ $teamName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </td>
                    <td>
                        <select name="eventID" class="form-select form-select-sm">
                            <option value="">— Etapas —</option>
                            @foreach($events as $eventID => $eventName)
                            <option value="{{ $eventID }}" {{ $eventID == $lastEnteredEventID ? 'selected' : '' }}>{{ $eventName }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="text-end">
                        <button type="submit"
                                class="btn btn-sm btn-primary agm-action-btn"
                                title="Pridėti žaidimą">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </td>
                    </form>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- Edit modal --}}
    <div class="modal fade" id="agmEditModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Redaguoti žaidimą
                        <span class="text-muted fw-normal fs-6" x-text="'#' + gameID"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="{{ route('admin.updateGame') }}">
                    @csrf
                    <input type="hidden" name="gameID" :value="gameID">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Data ir laikas</label>
                            <input type="datetime-local" class="form-control"
                                   name="gameDateTime" x-model="gameDateTime">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Šeimininkai</label>
                            <select name="homeTeamID" class="form-select" x-model="homeTeamID">
                                <option value="">—</option>
                                @foreach($teams as $teamID => $teamName)
                                <option value="{{ $teamID }}">{{ $teamName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Svečiai</label>
                            <select name="awayTeamID" class="form-select" x-model="awayTeamID">
                                <option value="">—</option>
                                @foreach($teams as $teamID => $teamName)
                                <option value="{{ $teamID }}">{{ $teamName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Etapas</label>
                            <select name="eventID" class="form-select" x-model="eventID">
                                <option value="">—</option>
                                @foreach($events as $eventID => $eventName)
                                <option value="{{ $eventID }}">{{ $eventName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button"
                                class="btn btn-outline-danger btn-sm"
                                @click="confirmDelete()">
                            Ištrinti žaidimą
                        </button>
                        <div>
                            <button type="button" class="btn btn-secondary btn-sm me-2"
                                    data-bs-dismiss="modal">Atšaukti</button>
                            <button type="submit" class="btn btn-primary btn-sm">Išsaugoti</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function gameModal() {
    return {
        gameID: '',
        gameDateTime: '',
        homeTeamID: '',
        awayTeamID: '',
        eventID: '',
        openModal(id, dateTime, homeTeamID, awayTeamID, eventID) {
            this.gameID     = id;
            this.gameDateTime = dateTime;
            this.homeTeamID = String(homeTeamID);
            this.awayTeamID = String(awayTeamID);
            this.eventID    = String(eventID);
            new bootstrap.Modal(document.getElementById('agmEditModal')).show();
        },
        confirmDelete() {
            if (confirm('Ištrinti žaidimą #' + this.gameID + '?')) {
                document.getElementById('agmDeleteForm').submit();
            }
        }
    }
}
</script>

@endsection
```

- [ ] **Step 2: Run the test suite to confirm no regressions**

```bash
php artisan test tests/Feature/GameControllerTest.php
```

Expected output (deprecation warnings are noise, not failures):
```
Tests:    2 deprecated (7 assertions)
Duration: ~0.2s
```

- [ ] **Step 3: Visual verification — superadmin**

Open `http://localhost:8000/admin/games` logged in as a superadmin (`session('admin') >= 9`).

Checklist:
- Table shows `# | Data / laikas | Rungtynės | Etapas | (edit button)` columns
- Each row shows e.g. `28 Jun · 19:00` · `Bosnia and Herzegovina vs Qatar` · `Group Stage badge` · pencil button
- Insert row at bottom with datetime-local, two team selects, stage select, `+` button
- Click pencil on any row → Bootstrap modal opens
- Modal header shows `Redaguoti žaidimą #72`
- All four fields (date/time, home, away, stage) are pre-filled with the row's values
- Save → redirects back, success banner, row updated
- Delete → `confirm()` dialog, on confirm row is deleted

- [ ] **Step 4: Visual verification — editor**

Log in as an editor (`session('admin') < 9`).

Checklist:
- Same table visible with identical columns except the actions column is absent
- No edit buttons, no insert row
- Rows are completely read-only

- [ ] **Step 5: Commit**

```bash
git add resources/views/admin/games.blade.php
git commit -m "feat: replace inline game editing with read-only list and modal edit"
```
