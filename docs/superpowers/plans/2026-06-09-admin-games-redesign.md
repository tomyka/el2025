# Admin Games Page Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign `/admin/games` from a Bootstrap grid-row layout to a proper `ag-table`, matching the pattern used by groups and teams pages, with split date/hour/minute columns in every row.

**Architecture:** Three coordinated changes — a one-line controller fix to `updateGame()` so it assembles `game_date` from three split fields, two new CSS column-width classes appended to `custom.css`, and a full rewrite of `games.blade.php` as an `ag-table` with an insert row at the bottom.

**Tech Stack:** Laravel 11 · Blade · Bootstrap 5 · Bootstrap Icons · PHPUnit (SQLite in-memory for tests)

---

## Files

| Action | Path |
|---|---|
| Modify | `app/Http/Controllers/GameController.php` — `updateGame()` only |
| Append | `public/css/custom.css` — 3 new `ag-col-*` classes |
| Rewrite | `resources/views/admin/games.blade.php` |
| Create | `tests/Feature/GameControllerTest.php` |

---

## Task 1: Update `updateGame()` to accept split date/time fields

The edit rows now send `gameDate` (date string), `gameHour` (18–22), and `gameMinute` (00/05/15/30/45) as separate fields instead of a single `gameDate` datetime string. The controller must combine them.

**Files:**
- Create: `tests/Feature/GameControllerTest.php`
- Modify: `app/Http/Controllers/GameController.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/GameControllerTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_game_assembles_game_date_from_split_fields(): void
    {
        $admin    = User::factory()->create();
        $home     = Team::create(['team' => 'Germany', 'group_name' => 'A', 'group_position' => 1]);
        $away     = Team::create(['team' => 'France',  'group_name' => 'B', 'group_position' => 1]);
        $event    = Event::create(['event' => 'Group Stage', 'event_day' => 1, 'event_survival' => 0, 'active' => 1, 'rate' => 1]);
        $game     = Game::create([
            'game_date'    => '2026-06-10 18:00:00',
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'event_id'     => $event->id,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin' => 1, 'eventID' => $event->id])
            ->post(route('admin.updateGame'), [
                'gameID'     => $game->id,
                'gameDate'   => '2026-06-11',
                'gameHour'   => '21',
                'gameMinute' => '30',
                'homeTeamID' => $home->id,
                'awayTeamID' => $away->id,
                'eventID'    => $event->id,
            ]);

        $response->assertRedirect(route('admin.games'));
        $this->assertSame('2026-06-11 21:30:00', $game->fresh()->game_date);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Feature/GameControllerTest.php --filter test_update_game_assembles_game_date_from_split_fields
```

Expected: FAIL — the test asserts `'2026-06-11 21:30:00'` but `updateGame()` currently saves the raw `gameDate` string `'2026-06-11'`.

- [ ] **Step 3: Update `updateGame()` in `GameController.php`**

Open `app/Http/Controllers/GameController.php`. Replace the `updateGame()` method (lines 87–104) with:

```php
public function updateGame(Request $request)
{
    $gameID     = $request->input('gameID');
    $homeTeamID = $request->input('homeTeamID');
    $awayTeamID = $request->input('awayTeamID');
    $eventID    = $request->input('eventID');

    $game = game::find($gameID);
    $game->game_date    = $request->input('gameDate') . ' ' . $request->input('gameHour') . ':' . $request->input('gameMinute') . ':00';
    $game->home_team_id = $homeTeamID;
    $game->away_team_id = $awayTeamID;
    $game->event_id     = $eventID;
    $game->save();

    return redirect()->route('admin.games')->with('info', 'Game ' . $gameID . ' has been updated');
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test tests/Feature/GameControllerTest.php --filter test_update_game_assembles_game_date_from_split_fields
```

Expected: PASS

- [ ] **Step 5: Run full test suite to check for regressions**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/GameControllerTest.php app/Http/Controllers/GameController.php
git commit -m "feat: split game date/hour/minute fields in updateGame controller"
```

---

## Task 2: Add game-table CSS column classes

The view needs three column-width classes not yet defined in `custom.css`. Append them after the existing `ag-*` block (currently ending around the `.ag-insert-row` rule).

**Files:**
- Modify: `public/css/custom.css`

- [ ] **Step 1: Locate the end of the existing `ag-*` block**

Search `public/css/custom.css` for `.ag-insert-row`. The new classes go immediately after that rule.

- [ ] **Step 2: Append the three new classes**

After the `.ag-insert-row td { ... }` rule, add:

```css
.ag-col-date        { width: 110px; }
.ag-col-time        { width: 64px; }
.ag-col-team        { min-width: 130px; }
```

- [ ] **Step 3: Commit**

```bash
git add public/css/custom.css
git commit -m "style: add ag-col-date/time/team column classes for games table"
```

---

## Task 3: Rewrite `games.blade.php` as an `ag-table`

Full replacement of the old grid-row layout. The table has one `<form>` per edit `<tr>` and a single insert `<form>` wrapping the bottom row.

**Files:**
- Rewrite: `resources/views/admin/games.blade.php`

- [ ] **Step 1: Replace the entire file** with the following content:

```blade
@extends('admin.layouts.master')
@section('content')

<div class="sb-card">
    <div class="sb-card-title">
        <i class="bi bi-calendar-event-fill sb-card-icon"></i> Žaidimai
        <span class="badge bg-secondary fw-normal ms-1">{{ $games->count() }}</span>
    </div>

    @if(Session::has('info'))
    <div class="alert alert-success py-2 mb-3">{{ Session::get('info') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 ag-table">
            <thead class="table-light">
                <tr>
                    <th class="ag-col-id text-muted">#</th>
                    <th class="ag-col-date">Data</th>
                    <th class="ag-col-time text-center">Val.</th>
                    <th class="ag-col-time text-center">Min.</th>
                    <th class="ag-col-team">Šeimininkai</th>
                    <th class="ag-col-team">Svečiai</th>
                    <th class="ag-col-team">Etapas</th>
                    <th class="ag-col-actions"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($games as $game)
                <tr>
                    <form method="post">
                    @csrf
                    <input type="hidden" name="gameID" value="{{ $game->id }}">
                    <td class="ag-id">{{ $game->id }}</td>
                    <td>
                        <input type="text" class="form-control form-control-sm"
                               name="gameDate" value="{{ substr($game->game_date, 0, 10) }}">
                    </td>
                    <td class="text-center">
                        <select name="gameHour" class="form-select form-select-sm">
                            @foreach(['18','19','20','21','22'] as $h)
                            <option value="{{ $h }}" {{ substr($game->game_date, 11, 2) == $h ? 'selected' : '' }}>{{ $h }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="text-center">
                        <select name="gameMinute" class="form-select form-select-sm">
                            @foreach(['00','05','15','30','45'] as $m)
                            <option value="{{ $m }}" {{ substr($game->game_date, 14, 2) == $m ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="homeTeamID" class="form-select form-select-sm">
                            <option value="">—</option>
                            @foreach($teams as $teamID => $teamName)
                            <option value="{{ $teamID }}" {{ $teamID == $game->home_team_id ? 'selected' : '' }}>{{ $teamName }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="awayTeamID" class="form-select form-select-sm">
                            <option value="">—</option>
                            @foreach($teams as $teamID => $teamName)
                            <option value="{{ $teamID }}" {{ $teamID == $game->away_team_id ? 'selected' : '' }}>{{ $teamName }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="eventID" class="form-select form-select-sm">
                            <option value="">—</option>
                            @foreach($events as $eventID => $eventName)
                            <option value="{{ $eventID }}" {{ $eventID == $game->event_id ? 'selected' : '' }}>{{ $eventName }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="text-end" style="white-space:nowrap;">
                        <button type="submit" name="update" value="1"
                                class="btn btn-sm btn-outline-secondary ag-action-btn"
                                formaction="{{ route('admin.updateGame') }}"
                                title="Išsaugoti">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        @if(session('admin') == 9)
                        <button type="submit" name="delete" value="1"
                                class="btn btn-sm btn-outline-secondary ag-action-btn ag-action-delete ms-1"
                                formaction="{{ route('admin.deleteGame') }}"
                                title="Ištrinti"
                                onclick="return confirm('Ištrinti žaidimą #{{ $game->id }}?')">
                            <i class="bi bi-trash3"></i>
                        </button>
                        @endif
                    </td>
                    </form>
                </tr>
                @endforeach

                {{-- Insert row --}}
                <tr class="ag-insert-row">
                    <form method="post" action="{{ route('admin.insertGame') }}">
                    @csrf
                    <td class="ag-id"><i class="bi bi-plus-lg text-muted"></i></td>
                    <td>
                        <input type="text" class="form-control form-control-sm"
                               name="gameDate" value="{{ substr($gameMaxDateTime, 0, 10) }}">
                    </td>
                    <td class="text-center">
                        <select name="gameHour" class="form-select form-select-sm">
                            @foreach(['18','19','20','21','22'] as $h)
                            <option value="{{ $h }}" {{ substr($gameMaxDateTime, 11, 2) == $h ? 'selected' : '' }}>{{ $h }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="text-center">
                        <select name="gameMinute" class="form-select form-select-sm">
                            @foreach(['00','05','15','30','45'] as $m)
                            <option value="{{ $m }}" {{ substr($gameMaxDateTime, 14, 2) == $m ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="homeTeamID" class="form-select form-select-sm">
                            <option value="">— Šeimininkai —</option>
                            @foreach($teams as $teamID => $teamName)
                            <option value="{{ $teamID }}">{{ $teamName }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="awayTeamID" class="form-select form-select-sm">
                            <option value="">— Svečiai —</option>
                            @foreach($teams as $teamID => $teamName)
                            <option value="{{ $teamID }}">{{ $teamName }}</option>
                            @endforeach
                        </select>
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
                        <button type="submit" name="insert" value="1"
                                class="btn btn-sm btn-primary ag-action-btn"
                                title="Pridėti žaidimą">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </td>
                    </form>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@endsection
```

- [ ] **Step 2: Run the full test suite**

```bash
php artisan test
```

Expected: all tests pass (the view change has no automated test; we verify visually in the next step).

- [ ] **Step 3: Start the dev server and open the page**

```bash
npm run dev
php artisan serve
```

Open `http://localhost:8000/admin/games` and verify:
- Card title "Žaidimai" with calendar icon and game count badge
- Table with columns: #, Data, Val., Min., Šeimininkai, Svečiai, Etapas, actions
- Each game row shows split date/hour/min dropdowns with correct pre-selected values
- Save button (✓) and delete button (🗑, superadmin only) are icon-only
- Insert row at the bottom with `+` button, pre-filled date/hour/min from most recent game
- Submitting a save updates the game correctly in the database
- Submitting the insert row adds a new game

- [ ] **Step 4: Commit**

```bash
git add resources/views/admin/games.blade.php
git commit -m "feat: redesign admin games page as ag-table with split date/time columns"
```
