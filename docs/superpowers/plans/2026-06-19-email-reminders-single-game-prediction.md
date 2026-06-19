# Email Reminders + Single-Game Prediction Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add opt-in email reminders for upcoming game predictions and a single-game prediction modal accessible from the main page (and a standalone page linked from reminder emails).

**Architecture:** Migration adds two columns (`games.reminder_sent`, `user_settings.receive_reminders`). A scheduled Artisan command runs every 15 minutes, computes reminder time per game (21:00 LT for night games, 1 hour before for day games), and queues `PredictionReminder` mailables to opted-in users. The main page upcoming-games widget gains a pencil button per unplayed game that opens a Bootstrap modal using data already in the view. A standalone page `/prediction/game/{id}` serves as the deep link target in reminder emails.

**Tech Stack:** Laravel 11, PHP 8.2+, Blade, Alpine.js, Bootstrap 5, Carbon, Laravel Mail (queued), Laravel Scheduler

---

## File Map

| Action | File | Responsibility |
|---|---|---|
| Create | `database/migrations/2026_06_19_000000_add_reminder_fields.php` | Two new columns |
| Modify | `app/Models/Game.php` | Add `reminder_sent` to fillable |
| Modify | `app/Models/UserSetting.php` | Add `receive_reminders` to fillable |
| Create | `tests/Feature/NotificationPreferenceTest.php` | Opt-in toggle tests |
| Modify | `app/Http/Controllers/UserProfileController.php` | Add `updateNotifications()`, `unsubscribe()` |
| Modify | `resources/views/userProfile.blade.php` | Add notifications card |
| Modify | `routes/web.php` | New opt-in + game prediction routes |
| Create | `tests/Feature/SingleGamePredictionTest.php` | Standalone page tests |
| Modify | `app/Http/Controllers/PredictionResultController.php` | Add `showSingleGame()`, add `prediction_id` + `game_winner_id` to query |
| Create | `resources/views/prediction/game-single.blade.php` | Standalone prediction page |
| Modify | `resources/views/partials/games.blade.php` | Pencil button + Alpine modal |
| Create | `app/Mail/PredictionReminder.php` | Queued mailable |
| Create | `resources/views/emails/prediction-reminder.blade.php` | Email template |
| Create | `tests/Feature/PredictionReminderCommandTest.php` | Command tests |
| Create | `app/Console/Commands/SendPredictionReminders.php` | Artisan command |
| Modify | `routes/console.php` | Register scheduler |

---

## Task 1: Database Migration

**Files:**
- Create: `database/migrations/2026_06_19_000000_add_reminder_fields.php`
- Modify: `app/Models/Game.php`
- Modify: `app/Models/UserSetting.php`

- [ ] **Step 1: Create migration file**

```php
<?php
// database/migrations/2026_06_19_000000_add_reminder_fields.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->boolean('reminder_sent')->default(false)->after('away_team_score');
        });

        Schema::table('user_settings', function (Blueprint $table) {
            $table->boolean('receive_reminders')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('reminder_sent');
        });
        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn('receive_reminders');
        });
    }
};
```

- [ ] **Step 2: Run migration**

```bash
php artisan migrate
```

Expected: `Migrating: 2026_06_19_000000_add_reminder_fields` then `Migrated`.

- [ ] **Step 3: Add `reminder_sent` to `Game::$fillable`**

In `app/Models/Game.php`, replace:

```php
protected $fillable = [
    'game_date',
    'event_id',
    'home_team_id',
    'away_team_id',
    'home_team_score',
    'away_team_score',
];
```

With:

```php
protected $fillable = [
    'game_date',
    'event_id',
    'home_team_id',
    'away_team_id',
    'home_team_score',
    'away_team_score',
    'reminder_sent',
];
```

- [ ] **Step 4: Add `receive_reminders` to `UserSetting::$fillable`**

In `app/Models/UserSetting.php`, replace:

```php
protected $fillable = [
    'admin', 'fee', 'color_id'
];
```

With:

```php
protected $fillable = [
    'admin', 'fee', 'color_id', 'receive_reminders'
];
```

- [ ] **Step 5: Run tests to confirm nothing broke**

```bash
php artisan test
```

Expected: All green.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_06_19_000000_add_reminder_fields.php \
        app/Models/Game.php \
        app/Models/UserSetting.php
git commit -m "feat: add reminder_sent to games and receive_reminders to user_settings"
```

---

## Task 2: Opt-in Notifications UI

**Files:**
- Create: `tests/Feature/NotificationPreferenceTest.php`
- Modify: `app/Http/Controllers/UserProfileController.php`
- Modify: `resources/views/userProfile.blade.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/NotificationPreferenceTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(bool $reminders = false): User
    {
        $user = User::factory()->create();
        UserSetting::create([
            'user_id'           => $user->id,
            'admin'             => 0,
            'receive_reminders' => $reminders,
        ]);
        return $user;
    }

    public function test_user_can_enable_reminders(): void
    {
        $user = $this->makeUser(false);

        $this->actingAs($user)
            ->post(route('profile.notifications'), ['receive_reminders' => '1'])
            ->assertRedirect(route('userProfile'));

        $this->assertTrue(
            (bool) UserSetting::where('user_id', $user->id)->value('receive_reminders')
        );
    }

    public function test_user_can_disable_reminders(): void
    {
        $user = $this->makeUser(true);

        $this->actingAs($user)
            ->post(route('profile.notifications'), []);

        $this->assertFalse(
            (bool) UserSetting::where('user_id', $user->id)->value('receive_reminders')
        );
    }

    public function test_unsubscribe_signed_url_disables_reminders(): void
    {
        $user = $this->makeUser(true);

        $url = URL::signedRoute('profile.notifications.unsubscribe', ['user' => $user->id]);

        $this->get($url)->assertRedirect();

        $this->assertFalse(
            (bool) UserSetting::where('user_id', $user->id)->value('receive_reminders')
        );
    }

    public function test_guest_cannot_post_notification_preferences(): void
    {
        $this->post(route('profile.notifications'), ['receive_reminders' => '1'])
            ->assertRedirect('/login');
    }
}
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
php artisan test tests/Feature/NotificationPreferenceTest.php
```

Expected: FAIL — route not found.

- [ ] **Step 3: Add new routes to `routes/web.php`**

Add inside the `auth` middleware group (after the existing profile routes at the top, around line 37):

```php
Route::post('profile/notifications', [UserProfileController::class, 'updateNotifications'])
    ->name('profile.notifications');
```

Add OUTSIDE any auth group (before `require __DIR__.'/auth.php'` at the bottom):

```php
Route::get('profile/notifications/unsubscribe/{user}', [UserProfileController::class, 'unsubscribe'])
    ->name('profile.notifications.unsubscribe')
    ->middleware('signed');

Route::get('prediction/game/{gameID}', [PredictionResultController::class, 'showSingleGame'])
    ->name('prediction.game.single')
    ->middleware('auth');
```

Also add `use App\Http\Controllers\PredictionResultController;` at the top of `routes/web.php` if not already present (it is — check line 5).

- [ ] **Step 4: Add `updateNotifications()` and `unsubscribe()` to `UserProfileController`**

Add these two methods to `app/Http/Controllers/UserProfileController.php` (before the closing `}`):

```php
public function updateNotifications(Request $request)
{
    $user = Auth::user();
    UserSetting::where('user_id', $user->id)->update([
        'receive_reminders' => $request->boolean('receive_reminders'),
    ]);
    return redirect()->route('userProfile')->with('info', 'Pranešimų nustatymai atnaujinti.');
}

public function unsubscribe(Request $request, int $user): \Illuminate\Http\RedirectResponse
{
    UserSetting::where('user_id', $user)->update(['receive_reminders' => false]);
    return redirect('/')->with('info', 'Pranešimai išjungti sėkmingai.');
}
```

Add `use App\Models\UserSetting;` to the imports of `UserProfileController.php`.

- [ ] **Step 5: Run tests to confirm they pass**

```bash
php artisan test tests/Feature/NotificationPreferenceTest.php
```

Expected: 4 tests, 4 passed.

- [ ] **Step 6: Add notifications card to `resources/views/userProfile.blade.php`**

Insert this block after the closing `</div>` of the two-column row (after line 230, before the Danger Zone card):

```blade
{{-- Notification preferences --}}
<div class="sb-card mb-3">
    <div class="sb-card-title"><i class="bi bi-bell me-1"></i>Pranešimai</div>
    <form method="POST" action="{{ route('profile.notifications') }}">
        @csrf
        <p class="text-muted mb-3" style="font-size:.82rem">
            Gauti priminimus el. paštu apie artėjančias rungtynes prieš pat žaidimo pradžią.
        </p>
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch"
                   id="receive_reminders" name="receive_reminders" value="1"
                   {{ $user->userSetting?->receive_reminders ? 'checked' : '' }}>
            <label class="form-check-label" for="receive_reminders">Įjungti priminimus</label>
        </div>
        <div class="text-end">
            <button type="submit" class="btn btn-primary btn-sm px-4">
                <i class="bi bi-check2 me-1"></i>Išsaugoti
            </button>
        </div>
    </form>
</div>
```

- [ ] **Step 7: Run all tests**

```bash
php artisan test
```

Expected: All green.

- [ ] **Step 8: Commit**

```bash
git add tests/Feature/NotificationPreferenceTest.php \
        app/Http/Controllers/UserProfileController.php \
        resources/views/userProfile.blade.php \
        routes/web.php
git commit -m "feat: add opt-in email reminder preference to user profile"
```

---

## Task 3: Standalone Single-Game Prediction Page

**Files:**
- Create: `tests/Feature/SingleGamePredictionTest.php`
- Modify: `app/Http/Controllers/PredictionResultController.php`
- Create: `resources/views/prediction/game-single.blade.php`

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/SingleGamePredictionTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\PredictionResult;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleGamePredictionTest extends TestCase
{
    use RefreshDatabase;

    private function makeGame(bool $started = false): Game
    {
        $event = Event::create([
            'event' => 'Test', 'event_day' => 1,
            'event_survival' => 0, 'active' => 1, 'rate' => 1,
        ]);
        $home = Team::create(['team' => 'Home' . uniqid()]);
        $away = Team::create(['team' => 'Away' . uniqid()]);
        return Game::create([
            'event_id'     => $event->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'game_date'    => $started
                ? now()->subHours(2)->toDateTimeString()
                : now()->addHours(3)->toDateTimeString(),
        ]);
    }

    public function test_authenticated_user_can_view_single_game_page(): void
    {
        $game = $this->makeGame();
        $user = User::factory()->create();
        PredictionResult::create(['user_id' => $user->id, 'game_id' => $game->id]);

        $this->actingAs($user)
            ->get(route('prediction.game.single', $game->id))
            ->assertOk()
            ->assertViewIs('prediction.game-single')
            ->assertViewHas('locked', false);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $game = $this->makeGame();

        $this->get(route('prediction.game.single', $game->id))
            ->assertRedirect('/login');
    }

    public function test_started_game_is_shown_as_locked(): void
    {
        $game = $this->makeGame(started: true);
        $user = User::factory()->create();
        PredictionResult::create(['user_id' => $user->id, 'game_id' => $game->id]);

        $this->actingAs($user)
            ->get(route('prediction.game.single', $game->id))
            ->assertOk()
            ->assertViewHas('locked', true);
    }

    public function test_nonexistent_game_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('prediction.game.single', 9999))
            ->assertNotFound();
    }
}
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
php artisan test tests/Feature/SingleGamePredictionTest.php
```

Expected: FAIL — method `showSingleGame` does not exist.

- [ ] **Step 3: Add `showSingleGame()` to `PredictionResultController`**

Add this method to `app/Http/Controllers/PredictionResultController.php` (before the final `}`):

```php
public function showSingleGame(int $gameID): \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
{
    $game = Game::with('home_team', 'away_team')->findOrFail($gameID);

    $sessionController = new SessionController();
    $sessionController->setSession(\Illuminate\Support\Facades\Auth::user());

    $userID = session('userID');
    $now    = Carbon::now('UTC')->format('Y-m-d H:i:s');
    $locked = $game->game_date <= $now;

    $prediction = PredictionResult::where('game_id', $gameID)
        ->where('user_id', $userID)
        ->first();

    return response(view('prediction.game-single', compact('game', 'locked', 'prediction')));
}
```

`SessionController` is in the same `App\Http\Controllers` namespace — no `use` import needed. The fully-qualified `\Illuminate\Support\Facades\Auth::user()` call also needs no import.

- [ ] **Step 4: Create the standalone prediction view**

Create `resources/views/prediction/game-single.blade.php`:

```blade
@extends('layouts.master')
@section('content')

@if(session('info'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('info') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="sb-card">
    <div class="sb-card-title">
        <i class="bi bi-pencil-square me-1"></i>Spėjimas
    </div>

    {{-- Teams --}}
    <div class="d-flex align-items-center justify-content-center gap-4 my-4">
        <div class="text-center">
            <img src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($game->home_team->team)) . '.svg') }}"
                 width="52" height="52" alt="{{ $game->home_team->team }}">
            <div class="fw-semibold mt-2">{{ $game->home_team->team }}</div>
        </div>
        <div class="text-muted fw-bold fs-4">vs</div>
        <div class="text-center">
            <img src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($game->away_team->team)) . '.svg') }}"
                 width="52" height="52" alt="{{ $game->away_team->team }}">
            <div class="fw-semibold mt-2">{{ $game->away_team->team }}</div>
        </div>
    </div>

    {{-- Kickoff time --}}
    <div class="text-center text-muted mb-4" style="font-size:.85rem">
        <i class="bi bi-clock me-1"></i>
        {{ \Carbon\Carbon::parse($game->game_date, 'UTC')->setTimezone('Europe/Vilnius')->format('Y-m-d H:i') }} LT
    </div>

    @if($locked)
        <div class="alert alert-secondary text-center">
            <i class="bi bi-lock-fill me-1"></i>Žaidimas jau prasidėjo — spėjimų keisti negalima.
        </div>
        @if($prediction && $prediction->home_team_score !== null)
        <div class="text-center mt-3">
            <span class="fs-3 fw-bold">{{ $prediction->home_team_score }} : {{ $prediction->away_team_score }}</span>
            <div class="text-muted mt-1" style="font-size:.82rem">Jūsų spėjimas</div>
        </div>
        @endif
    @elseif($prediction)
        <form id="sg-form">
            @csrf
            <input type="hidden" name="gameID" value="{{ $game->id }}">
            <input type="hidden" name="prediction_gameID" value="{{ $prediction->id }}">
            <input type="hidden" name="gameWinnerID" value="{{ $prediction->game_winner_id ?? '' }}">

            <div class="d-flex align-items-center justify-content-center gap-3 mb-4">
                <input type="number" id="sg-home" name="homeTeamScore"
                       class="form-control text-center fw-bold fs-5"
                       style="width:80px" min="0" max="99"
                       value="{{ $prediction->home_team_score ?? '' }}"
                       placeholder="?">
                <span class="fw-bold fs-4 text-muted">:</span>
                <input type="number" id="sg-away" name="awayTeamScore"
                       class="form-control text-center fw-bold fs-5"
                       style="width:80px" min="0" max="99"
                       value="{{ $prediction->away_team_score ?? '' }}"
                       placeholder="?">
            </div>
            <div class="text-center">
                <button type="submit" id="sg-btn" class="btn btn-primary px-5">
                    <i class="bi bi-check2 me-1"></i>Išsaugoti spėjimą
                </button>
            </div>
        </form>
        <script>
        document.getElementById('sg-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('sg-btn');
            btn.disabled = true;
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const fd = new FormData(this);
            fd.set('_token', token);
            try {
                const res = await fetch('/prediction/results', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (res.ok) {
                    window.location = '{{ route("main") }}';
                } else {
                    btn.disabled = false;
                }
            } catch {
                btn.disabled = false;
            }
        });
        </script>
    @else
        <div class="alert alert-warning text-center">
            Spėjimas nerastas. Bandykite dar kartą nuo <a href="{{ route('main') }}">pagrindinio puslapio</a>.
        </div>
    @endif
</div>

@endsection
```

- [ ] **Step 5: Run tests**

```bash
php artisan test tests/Feature/SingleGamePredictionTest.php
```

Expected: 4 tests, 4 passed.

- [ ] **Step 6: Run all tests**

```bash
php artisan test
```

Expected: All green.

- [ ] **Step 7: Commit**

```bash
git add tests/Feature/SingleGamePredictionTest.php \
        app/Http/Controllers/PredictionResultController.php \
        resources/views/prediction/game-single.blade.php
git commit -m "feat: add standalone single-game prediction page"
```

---

## Task 4: Single-Game Prediction Modal on Main Page

**Files:**
- Modify: `app/Http/Controllers/PredictionResultController.php` (add two fields to SQL query)
- Modify: `resources/views/partials/games.blade.php`

No automated tests for the modal UI. The query change is covered by verifying the app renders without error.

- [ ] **Step 1: Add `prr.id AS prediction_id` and `prr.game_winner_id` to the SQL in `getPredictionResultsUserGroupEventDay`**

In `app/Http/Controllers/PredictionResultController.php`, find `getPredictionResultsUserGroupEventDay` (around line 128). The `DB::select(...)` query currently starts with:

```sql
SELECT
    g.id,
    g.game_date,
    ht.team AS home_team,
    at.team AS away_team,
    ht.id AS home_team_id,
    at.id AS away_team_id,
    g.home_team_score,
    g.away_team_score,
    prr.home_team_score AS p_home_team_score,
    prr.away_team_score AS p_away_team_score,
```

Replace the opening SELECT lines (after `DB::select('SELECT`) with:

```sql
SELECT
    g.id,
    prr.id AS prediction_id,
    prr.game_winner_id,
    g.game_date,
    ht.team AS home_team,
    at.team AS away_team,
    ht.id AS home_team_id,
    at.id AS away_team_id,
    g.home_team_score,
    g.away_team_score,
    prr.home_team_score AS p_home_team_score,
    prr.away_team_score AS p_away_team_score,
```

- [ ] **Step 2: Update `resources/views/partials/games.blade.php` to add modal and pencil button**

Replace the entire contents of `resources/views/partials/games.blade.php` with:

```blade
<div class="sb-card" x-data="predModal()">
    <div class="sb-card-title d-flex justify-content-between align-items-center">
        <span><i class="bi bi-calendar3"></i> Artimiausios rungtynės</span>
        <a href="{{ route('prediction.results') }}" class="upcoming-all-link">Visi spėjimai <i class="bi bi-arrow-right-short"></i></a>
    </div>
    <div class="upcoming-list">
        @foreach($predictionGames as $predictionGame)
        @php
            $g      = $predictionGame['gameDetails'];
            $played = $g->home_team_score !== null;
            $canPred = !$played && isset($g->prediction_id);
        @endphp
        <a href="{{ route('prediction.results') }}" class="upcoming-row">
            <span class="upcoming-date">
                <span>{{ \Carbon\Carbon::parse($g->game_date, 'UTC')->setTimezone('Europe/Vilnius')->format('d.m') }}</span>
                <span class="upcoming-time">{{ \Carbon\Carbon::parse($g->game_date, 'UTC')->setTimezone('Europe/Vilnius')->format('H:i') }}</span>
            </span>

            <span class="upcoming-team upcoming-home">
                <img src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($g->home_team)) . '.svg') }}"
                     class="upcoming-flag" alt="{{ $g->home_team }}">
                <span class="upcoming-name d-none d-md-inline">{{ $g->home_team }}</span>
            </span>

            <span class="upcoming-scores">
                @if($played)
                    <span class="usc-actual">{{ $g->home_team_score }}:{{ $g->away_team_score }}</span>
                    <span class="usc-sep">/</span>
                @endif
                <span id="usc-pred-{{ $g->id }}" class="usc-pred {{ $played ? '' : 'usc-pred-only' }}">{{ $g->p_home_team_score ?? '?' }}:{{ $g->p_away_team_score ?? '?' }}</span>
            </span>

            <span class="upcoming-team upcoming-away">
                <span class="upcoming-name d-none d-md-inline">{{ $g->away_team }}</span>
                <img src="{{ asset('img/teams/' . str_replace(' ', '%20', strtolower($g->away_team)) . '.svg') }}"
                     class="upcoming-flag" alt="{{ $g->away_team }}">
            </span>

            @php $streak = $played ? ($g->streak_bonus ?? 0) : 0; @endphp
            <span class="upcoming-pts {{ $played && ($g->full_points + $streak) > 0 ? 'upt-scored' : 'upt-empty' }}">
                {{ $played ? number_format($g->full_points + $streak, 1) : '' }}
                @if($streak > 0)
                    <span class="upt-streak"><i class="bi bi-fire"></i>+{{ number_format($streak, 1) }}</span>
                @endif
            </span>

            @if($canPred)
            <button type="button"
                    class="btn btn-sm btn-outline-primary upcoming-pred-btn"
                    title="Prognozuoti"
                    @click.prevent.stop="open(
                        {{ $g->id }},
                        {{ $g->prediction_id }},
                        '{{ addslashes($g->home_team) }}',
                        '{{ addslashes($g->away_team) }}',
                        {{ $g->p_home_team_score ?? 'null' }},
                        {{ $g->p_away_team_score ?? 'null' }},
                        {{ $g->game_winner_id ?? 'null' }}
                    )">
                <i class="bi bi-pencil-fill"></i>
            </button>
            @endif
        </a>
        @endforeach
    </div>

    {{-- Single-game prediction modal --}}
    <div class="modal fade" id="gamePredModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0 pb-1">
                    <span class="fw-semibold" x-text="homeTeam + ' vs ' + awayTeam"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="d-flex align-items-center justify-content-center gap-3">
                        <input type="number" x-model="homeScore"
                               class="form-control text-center fw-bold fs-5"
                               style="width:72px" min="0" max="99" placeholder="?">
                        <span class="fw-bold fs-4 text-muted">:</span>
                        <input type="number" x-model="awayScore"
                               class="form-control text-center fw-bold fs-5"
                               style="width:72px" min="0" max="99" placeholder="?">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-primary w-100" :disabled="saving" @click="save()">
                        <span x-show="!saving"><i class="bi bi-check2 me-1"></i>Išsaugoti</span>
                        <span x-show="saving">Saugoma...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function predModal() {
    return {
        gameID:       null,
        predictionID: null,
        homeTeam:     '',
        awayTeam:     '',
        homeScore:    null,
        awayScore:    null,
        winnerId:     null,
        saving:       false,

        open(gameID, predictionID, homeTeam, awayTeam, homeScore, awayScore, winnerId) {
            this.gameID       = gameID;
            this.predictionID = predictionID;
            this.homeTeam     = homeTeam;
            this.awayTeam     = awayTeam;
            this.homeScore    = homeScore;
            this.awayScore    = awayScore;
            this.winnerId     = winnerId;
            this.saving       = false;
            bootstrap.Modal.getOrCreateInstance(
                document.getElementById('gamePredModal')
            ).show();
        },

        async save() {
            this.saving = true;
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const fd = new FormData();
            fd.append('_token',           token);
            fd.append('gameID',           this.gameID);
            fd.append('prediction_gameID', this.predictionID);
            fd.append('homeTeamScore',    this.homeScore ?? '');
            fd.append('awayTeamScore',    this.awayScore ?? '');
            fd.append('gameWinnerID',     this.winnerId ?? '');

            try {
                const res = await fetch('/prediction/results', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (res.ok) {
                    // Update displayed prediction score in this row
                    const el = document.getElementById('usc-pred-' + this.gameID);
                    if (el) {
                        const h = this.homeScore !== null ? this.homeScore : '?';
                        const a = this.awayScore !== null ? this.awayScore : '?';
                        el.textContent = h + ':' + a;
                    }
                    bootstrap.Modal.getInstance(
                        document.getElementById('gamePredModal')
                    ).hide();
                }
            } finally {
                this.saving = false;
            }
        }
    };
}
</script>
```

- [ ] **Step 3: Run all tests**

```bash
php artisan test
```

Expected: All green.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/PredictionResultController.php \
        resources/views/partials/games.blade.php
git commit -m "feat: add single-game prediction modal to upcoming games widget"
```

---

## Task 5: PredictionReminder Mailable + Email View

**Files:**
- Create: `app/Mail/PredictionReminder.php`
- Create: `resources/views/emails/prediction-reminder.blade.php`

- [ ] **Step 1: Create `app/Mail/PredictionReminder.php`**

```php
<?php

namespace App\Mail;

use App\Models\Game;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class PredictionReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Game $game,
        public readonly User $recipient,
    ) {}

    public function envelope(): Envelope
    {
        $home = $this->game->home_team->team;
        $away = $this->game->away_team->team;
        $time = Carbon::parse($this->game->game_date, 'UTC')
            ->setTimezone('Europe/Vilnius')
            ->format('H:i');

        return new Envelope(
            to: $this->recipient->email,
            subject: "Prognozė: {$home} vs {$away} – {$time} LT",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.prediction-reminder',
            with: [
                'unsubscribeUrl' => URL::signedRoute(
                    'profile.notifications.unsubscribe',
                    ['user' => $this->recipient->id]
                ),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
```

- [ ] **Step 2: Create `resources/views/emails/prediction-reminder.blade.php`**

```blade
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family:sans-serif;background:#f0f0f0;margin:0;padding:20px">
<div style="max-width:480px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)">

    {{-- Header --}}
    <div style="background:#1a1a2e;padding:18px 24px;text-align:center">
        <span style="color:#fff;font-size:1rem;font-weight:700;letter-spacing:.5px">&#9917; SportBet</span>
    </div>

    {{-- Body --}}
    <div style="padding:28px 24px">
        <h2 style="margin:0 0 8px;font-size:1.05rem;color:#111">Nepamirškite prognozuoti!</h2>
        <p style="color:#555;font-size:.9rem;margin:0 0 20px">Artėja rungtynės — pateikite savo spėjimą laiku.</p>

        {{-- Match card --}}
        <div style="background:#f8f8f8;border-radius:8px;padding:16px 20px;text-align:center;margin-bottom:20px">
            <div style="display:flex;justify-content:center;align-items:center;gap:16px">
                <span style="font-weight:700;font-size:1rem">{{ $game->home_team->team }}</span>
                <span style="color:#aaa;font-size:.85rem">vs</span>
                <span style="font-weight:700;font-size:1rem">{{ $game->away_team->team }}</span>
            </div>
            <div style="color:#888;font-size:.82rem;margin-top:8px">
                &#128337;
                {{ \Carbon\Carbon::parse($game->game_date, 'UTC')->setTimezone('Europe/Vilnius')->format('Y-m-d H:i') }} LT
            </div>
        </div>

        {{-- CTA button --}}
        <div style="text-align:center;margin-bottom:24px">
            <a href="{{ route('prediction.game.single', $game->id) }}"
               style="display:inline-block;background:#198754;color:#fff;padding:13px 36px;border-radius:6px;text-decoration:none;font-weight:700;font-size:.95rem">
                Prognozuoti
            </a>
        </div>

        <hr style="border:none;border-top:1px solid #eee;margin:0 0 16px">

        <p style="color:#bbb;font-size:.72rem;text-align:center;margin:0">
            <a href="{{ $unsubscribeUrl }}" style="color:#bbb;text-decoration:underline">
                Atsisakyti priminimų
            </a>
        </p>
    </div>
</div>
</body>
</html>
```

Note: `$unsubscribeUrl` is passed via the `with:` array in `content()` so it is available as a plain variable in the Blade view.

- [ ] **Step 3: Run all tests**

```bash
php artisan test
```

Expected: All green.

- [ ] **Step 4: Commit**

```bash
git add app/Mail/PredictionReminder.php \
        resources/views/emails/prediction-reminder.blade.php
git commit -m "feat: add PredictionReminder mailable and email template"
```

---

## Task 6: SendPredictionReminders Artisan Command + Scheduler

**Files:**
- Create: `tests/Feature/PredictionReminderCommandTest.php`
- Create: `app/Console/Commands/SendPredictionReminders.php`
- Modify: `routes/console.php`

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/PredictionReminderCommandTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Console\Commands\SendPredictionReminders;
use App\Mail\PredictionReminder;
use App\Models\Event;
use App\Models\Game;
use App\Models\Team;
use App\Models\User;
use App\Models\UserSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PredictionReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    private function makeGame(string $gameDate): Game
    {
        $event = Event::create([
            'event' => 'Test', 'event_day' => 1,
            'event_survival' => 0, 'active' => 1, 'rate' => 1,
        ]);
        $home = Team::create(['team' => 'Home' . uniqid()]);
        $away = Team::create(['team' => 'Away' . uniqid()]);
        return Game::create([
            'event_id'     => $event->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'game_date'    => $gameDate,
        ]);
    }

    private function makeOptedInUser(): User
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        UserSetting::create([
            'user_id'           => $user->id,
            'admin'             => 0,
            'receive_reminders' => true,
        ]);
        return $user;
    }

    public function test_sends_reminder_to_opted_in_user_when_reminder_time_passed(): void
    {
        Mail::fake();
        // Now: 23:00 LT (20:00 UTC). Game at 23:30 LT (20:30 UTC).
        // Night game (hour=23 >= 22) → reminderTime = 21:00 LT (18:00 UTC).
        // 23:00 LT > 21:00 LT → send.
        Carbon::setTestNow('2026-06-19 20:00:00');
        $game = $this->makeGame('2026-06-19 20:30:00');
        $user = $this->makeOptedInUser();

        $this->artisan('reminders:send')->assertExitCode(0);

        Mail::assertQueued(PredictionReminder::class, fn($m) => $m->hasTo($user->email));
        $this->assertTrue((bool) $game->fresh()->reminder_sent);
    }

    public function test_does_not_send_when_reminder_time_not_yet_reached(): void
    {
        Mail::fake();
        // Now: 20:00 LT (17:00 UTC). Game at 23:30 LT (20:30 UTC).
        // reminderTime = 21:00 LT (18:00 UTC). 20:00 LT < 21:00 LT → skip.
        Carbon::setTestNow('2026-06-19 17:00:00');
        $game = $this->makeGame('2026-06-19 20:30:00');
        $this->makeOptedInUser();

        $this->artisan('reminders:send')->assertExitCode(0);

        Mail::assertNothingQueued();
        $this->assertFalse((bool) $game->fresh()->reminder_sent);
    }

    public function test_does_not_send_to_opted_out_user(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-06-19 20:00:00');
        $game = $this->makeGame('2026-06-19 20:30:00');
        $user = User::factory()->create(['email' => 'out@example.com']);
        UserSetting::create(['user_id' => $user->id, 'admin' => 0, 'receive_reminders' => false]);

        $this->artisan('reminders:send')->assertExitCode(0);

        Mail::assertNothingQueued();
    }

    public function test_does_not_resend_if_reminder_already_sent(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-06-19 20:00:00');
        $game = $this->makeGame('2026-06-19 20:30:00');
        $game->update(['reminder_sent' => true]);
        $this->makeOptedInUser();

        $this->artisan('reminders:send')->assertExitCode(0);

        Mail::assertNothingQueued();
    }

    public function test_skips_games_with_scores_already_entered(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-06-19 20:00:00');
        $game = $this->makeGame('2026-06-19 20:30:00');
        $game->update(['home_team_score' => 1, 'away_team_score' => 0]);
        $this->makeOptedInUser();

        $this->artisan('reminders:send')->assertExitCode(0);

        Mail::assertNothingQueued();
    }

    // Direct unit tests for computeReminderTime

    public function test_reminder_time_for_22xx_game_is_2100_same_day(): void
    {
        $cmd = new SendPredictionReminders();
        $gameTime = Carbon::parse('2026-06-19 22:45:00', 'Europe/Vilnius');
        $expected = Carbon::parse('2026-06-19 21:00:00', 'Europe/Vilnius');

        $this->assertEquals($expected->timestamp, $cmd->computeReminderTime($gameTime)->timestamp);
    }

    public function test_reminder_time_for_0300_game_is_2100_previous_day(): void
    {
        $cmd = new SendPredictionReminders();
        $gameTime = Carbon::parse('2026-06-20 03:00:00', 'Europe/Vilnius');
        $expected = Carbon::parse('2026-06-19 21:00:00', 'Europe/Vilnius');

        $this->assertEquals($expected->timestamp, $cmd->computeReminderTime($gameTime)->timestamp);
    }

    public function test_reminder_time_for_1800_game_is_1700_same_day(): void
    {
        $cmd = new SendPredictionReminders();
        $gameTime = Carbon::parse('2026-06-19 18:00:00', 'Europe/Vilnius');
        $expected = Carbon::parse('2026-06-19 17:00:00', 'Europe/Vilnius');

        $this->assertEquals($expected->timestamp, $cmd->computeReminderTime($gameTime)->timestamp);
    }
}
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
php artisan test tests/Feature/PredictionReminderCommandTest.php
```

Expected: FAIL — class `SendPredictionReminders` not found.

- [ ] **Step 3: Create `app/Console/Commands/SendPredictionReminders.php`**

```php
<?php

namespace App\Console\Commands;

use App\Mail\PredictionReminder;
use App\Models\Game;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendPredictionReminders extends Command
{
    protected $signature   = 'reminders:send';
    protected $description = 'Send email reminders for upcoming game predictions';

    public function handle(): int
    {
        $now = Carbon::now('Europe/Vilnius');

        $games = Game::with('home_team', 'away_team')
            ->whereNull('home_team_score')
            ->where('reminder_sent', false)
            ->get();

        foreach ($games as $game) {
            $gameTime    = Carbon::parse($game->game_date, 'UTC')->setTimezone('Europe/Vilnius');
            $reminderTime = $this->computeReminderTime($gameTime);

            if ($now->lt($reminderTime)) {
                continue;
            }

            // Game already started — mark sent and skip emailing
            if ($now->gte($gameTime)) {
                $game->reminder_sent = true;
                $game->save();
                continue;
            }

            $users = User::whereHas('userSetting', fn($q) => $q->where('receive_reminders', true))
                ->whereNotNull('email')
                ->get();

            foreach ($users as $user) {
                Mail::to($user->email)->queue(new PredictionReminder($game, $user));
            }

            $game->reminder_sent = true;
            $game->save();

            $this->info("Reminders queued for: {$game->home_team->team} vs {$game->away_team->team}");
        }

        return Command::SUCCESS;
    }

    public function computeReminderTime(Carbon $gameTime): Carbon
    {
        $hour = (int) $gameTime->format('H');

        if ($hour >= 22) {
            return $gameTime->copy()->setTime(21, 0, 0);
        }

        if ($hour < 8) {
            return $gameTime->copy()->subDay()->setTime(21, 0, 0);
        }

        return $gameTime->copy()->subHour();
    }
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test tests/Feature/PredictionReminderCommandTest.php
```

Expected: 8 tests, 8 passed.

- [ ] **Step 5: Register scheduler in `routes/console.php`**

Replace the entire `routes/console.php` with:

```php
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('reminders:send')->everyFifteenMinutes();
```

- [ ] **Step 6: Run all tests**

```bash
php artisan test
```

Expected: All green.

- [ ] **Step 7: Commit**

```bash
git add tests/Feature/PredictionReminderCommandTest.php \
        app/Console/Commands/SendPredictionReminders.php \
        routes/console.php
git commit -m "feat: add SendPredictionReminders command with 15-min scheduler"
```

---

## Production Checklist (after deployment)

- [ ] Run `php artisan migrate` on the production server
- [ ] Set real SMTP credentials in `.env` (`MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`)
- [ ] Ensure `php artisan queue:work` is running (or Supervisor is configured)
- [ ] Ensure the Laravel scheduler cron is active:
  ```
  * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
  ```
