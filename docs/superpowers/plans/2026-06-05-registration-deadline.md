# Registration Deadline Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the hardcoded registration deadline in the login modal with a dynamic check against the earliest game date in the database.

**Architecture:** A single `@php` block at the top of `modals/main.blade.php` queries `Game::min('game_date')` and sets `$registrationOpen`. Both existing `@if` gates reference this variable. No controller changes needed — the modal is a partial included on every page.

**Tech Stack:** Laravel 11, Blade, PHPUnit 11, MySQL via Sail

---

## File Map

| Action | File |
|---|---|
| Modify | `resources/views/modals/main.blade.php` |
| Create | `tests/Feature/RegistrationDeadlineTest.php` |

---

## Task 1: Registration deadline tests

**Files:**
- Create: `tests/Feature/RegistrationDeadlineTest.php`

- [ ] **Step 1: Write the three failing tests**

Create `tests/Feature/RegistrationDeadlineTest.php` with this exact content:

```php
<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationDeadlineTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_tab_visible_when_no_games(): void
    {
        $response = $this->get(route('main'));

        $response->assertOk();
        $response->assertSee('Registruotis');
    }

    public function test_registration_tab_visible_when_first_game_is_in_future(): void
    {
        $homeTeam = Team::create(['team' => 'TeamA']);
        $awayTeam = Team::create(['team' => 'TeamB']);
        $event = Event::create([
            'event' => 'WC 2026',
            'event_day' => 1,
            'event_survival' => 0,
            'active' => 1,
            'rate' => 1,
        ]);
        Game::create([
            'game_date' => now()->addDay(),
            'event_id' => $event->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
        ]);

        $response = $this->get(route('main'));

        $response->assertOk();
        $response->assertSee('Registruotis');
    }

    public function test_registration_tab_hidden_when_first_game_has_started(): void
    {
        $homeTeam = Team::create(['team' => 'TeamA']);
        $awayTeam = Team::create(['team' => 'TeamB']);
        $event = Event::create([
            'event' => 'WC 2026',
            'event_day' => 1,
            'event_survival' => 0,
            'active' => 1,
            'rate' => 1,
        ]);
        Game::create([
            'game_date' => now()->subHour(),
            'event_id' => $event->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
        ]);

        $response = $this->get(route('main'));

        $response->assertOk();
        $response->assertDontSee('Registruotis');
    }
}
```

- [ ] **Step 2: Run the tests — expect them to fail**

```bash
docker exec php php artisan test tests/Feature/RegistrationDeadlineTest.php
```

Expected: the first test (`no_games`) passes (hardcoded date already expired so registration is hidden — wait, actually it may fail since the hardcoded check `now() < '2025-09-01'` is false, meaning "Registruotis" is NOT rendered). All three tests should fail or behave unexpectedly because the current implementation uses a hardcoded expired date:
- `test_registration_tab_visible_when_no_games` → FAIL (hardcoded date expired, tab hidden)
- `test_registration_tab_visible_when_first_game_is_in_future` → FAIL (same reason)
- `test_registration_tab_hidden_when_first_game_has_started` → PASS (accidentally correct)

Note the results. Proceed to Task 2.

---

## Task 2: Replace hardcoded date in modal

**Files:**
- Modify: `resources/views/modals/main.blade.php`

- [ ] **Step 1: Replace the file content**

Replace the entire content of `resources/views/modals/main.blade.php` with:

```blade
@php
    $registrationOpen = !\App\Models\Game::exists()
        || now()->lt(\App\Models\Game::min('game_date'));
@endphp
<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">

        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
                <nav>
                    <div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">
                        <button class="nav-link active" id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home" aria-selected="true">Prisijungti</button>
                        @if ($registrationOpen)
                            <button class="nav-link" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">Registruotis</button>
                        @endif
                    </div>
                </nav>
                <div class="tab-content" id="nav-tabContent">
                    <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
                        @include('modals.login')
                    </div>
                    @if ($registrationOpen)
                        <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                            @include('modals.register')
                        </div>
                    @endif
                </div>

        </div>
    </div>
</div>
```

- [ ] **Step 2: Run the deadline tests — expect all 3 to pass**

```bash
docker exec php php artisan test tests/Feature/RegistrationDeadlineTest.php
```

Expected:
```
Tests:    3 passed
```

- [ ] **Step 3: Run the full suite — confirm no regressions**

```bash
docker exec php php artisan test
```

Expected: all 65 tests pass (62 existing + 3 new).

- [ ] **Step 4: Commit**

```bash
git add resources/views/modals/main.blade.php tests/Feature/RegistrationDeadlineTest.php
git commit -m "feat: derive registration deadline from first game date"
```
