# Multi-Tournament Architecture Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a `tournaments` table, scope `events`/`teams`/`leagues` to it, replace the single-tournament welcome page with a tournament hub, and surface admin CRUD for tournaments — all without touching any existing prediction, points, or odds data.

**Architecture:** Chain scoping — only 3 tables get `tournament_id` FK columns; all prediction/points data scopes automatically through existing FK chains (games→events→tournament, standings→team→tournament, messages→league→tournament). `SessionController` derives `tournamentID` from the user's active league so existing downstream controllers are untouched.

**Tech Stack:** Laravel 11, PHP 8.2, MySQL (prod) / SQLite (tests), Blade/Alpine.js/Bootstrap 5

---

## File Map

**Create:**
- `database/migrations/2026_06_30_100000_create_tournaments_and_add_fks.php`
- `app/Models/Tournament.php`
- `app/Http/Controllers/TournamentController.php`
- `resources/views/tournaments/hub.blade.php`
- `resources/views/tournaments/show.blade.php`
- `resources/views/admin/tournaments/index.blade.php`
- `resources/views/admin/tournaments/form.blade.php`
- `tests/Feature/TournamentTest.php`
- `tests/Feature/TournamentSessionTest.php`

**Modify:**
- `app/Models/Event.php` — add `tournament_id` to fillable + `belongsTo`
- `app/Models/Team.php` — add `tournament_id` to fillable + `belongsTo`
- `app/Models/League.php` — add `tournament_id` to fillable + `belongsTo`
- `app/Http/Controllers/SessionController.php` — derive tournamentID, filter events, read survivalGame from tournament
- `app/Http/Controllers/MainController.php` — guest branch: redirect to hub
- `resources/views/partials/header.blade.php` — add "← Turnyrai" link
- `resources/views/admin/index.blade.php` — add Turnyrai admin tile
- `routes/web.php` — new tournament routes, change `/` to hub

---

## Task 1: Migration — tournaments table + FK columns + backfill

**Files:**
- Create: `database/migrations/2026_06_30_100000_create_tournaments_and_add_fks.php`
- Test: `tests/Feature/TournamentTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TournamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_tournaments_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('tournaments'));
    }

    public function test_tournaments_table_has_required_columns(): void
    {
        foreach (['id','name','slug','sport','status','start_date','end_date',
                  'description','cover_image','is_public','survival_game',
                  'created_at','updated_at'] as $col) {
            $this->assertTrue(Schema::hasColumn('tournaments', $col), "Missing column: $col");
        }
    }

    public function test_events_has_tournament_id(): void
    {
        $this->assertTrue(Schema::hasColumn('events', 'tournament_id'));
    }

    public function test_teams_has_tournament_id(): void
    {
        $this->assertTrue(Schema::hasColumn('teams', 'tournament_id'));
    }

    public function test_leagues_has_tournament_id(): void
    {
        $this->assertTrue(Schema::hasColumn('leagues', 'tournament_id'));
    }

    public function test_world_cup_2026_seeded_as_tournament_1(): void
    {
        $t = \Illuminate\Support\Facades\DB::table('tournaments')->where('id', 1)->first();
        $this->assertNotNull($t);
        $this->assertEquals('World Football Cup 2026', $t->name);
        $this->assertEquals('world-cup-2026', $t->slug);
        $this->assertEquals('active', $t->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```
php artisan test tests/Feature/TournamentTest.php
```

Expected: 5 FAIL — table and columns don't exist yet.

- [ ] **Step 3: Create the migration**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sport')->default('football');
            $table->enum('status', ['upcoming', 'active', 'finished'])->default('upcoming');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('survival_game')->default(false);
            $table->timestamps();
        });

        // Seed World Football Cup 2026 as tournament 1
        $survivalVal = (bool) DB::table('settings')
            ->where('setting', 'survivalGame')
            ->value('value');

        DB::table('tournaments')->insert([
            'name'          => 'World Football Cup 2026',
            'slug'          => 'world-cup-2026',
            'sport'         => 'football',
            'status'        => 'active',
            'is_public'     => true,
            'survival_game' => $survivalVal,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // Add nullable tournament_id to events, teams, leagues
        foreach (['events', 'teams', 'leagues'] as $tbl) {
            Schema::table($tbl, function (Blueprint $table) {
                $table->unsignedBigInteger('tournament_id')->nullable()->after('id');
                $table->foreign('tournament_id')->references('id')->on('tournaments');
            });
        }

        // Backfill all existing rows → tournament 1
        DB::table('events')->update(['tournament_id' => 1]);
        DB::table('teams')->update(['tournament_id' => 1]);
        DB::table('leagues')->update(['tournament_id' => 1]);

        // Make NOT NULL now that all rows are filled
        foreach (['events', 'teams', 'leagues'] as $tbl) {
            Schema::table($tbl, function (Blueprint $table) {
                $table->unsignedBigInteger('tournament_id')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['events', 'teams', 'leagues'] as $tbl) {
            Schema::table($tbl, function (Blueprint $table) {
                $table->dropForeign(['tournament_id']);
                $table->dropColumn('tournament_id');
            });
        }
        Schema::dropIfExists('tournaments');
    }
};
```

- [ ] **Step 4: Run migration**

```
php artisan migrate
```

Expected: runs without error.

- [ ] **Step 5: Run test to verify it passes**

```
php artisan test tests/Feature/TournamentTest.php
```

Expected: 5 PASS.

- [ ] **Step 6: Commit**

```
git add database/migrations/2026_06_30_100000_create_tournaments_and_add_fks.php tests/Feature/TournamentTest.php
git commit -m "feat: add tournaments table and tournament_id FKs to events, teams, leagues"
```

---

## Task 2: Tournament model + update Event, Team, League models

**Files:**
- Create: `app/Models/Tournament.php`
- Modify: `app/Models/Event.php`, `app/Models/Team.php`, `app/Models/League.php`
- Test: `tests/Feature/TournamentTest.php` (extend)

- [ ] **Step 1: Write the failing tests** (append to `tests/Feature/TournamentTest.php`)

```php
    public function test_tournament_model_creates_and_reads(): void
    {
        $t = \App\Models\Tournament::create([
            'name'   => 'Test Cup',
            'slug'   => 'test-cup',
            'sport'  => 'football',
            'status' => 'upcoming',
        ]);
        $this->assertDatabaseHas('tournaments', ['slug' => 'test-cup']);
        $this->assertEquals('Test Cup', \App\Models\Tournament::where('slug','test-cup')->first()->name);
    }

    public function test_league_belongs_to_tournament(): void
    {
        $t = \App\Models\Tournament::create(['name'=>'T','slug'=>'t','sport'=>'football','status'=>'upcoming']);
        $league = \App\Models\League::create(['name'=>'L','is_public'=>false,'tournament_id'=>$t->id]);
        $this->assertEquals($t->id, $league->tournament->id);
    }

    public function test_event_belongs_to_tournament(): void
    {
        $t = \App\Models\Tournament::create(['name'=>'T2','slug'=>'t2','sport'=>'football','status'=>'upcoming']);
        $event = \App\Models\Event::create([
            'event'=>'Round 1','event_day'=>1,'event_survival'=>0,'active'=>1,'rate'=>1,'tournament_id'=>$t->id
        ]);
        $this->assertEquals($t->id, $event->tournament->id);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

```
php artisan test tests/Feature/TournamentTest.php --filter="model|belongs_to"
```

Expected: FAIL — Tournament class does not exist.

- [ ] **Step 3: Create `app/Models/Tournament.php`**

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    protected $fillable = [
        'name', 'slug', 'sport', 'status',
        'start_date', 'end_date', 'description',
        'cover_image', 'is_public', 'survival_game',
    ];

    protected $casts = [
        'is_public'      => 'boolean',
        'survival_game'  => 'boolean',
        'start_date'     => 'date',
        'end_date'       => 'date',
    ];

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function leagues()
    {
        return $this->hasMany(League::class);
    }
}
```

- [ ] **Step 4: Update `app/Models/Event.php`**

Replace the existing `$fillable` array and add the relationship:

```php
protected $fillable = [
    'event', 'event_day', 'event_survival', 'is_knockout', 'active', 'rate', 'tournament_id',
];

public function tournament()
{
    return $this->belongsTo(Tournament::class);
}
```

- [ ] **Step 5: Update `app/Models/Team.php`**

Read the current file first, then add `tournament_id` to `$fillable` and add the relationship method:

```php
// Add to $fillable:
'tournament_id',

// Add method:
public function tournament()
{
    return $this->belongsTo(Tournament::class);
}
```

- [ ] **Step 6: Update `app/Models/League.php`**

Add `tournament_id` to `$fillable`:

```php
protected $fillable = [
    'name', 'description', 'is_public', 'owner_id',
    'base_fee', 'penalty_step', 'use_league_odds', 'reward_description', 'tournament_id',
];
```

Add the relationship method:

```php
public function tournament()
{
    return $this->belongsTo(Tournament::class);
}
```

- [ ] **Step 7: Run tests to verify they pass**

```
php artisan test tests/Feature/TournamentTest.php
```

Expected: all PASS.

- [ ] **Step 8: Run full suite to verify no regressions**

```
php artisan test
```

Expected: same 25 pre-existing failures, no new failures.

- [ ] **Step 9: Commit**

```
git add app/Models/Tournament.php app/Models/Event.php app/Models/Team.php app/Models/League.php tests/Feature/TournamentTest.php
git commit -m "feat: add Tournament model and tournament_id relationships to Event, Team, League"
```

---

## Task 3: SessionController — derive tournamentID, filter events, read survivalGame from tournament

**Files:**
- Modify: `app/Http/Controllers/SessionController.php`
- Test: `tests/Feature/TournamentSessionTest.php`

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/TournamentSessionTest.php`:

```php
<?php
namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\League;
use App\Models\LeagueMember;
use App\Models\Setting;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentSessionTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserInTournament(bool $survivalGame = false): array
    {
        $tournament = Tournament::create([
            'name' => 'Test Cup', 'slug' => 'test-cup',
            'sport' => 'football', 'status' => 'active',
            'survival_game' => $survivalGame,
        ]);
        $league = League::create(['name' => 'L', 'is_public' => false, 'tournament_id' => $tournament->id]);
        $user   = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $user->id, 'admin' => 0]);
        Setting::firstOrCreate(['setting' => 'timeDifference'], ['value' => 0]);
        LeagueMember::create([
            'league_id' => $league->id, 'user_id' => $user->id,
            'active' => true, 'is_guest' => false,
        ]);
        return [$user, $tournament, $league];
    }

    public function test_set_session_puts_tournament_id_in_session(): void
    {
        [$user, $tournament] = $this->makeUserInTournament();
        (new \App\Http\Controllers\SessionController())->setSession($user);
        $this->assertEquals($tournament->id, session('tournamentID'));
    }

    public function test_survival_game_comes_from_tournament_not_settings(): void
    {
        [$user] = $this->makeUserInTournament(survivalGame: true);
        // Deliberately set settings to opposite value
        Setting::firstOrCreate(['setting' => 'survivalGame'], ['value' => 0]);
        (new \App\Http\Controllers\SessionController())->setSession($user);
        $this->assertEquals(1, session('survivalGame'));
    }

    public function test_event_lookup_scoped_to_tournament(): void
    {
        [$user, $tournament] = $this->makeUserInTournament();

        $otherTournament = Tournament::create([
            'name' => 'Other Cup', 'slug' => 'other-cup',
            'sport' => 'football', 'status' => 'active', 'survival_game' => false,
        ]);
        $otherEvent = Event::create([
            'event' => 'Other Round', 'event_day' => 1, 'event_survival' => 0,
            'active' => 1, 'rate' => 1, 'tournament_id' => $otherTournament->id,
        ]);
        $otherTeamA = Team::create(['team' => 'X', 'tournament_id' => $otherTournament->id]);
        $otherTeamB = Team::create(['team' => 'Y', 'tournament_id' => $otherTournament->id]);
        Game::create([
            'game_date' => now()->addDay(),
            'event_id' => $otherEvent->id,
            'home_team_id' => $otherTeamA->id,
            'away_team_id' => $otherTeamB->id,
        ]);

        (new \App\Http\Controllers\SessionController())->setSession($user);
        // eventID must be 0 because no games exist in the user's tournament
        $this->assertEquals(0, session('eventID'));
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```
php artisan test tests/Feature/TournamentSessionTest.php
```

Expected: 3 FAIL.

- [ ] **Step 3: Update `app/Http/Controllers/SessionController.php`**

Replace the entire file:

```php
<?php
namespace App\Http\Controllers;

use App\Models\LeagueMember;
use App\Models\Tournament;
use App\Models\UserSetting;
use App\Models\Setting;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use DateTime;

class SessionController extends Controller
{
    public function setSession($user): void
    {
        $userSettings = UserSetting::where('user_id', $user->id)->firstOrFail();
        $leagueMember = LeagueMember::where('user_id', $user->id)
            ->where('active', true)
            ->with('league')
            ->firstOrFail();

        $leagueID     = $leagueMember->league_id;
        $tournamentID = $leagueMember->league->tournament_id;
        $tournament   = Tournament::findOrFail($tournamentID);

        $hasGames = DB::table('games')
            ->join('events', 'games.event_id', '=', 'events.id')
            ->where('events.tournament_id', $tournamentID)
            ->exists();

        if ($hasGames) {
            $event = DB::table('games')
                ->join('events', 'games.event_id', '=', 'events.id')
                ->select('events.id', 'events.event_survival', 'events.rate')
                ->whereNull('games.home_team_score')
                ->where('events.tournament_id', $tournamentID)
                ->first();

            if (empty($event)) {
                $eventID = 0; $eventSurvival = 0; $eventRate = 0;
            } else {
                $eventID       = $event->id;
                $eventSurvival = $event->event_survival;
                $eventRate     = $event->rate;
            }

            $firstGame = DB::table('games')
                ->join('events', 'games.event_id', '=', 'events.id')
                ->where('events.tournament_id', $tournamentID)
                ->orderBy('games.game_date')
                ->select('games.game_date')
                ->first();

            $firstGameDate = new DateTime($firstGame->game_date);
            $disabled = strtotime('-0 day', $firstGameDate->getTimestamp()) < time() ? 'disabled' : '';
        } else {
            $eventID = 0; $eventSurvival = 0; $eventRate = 0; $disabled = '';
        }

        $timeDifference = Setting::where('setting', 'timeDifference')->first();

        Session::put('tournamentID',   $tournamentID);
        Session::put('active',         $user->active);
        Session::put('eventID',        $eventID);
        Session::put('eventSurvival',  $eventSurvival);
        Session::put('eventRate',      $eventRate);
        Session::put('disabled',       $disabled);
        Session::put('userID',         $user->id);
        Session::put('resultAmount',   $userSettings->result_amount);
        Session::put('leagueID',       $leagueID);
        Session::put('admin',          $userSettings->admin);
        Session::put('fee',            $leagueMember->league->base_fee);
        Session::put('guest',          (int) $leagueMember->is_guest);
        Session::put('survivalGame',   $tournament->survival_game ? 1 : 0);
        Session::put('timeDifference', $timeDifference?->value ?? 0);
    }
}
```

- [ ] **Step 4: Run new tests to verify they pass**

```
php artisan test tests/Feature/TournamentSessionTest.php
```

Expected: 3 PASS.

- [ ] **Step 5: Run full suite to check for regressions**

```
php artisan test
```

The existing `test_set_session_puts_league_id_in_session` in `LeagueFoundationTest.php` will fail because it does not set up a tournament. Fix it by adding tournament setup. Open `tests/Feature/LeagueFoundationTest.php` and update `test_set_session_puts_league_id_in_session`:

```php
public function test_set_session_puts_league_id_in_session(): void
{
    $user = \App\Models\User::factory()->create();
    \App\Models\UserSetting::factory()->create(['user_id' => $user->id, 'admin' => 0]);
    \App\Models\Setting::firstOrCreate(['setting' => 'timeDifference'], ['value' => 0]);

    $tournament = \App\Models\Tournament::create([
        'name' => 'T', 'slug' => 'test-t', 'sport' => 'football', 'status' => 'active',
    ]);
    $league = \App\Models\League::create([
        'name' => 'My League', 'is_public' => false, 'tournament_id' => $tournament->id,
    ]);
    \App\Models\LeagueMember::create([
        'league_id' => $league->id,
        'user_id'   => $user->id,
        'active'    => true,
        'is_guest'  => false,
    ]);

    $sessionController = new \App\Http\Controllers\SessionController();
    $sessionController->setSession($user);

    $this->assertEquals($league->id, session('leagueID'));
    $this->assertNull(session('groupID'));
    $this->assertEquals(0, session('guest'));
    $this->assertEquals($tournament->id, session('tournamentID'));
}
```

Also check `test_get_all_user_points_filters_by_league` — it calls `League::create` without `tournament_id`. Since the column is now NOT NULL, add it:

```php
// In test_get_all_user_points_filters_by_league, replace the League::create calls:
$tournament = \App\Models\Tournament::create(['name'=>'T','slug'=>'t-pts','sport'=>'football','status'=>'active']);
$leagueA = \App\Models\League::create(['name' => 'League A', 'is_public' => false, 'tournament_id' => $tournament->id]);
$leagueB = \App\Models\League::create(['name' => 'League B', 'is_public' => false, 'tournament_id' => $tournament->id]);
```

Scan all other tests that call `League::create` without `tournament_id` and add it (use the same `$tournament` from the data migration row, id=1, via `Tournament::first()` or just hardcode `'tournament_id' => 1` where `RefreshDatabase` runs migrations and seeds tournament 1).

- [ ] **Step 6: Run full suite confirming same 25 pre-existing failures**

```
php artisan test
```

Expected: same 25 pre-existing failures from `SqlInjectionRegressionTest`, no new failures.

- [ ] **Step 7: Commit**

```
git add app/Http/Controllers/SessionController.php tests/Feature/TournamentSessionTest.php tests/Feature/LeagueFoundationTest.php
git commit -m "feat: scope SessionController event/survivalGame lookups to tournament"
```

---

## Task 4: TournamentController + routes

**Files:**
- Create: `app/Http/Controllers/TournamentController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/TournamentTest.php` (extend)

- [ ] **Step 1: Write failing tests** (append to `tests/Feature/TournamentTest.php`)

```php
    public function test_hub_route_returns_200_for_guests(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_tournament_enter_redirects_to_login_for_guest(): void
    {
        $tournament = \App\Models\Tournament::create([
            'name' => 'T', 'slug' => 'test-slug', 'sport' => 'football', 'status' => 'active',
        ]);
        $response = $this->post('/tournament/test-slug/enter');
        $response->assertRedirect('/login');
    }

    public function test_tournament_exit_clears_session(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);
        session(['tournamentID' => 1, 'leagueID' => 1]);

        $this->get('/tournaments/exit');

        $this->assertNull(session('tournamentID'));
        $this->assertNull(session('leagueID'));
    }
```

- [ ] **Step 2: Run tests to verify they fail**

```
php artisan test tests/Feature/TournamentTest.php --filter="hub_route|tournament_enter|tournament_exit"
```

Expected: 3 FAIL.

- [ ] **Step 3: Create `app/Http/Controllers/TournamentController.php`**

```php
<?php
namespace App\Http\Controllers;

use App\Models\League;
use App\Models\LeagueMember;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class TournamentController extends Controller
{
    public function hub(): View
    {
        $tournaments = Tournament::orderByRaw("FIELD(status, 'active', 'upcoming', 'finished')")
            ->orderBy('start_date')
            ->get();

        $myLeaguesByTournament = collect();
        if (Auth::check()) {
            $myLeaguesByTournament = LeagueMember::where('user_id', session('userID'))
                ->where('active', true)
                ->with('league')
                ->get()
                ->keyBy(fn($m) => $m->league->tournament_id);
        }

        return view('tournaments.hub', compact('tournaments', 'myLeaguesByTournament'));
    }

    public function enter(string $slug): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $tournament  = Tournament::where('slug', $slug)->firstOrFail();
        $membership  = LeagueMember::where('user_id', session('userID'))
            ->where('active', true)
            ->whereHas('league', fn($q) => $q->where('tournament_id', $tournament->id))
            ->with('league')
            ->first();

        Session::put('tournamentID', $tournament->id);

        if ($membership) {
            Session::put('leagueID', $membership->league_id);
            return redirect()->route('main');
        }

        return redirect()->route('tournament.show', $slug);
    }

    public function exit(): RedirectResponse
    {
        Session::forget(['tournamentID', 'leagueID']);
        return redirect()->route('tournaments.hub');
    }

    public function show(string $slug): View
    {
        $tournament = Tournament::where('slug', $slug)->firstOrFail();

        $participantCount = LeagueMember::whereHas(
            'league', fn($q) => $q->where('tournament_id', $tournament->id)
        )->where('is_guest', false)->distinct('user_id')->count('user_id');

        return view('tournaments.show', compact('tournament', 'participantCount'));
    }

    // ── Admin ──────────────────────────────────────────────────────────────

    public function adminIndex(): View
    {
        $tournaments = Tournament::orderByDesc('created_at')->get();
        return view('admin.tournaments.index', compact('tournaments'));
    }

    public function adminCreate(): View
    {
        return view('admin.tournaments.form', ['tournament' => new Tournament()]);
    }

    public function adminStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'slug'          => 'required|string|max:100|unique:tournaments,slug|regex:/^[a-z0-9-]+$/',
            'sport'         => 'required|string|max:50',
            'status'        => 'required|in:upcoming,active,finished',
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
            'description'   => 'nullable|string|max:1000',
            'cover_image'   => 'nullable|string|max:255',
            'is_public'     => 'boolean',
            'survival_game' => 'boolean',
        ]);
        $data['is_public']     = $request->boolean('is_public');
        $data['survival_game'] = $request->boolean('survival_game');

        Tournament::create($data);
        return redirect()->route('admin.tournaments')->with('info', 'Turnyras sukurtas.');
    }

    public function adminEdit(Tournament $tournament): View
    {
        return view('admin.tournaments.form', compact('tournament'));
    }

    public function adminUpdate(Request $request, Tournament $tournament): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'slug'          => 'required|string|max:100|regex:/^[a-z0-9-]+$/|unique:tournaments,slug,'.$tournament->id,
            'sport'         => 'required|string|max:50',
            'status'        => 'required|in:upcoming,active,finished',
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
            'description'   => 'nullable|string|max:1000',
            'cover_image'   => 'nullable|string|max:255',
            'is_public'     => 'boolean',
            'survival_game' => 'boolean',
        ]);
        $data['is_public']     = $request->boolean('is_public');
        $data['survival_game'] = $request->boolean('survival_game');

        $tournament->update($data);
        return redirect()->route('admin.tournaments')->with('info', 'Turnyras atnaujintas.');
    }

    public function adminEnterContext(Tournament $tournament): RedirectResponse
    {
        Session::put('tournamentID', $tournament->id);
        return redirect()->route('admin.index')->with('info', 'Turnyro kontekstas: '.$tournament->name);
    }
}
```

- [ ] **Step 4: Update `routes/web.php`**

Add the import at the top:

```php
use App\Http\Controllers\TournamentController;
```

Replace this line:
```php
Route::get('/', [MainController::class,  'loadApp'])->name('/');
```
with:
```php
Route::get('/', [TournamentController::class, 'hub'])->name('tournaments.hub');
```

Add public tournament routes (before the `auth` middleware group):

```php
Route::get('/tournament/{slug}', [TournamentController::class, 'show'])->name('tournament.show');
Route::post('/tournament/{slug}/enter', [TournamentController::class, 'enter'])->name('tournament.enter');
Route::get('/tournaments/exit', [TournamentController::class, 'exit'])->name('tournaments.exit');
```

Add admin tournament routes inside the `superadmin` middleware group:

```php
Route::get('tournaments', [TournamentController::class, 'adminIndex'])->name('admin.tournaments');
Route::get('tournaments/create', [TournamentController::class, 'adminCreate'])->name('admin.tournaments.create');
Route::post('tournaments', [TournamentController::class, 'adminStore'])->name('admin.tournaments.store');
Route::get('tournaments/{tournament}/edit', [TournamentController::class, 'adminEdit'])->name('admin.tournaments.edit');
Route::post('tournaments/{tournament}', [TournamentController::class, 'adminUpdate'])->name('admin.tournaments.update');
Route::post('tournaments/{tournament}/context', [TournamentController::class, 'adminEnterContext'])->name('admin.tournaments.context');
```

- [ ] **Step 5: Update `MainController::loadApp()` — guest branch**

In `app/Http/Controllers/MainController.php`, find the guest `else` block and replace:

```php
} else {
    $games = Game::with('away_team')->with('home_team')->take(9)->get();
    return view('main')->with('games',$games);
}
```

with:

```php
} else {
    return redirect()->route('tournaments.hub');
}
```

- [ ] **Step 6: Run tests to verify they pass**

```
php artisan test tests/Feature/TournamentTest.php
```

Expected: all PASS.

- [ ] **Step 7: Commit**

```
git add app/Http/Controllers/TournamentController.php app/Http/Controllers/MainController.php routes/web.php tests/Feature/TournamentTest.php
git commit -m "feat: add TournamentController with hub, enter, exit, show, and admin CRUD routes"
```

---

## Task 5: Tournament hub view + tournament show view

**Files:**
- Create: `resources/views/tournaments/hub.blade.php`
- Create: `resources/views/tournaments/show.blade.php`

- [ ] **Step 1: Create directory**

```
mkdir resources\views\tournaments
```

- [ ] **Step 2: Create `resources/views/tournaments/hub.blade.php`**

```blade
@extends('layouts.master')
@section('content')

<div class="sb-card mb-3">
  <div class="sb-card-title">
    <i class="bi bi-globe2 sb-card-icon"></i> Turnyrai
  </div>
  <p style="font-size:.9rem;color:var(--sb-muted);margin:0 0 16px">
    Pasirinkite turnyrą, kuriame norite dalyvauti.
  </p>

  @foreach([['active','🔴 Vykstantys'],['upcoming','⏳ Artėjantys'],['finished','📁 Pasibaigę']] as [$status, $label])
  @php $group = $tournaments->where('status', $status); @endphp
  @if($group->isNotEmpty())
  <div style="font-weight:700;font-size:.88rem;margin-bottom:10px;margin-top:18px;">{{ $label }}</div>
  <div class="row g-3 mb-2">
    @foreach($group as $t)
    @php
      $membership  = $myLeaguesByTournament->get($t->id);
      $hasLeague   = !is_null($membership);
      $participantCount = \App\Models\LeagueMember::whereHas('league', fn($q)=>$q->where('tournament_id',$t->id))
                          ->where('is_guest',false)->distinct('user_id')->count('user_id');
    @endphp
    <div class="col-md-4 col-sm-6">
      <div class="sb-card h-100" style="{{ $status==='active' ? 'border:2px solid var(--sb-accent)' : '' }}">
        <div style="font-weight:700;font-size:1rem;margin-bottom:4px">{{ $t->name }}</div>
        <div style="font-size:.78rem;color:var(--sb-muted);margin-bottom:8px">
          {{ ucfirst($t->sport) }}
          @if($t->start_date) · {{ $t->start_date->format('Y') }} @endif
          · {{ $participantCount }} dalyviai
        </div>
        @if($hasLeague)
        <span style="font-size:.72rem;background:var(--sb-accent);color:#fff;border-radius:4px;padding:2px 8px;display:inline-block;margin-bottom:10px">
          {{ $membership->league->name }}
        </span>
        @endif
        @if($t->description)
        <p style="font-size:.82rem;color:var(--sb-muted);margin-bottom:10px">{{ $t->description }}</p>
        @endif

        @auth
          @if($status === 'active' || $status === 'upcoming')
            <form method="POST" action="{{ route('tournament.enter', $t->slug) }}">
              @csrf
              <button class="sb-btn sb-btn-primary w-100">
                {{ $hasLeague ? 'Žaisti →' : 'Peržiūrėti / Prisijungti →' }}
              </button>
            </form>
          @else
            <a href="{{ route('tournament.show', $t->slug) }}" class="sb-btn sb-btn-secondary w-100">
              Peržiūrėti rezultatus →
            </a>
          @endif
        @else
          <a href="{{ route('login') }}" class="sb-btn sb-btn-primary w-100">Prisijungti →</a>
        @endauth
      </div>
    </div>
    @endforeach
  </div>
  @endif
  @endforeach

</div>

<p style="font-size:.78rem;opacity:.65;margin-top:8px;color:var(--sb-muted)">
  SportBet yra nemokamas pramoginis žaidimas — realių pinigų lažybų nėra.
</p>
@endsection
```

- [ ] **Step 3: Create `resources/views/tournaments/show.blade.php`**

```blade
@extends('layouts.master')
@section('content')

<div class="sb-card mb-3">
  <div class="sb-card-title">
    <i class="bi bi-globe2 sb-card-icon"></i> {{ $tournament->name }}
    <a href="{{ route('tournaments.hub') }}" class="ms-auto sb-btn sb-btn-ghost" style="font-size:.8rem">
      ← Turnyrai
    </a>
  </div>

  @if($tournament->description)
  <p style="color:var(--sb-muted);font-size:.9rem;margin-bottom:16px">{{ $tournament->description }}</p>
  @endif

  <div style="font-size:.85rem;color:var(--sb-muted);margin-bottom:20px">
    {{ ucfirst($tournament->sport) }}
    @if($tournament->start_date) · {{ $tournament->start_date->format('Y') }} @endif
    · {{ $participantCount }} dalyviai
  </div>

  @auth
  <div class="mb-3">
    <a href="{{ route('leagues.index') }}" class="sb-btn sb-btn-primary">
      <i class="bi bi-plus-circle"></i> Sukurti lygą šiame turnyre
    </a>
  </div>
  @else
  <a href="{{ route('login') }}" class="sb-btn sb-btn-primary">Prisijungti ir dalyvauti</a>
  @endauth
</div>

@endsection
```

- [ ] **Step 4: Verify hub renders in browser**

Start dev server:
```
php artisan serve
```

Visit `http://localhost:8000/` — you should see the tournament hub with World Football Cup 2026 card. Verify the "Žaisti →" button appears for logged-in users who have a league in that tournament.

- [ ] **Step 5: Commit**

```
git add resources/views/tournaments/
git commit -m "feat: add tournament hub and tournament intro views"
```

---

## Task 6: Navbar — "← Turnyrai" link

**Files:**
- Modify: `resources/views/partials/header.blade.php`

The "← Turnyrai" link must appear on **both desktop and mobile** only when `session('tournamentID')` is set (i.e., user is inside a tournament context).

- [ ] **Step 1: Add to desktop nav (right side, before league switcher)**

In `resources/views/partials/header.blade.php`, find the desktop right section (`sb-topnav-right`). Before the `@if(session('userID'))` league switcher block, add:

```blade
      @if(session('tournamentID'))
      <a href="{{ route('tournaments.exit') }}"
         class="sb-nav-pill sb-nav-pill--ghost"
         style="font-size:.8rem;margin-right:4px">
        ← Turnyrai
      </a>
      @endif
```

- [ ] **Step 2: Add to mobile nav (top of mobile collapse panel)**

In the mobile collapse panel (`#sbNavMobile`), add as the first group before the "Spėjimai" group:

```blade
    @if(session('tournamentID'))
    <div class="sb-mobile-group">
      <a class="sb-nav-link" href="{{ route('tournaments.exit') }}">
        <i class="bi bi-arrow-left"></i> ← Turnyrai
      </a>
    </div>
    @endif
```

- [ ] **Step 3: Verify in browser**

Log in, click "Žaisti →" on the hub, verify you land on `/main`. Verify "← Turnyrai" appears in the navbar. Click it — verify you land back at `/` hub and `← Turnyrai` disappears.

- [ ] **Step 4: Commit**

```
git add resources/views/partials/header.blade.php
git commit -m "feat: add back-to-tournaments nav link when inside tournament context"
```

---

## Task 7: Admin tournament tile + CRUD views

**Files:**
- Modify: `resources/views/admin/index.blade.php`
- Create: `resources/views/admin/tournaments/index.blade.php`
- Create: `resources/views/admin/tournaments/form.blade.php`

- [ ] **Step 1: Add Turnyrai tile to admin index**

In `resources/views/admin/index.blade.php`, add to the `$sections` array (before the closing `];`):

```php
        ['icon' => 'bi-globe2', 'label' => 'Turnyrai', 'route' => 'admin.tournaments', 'super' => true],
```

- [ ] **Step 2: Create `resources/views/admin/tournaments/` directory**

```
mkdir resources\views\admin\tournaments
```

- [ ] **Step 3: Create `resources/views/admin/tournaments/index.blade.php`**

```blade
@extends('admin.layouts.master')
@section('content')

<div class="sb-card-title mb-3">
  <i class="bi bi-globe2 sb-card-icon"></i> Turnyrai
  <a href="{{ route('admin.tournaments.create') }}" class="sb-btn sb-btn-primary ms-auto" style="font-size:.8rem">
    + Naujas turnyras
  </a>
</div>

@if(session('info'))
  <div class="alert alert-info">{{ session('info') }}</div>
@endif

<div class="table-responsive">
<table class="table">
  <thead>
    <tr>
      <th>#</th><th>Pavadinimas</th><th>Sportas</th><th>Statusas</th><th>Pradžia</th><th>Veiksmai</th>
    </tr>
  </thead>
  <tbody>
    @foreach($tournaments as $t)
    <tr>
      <td>{{ $t->id }}</td>
      <td><strong>{{ $t->name }}</strong><br><small class="text-muted">{{ $t->slug }}</small></td>
      <td>{{ $t->sport }}</td>
      <td>
        <span class="badge {{ $t->status==='active'?'bg-success':($t->status==='upcoming'?'bg-warning':'bg-secondary') }}">
          {{ $t->status }}
        </span>
      </td>
      <td>{{ $t->start_date?->format('Y-m-d') ?? '—' }}</td>
      <td class="d-flex gap-1">
        <a href="{{ route('admin.tournaments.edit', $t) }}" class="sb-btn sb-btn-secondary" style="font-size:.75rem">Redaguoti</a>
        <form method="POST" action="{{ route('admin.tournaments.context', $t) }}">
          @csrf
          <button class="sb-btn sb-btn-primary" style="font-size:.75rem" title="Dirbti šiame turnyre">▶ Kontekstas</button>
        </form>
      </td>
    </tr>
    @endforeach
  </tbody>
</table>
</div>

@endsection
```

- [ ] **Step 4: Create `resources/views/admin/tournaments/form.blade.php`**

```blade
@extends('admin.layouts.master')
@section('content')

<div class="sb-card-title mb-3">
  <i class="bi bi-globe2 sb-card-icon"></i>
  {{ $tournament->exists ? 'Redaguoti turnyrą' : 'Naujas turnyras' }}
</div>

@if($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </div>
@endif

@php
  $action = $tournament->exists
    ? route('admin.tournaments.update', $tournament)
    : route('admin.tournaments.store');
@endphp

<form method="POST" action="{{ $action }}" class="sb-card">
  @csrf
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Pavadinimas</label>
      <input class="form-control" name="name" value="{{ old('name', $tournament->name) }}" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Slug (URL)</label>
      <input class="form-control" name="slug" value="{{ old('slug', $tournament->slug) }}"
             placeholder="world-cup-2026" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Sportas</label>
      <input class="form-control" name="sport" value="{{ old('sport', $tournament->sport ?? 'football') }}" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Statusas</label>
      <select class="form-select" name="status">
        @foreach(['upcoming','active','finished'] as $s)
        <option value="{{ $s }}" {{ old('status', $tournament->status) === $s ? 'selected' : '' }}>{{ $s }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Pradžia</label>
      <input class="form-control" type="date" name="start_date"
             value="{{ old('start_date', $tournament->start_date?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-3">
      <label class="form-label">Pabaiga</label>
      <input class="form-control" type="date" name="end_date"
             value="{{ old('end_date', $tournament->end_date?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-3">
      <label class="form-label">Viršelio paveikslėlis (kelias)</label>
      <input class="form-control" name="cover_image" value="{{ old('cover_image', $tournament->cover_image) }}">
    </div>
    <div class="col-12">
      <label class="form-label">Aprašymas</label>
      <textarea class="form-control" name="description" rows="3">{{ old('description', $tournament->description) }}</textarea>
    </div>
    <div class="col-md-3 d-flex align-items-center gap-3 pt-2">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_public" value="1"
               {{ old('is_public', $tournament->is_public ?? true) ? 'checked' : '' }}>
        <label class="form-check-label">Viešas</label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="survival_game" value="1"
               {{ old('survival_game', $tournament->survival_game ?? false) ? 'checked' : '' }}>
        <label class="form-check-label">Išlikimas</label>
      </div>
    </div>
    <div class="col-12">
      <button type="submit" class="sb-btn sb-btn-primary">
        {{ $tournament->exists ? 'Atnaujinti' : 'Sukurti' }}
      </button>
      <a href="{{ route('admin.tournaments') }}" class="sb-btn sb-btn-secondary ms-2">Atšaukti</a>
    </div>
  </div>
</form>

@endsection
```

- [ ] **Step 5: Verify admin flow in browser**

Log in as superadmin, go to `/admin/index`, verify "Turnyrai" tile appears. Click it — list shows World Football Cup 2026. Click "Redaguoti" — form pre-fills. Click "▶ Kontekstas" — redirects to admin dashboard with info flash. Click "+ Naujas turnyras" — empty form. Submit a new tournament — it appears in the list.

- [ ] **Step 6: Commit**

```
git add resources/views/admin/index.blade.php resources/views/admin/tournaments/
git commit -m "feat: admin tournament management tile, list, create/edit form"
```

---

## Self-Review Checklist

**Spec coverage:**

| Spec requirement | Task that covers it |
|---|---|
| `tournaments` table with all columns | Task 1 |
| `tournament_id` on events, teams, leagues | Task 1 |
| Backfill existing data → tournament 1 (World Football Cup 2026) | Task 1 |
| Move `survivalGame` to `tournaments.survival_game` | Task 1 + Task 3 |
| Tournament model + relationships | Task 2 |
| SessionController: tournamentID, filtered event lookup | Task 3 |
| `timeDifference` stays global | Task 3 (reads from settings) |
| Hub route `/` with Active/Upcoming/Past | Task 4 + Task 5 |
| "Enter →" sets session + redirect | Task 4 |
| "← Turnyrai" clears session + redirect to hub | Task 4 + Task 6 |
| Tournament intro/join page | Task 4 + Task 5 |
| Admin: Turnyrai tile | Task 7 |
| Admin: create/edit tournament | Task 4 + Task 7 |
| Admin: enter tournament context | Task 4 + Task 7 |

All 13 spec requirements are covered.

**Type consistency:** `Tournament::create()` uses the same `$fillable` keys throughout. `tournament_id` column named consistently across migration, models, and tests.
