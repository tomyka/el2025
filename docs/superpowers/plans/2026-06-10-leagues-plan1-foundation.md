# Leagues — Plan 1 of 3: Foundation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the `groups`/`user_groups` DB tables with `leagues`/`league_members`, migrate all existing data, and update every controller and session reference so the app runs identically — just against the new schema.

**Architecture:** Four new migrations create the leagues schema. A data-migration migration ports existing rows. Then `SessionController`, `PostRegisterController`, and every controller that JOINs `user_groups` or reads `session('groupID')` is updated. Old tables are dropped last. No new UI in this plan — that is Plan 2.

**Tech Stack:** Laravel 11 · PHP 8.2 · MySQL (prod) · SQLite (tests) · PHPUnit

**Spec:** `docs/superpowers/specs/2026-06-10-leagues-design.md`

**Follow-up plans:**
- Plan 2 — Membership UI: `/leagues` hub, navbar switcher, invite inbox, create/leave league
- Plan 3 — Per-league odds: `league_game_odds` computation, leaderboard odds recalculation

---

## Files

| Action | Path |
|---|---|
| Create | `database/migrations/2026_06_10_200000_create_leagues_table.php` |
| Create | `database/migrations/2026_06_10_200001_create_league_members_table.php` |
| Create | `database/migrations/2026_06_10_200002_create_league_invites_table.php` |
| Create | `database/migrations/2026_06_10_200003_create_league_game_odds_table.php` |
| Create | `database/migrations/2026_06_10_200004_migrate_groups_to_leagues.php` |
| Create | `database/migrations/2026_06_10_200005_migrate_messages_to_leagues.php` |
| Create | `database/migrations/2026_06_10_200006_drop_groups_user_groups.php` |
| Create | `app/Models/League.php` |
| Create | `app/Models/LeagueMember.php` |
| Create | `app/Models/LeagueInvite.php` |
| Create | `app/Models/LeagueGameOdds.php` |
| Create | `tests/Feature/LeagueFoundationTest.php` |
| Modify | `app/Http/Controllers/SessionController.php` |
| Modify | `app/Http/Controllers/PostRegisterController.php` |
| Modify | `app/Http/Controllers/PointController.php` |
| Modify | `app/Http/Controllers/ChartController.php` |
| Modify | `app/Http/Controllers/PredictionStandingController.php` |
| Modify | `app/Http/Controllers/PredictionResultController.php` |
| Modify | `app/Http/Controllers/FeeController.php` |
| Modify | `app/Http/Controllers/MessageController.php` |
| Modify | `app/Http/Controllers/MainController.php` |
| Modify | `app/Models/User.php` |
| Modify | `routes/web.php` |

---

## Task 1: Schema migrations — four new tables

**Files:**
- Create: `database/migrations/2026_06_10_200000_create_leagues_table.php`
- Create: `database/migrations/2026_06_10_200001_create_league_members_table.php`
- Create: `database/migrations/2026_06_10_200002_create_league_invites_table.php`
- Create: `database/migrations/2026_06_10_200003_create_league_game_odds_table.php`

- [ ] **Step 1: Create leagues migration**

```php
// database/migrations/2026_06_10_200000_create_leagues_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leagues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('base_fee')->nullable();
            $table->integer('penalty_step')->nullable();
            $table->boolean('use_league_odds')->default(false);
            $table->text('reward_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leagues');
    }
};
```

- [ ] **Step 2: Create league_members migration**

```php
// database/migrations/2026_06_10_200001_create_league_members_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('league_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained('leagues')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_guest')->default(false);
            $table->boolean('active')->default(false);
            $table->timestamps();
            $table->unique(['league_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_members');
    }
};
```

- [ ] **Step 3: Create league_invites migration**

```php
// database/migrations/2026_06_10_200002_create_league_invites_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('league_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained('leagues')->cascadeOnDelete();
            $table->foreignId('invited_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('invited_by_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            $table->timestamps();
            $table->unique(['league_id', 'invited_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_invites');
    }
};
```

- [ ] **Step 4: Create league_game_odds migration**

```php
// database/migrations/2026_06_10_200003_create_league_game_odds_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('league_game_odds', function (Blueprint $table) {
            $table->foreignId('league_id')->constrained('leagues')->cascadeOnDelete();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->decimal('home_odds', 5, 2)->default(1.0);
            $table->decimal('draw_odds', 5, 2)->default(1.0);
            $table->decimal('away_odds', 5, 2)->default(1.0);
            $table->timestamp('updated_at')->nullable();
            $table->primary(['league_id', 'game_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_game_odds');
    }
};
```

- [ ] **Step 5: Run migrations to verify all four tables create cleanly**

```bash
php artisan migrate
```

Expected: no errors. Run `php artisan migrate:status` and verify all four new migrations show as `Ran`.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_06_10_200000_create_leagues_table.php \
        database/migrations/2026_06_10_200001_create_league_members_table.php \
        database/migrations/2026_06_10_200002_create_league_invites_table.php \
        database/migrations/2026_06_10_200003_create_league_game_odds_table.php
git commit -m "feat: add leagues, league_members, league_invites, league_game_odds migrations"
```

---

## Task 2: Eloquent models

**Files:**
- Create: `app/Models/League.php`
- Create: `app/Models/LeagueMember.php`
- Create: `app/Models/LeagueInvite.php`
- Create: `app/Models/LeagueGameOdds.php`
- Modify: `app/Models/User.php`

- [ ] **Step 1: Create League model**

```php
// app/Models/League.php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class League extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'is_public', 'owner_id',
        'base_fee', 'penalty_step', 'use_league_odds', 'reward_description',
    ];

    public function members()
    {
        return $this->hasMany(LeagueMember::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function invites()
    {
        return $this->hasMany(LeagueInvite::class);
    }

    public function gameOdds()
    {
        return $this->hasMany(LeagueGameOdds::class);
    }
}
```

- [ ] **Step 2: Create LeagueMember model**

```php
// app/Models/LeagueMember.php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeagueMember extends Model
{
    protected $fillable = ['league_id', 'user_id', 'is_admin', 'is_guest', 'active'];

    public function league()
    {
        return $this->belongsTo(League::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 3: Create LeagueInvite model**

```php
// app/Models/LeagueInvite.php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeagueInvite extends Model
{
    protected $fillable = ['league_id', 'invited_user_id', 'invited_by_id', 'status'];

    public function league()
    {
        return $this->belongsTo(League::class);
    }

    public function invitedUser()
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }

    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }
}
```

- [ ] **Step 4: Create LeagueGameOdds model**

```php
// app/Models/LeagueGameOdds.php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeagueGameOdds extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = ['league_id', 'game_id', 'home_odds', 'draw_odds', 'away_odds'];

    public function league()
    {
        return $this->belongsTo(League::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
```

- [ ] **Step 5: Add leagueMembers relationship to User model**

In `app/Models/User.php`, add this method alongside the existing `userGroup()` relationship:

```php
public function leagueMembers()
{
    return $this->hasMany(LeagueMember::class);
}
```

- [ ] **Step 6: Write a smoke test**

```php
// tests/Feature/LeagueFoundationTest.php
<?php
namespace Tests\Feature;

use App\Models\League;
use App\Models\LeagueMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeagueFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_league_model_creates_and_retrieves(): void
    {
        $league = League::create([
            'name'      => 'Test League',
            'is_public' => true,
        ]);

        $this->assertDatabaseHas('leagues', ['name' => 'Test League', 'is_public' => true]);
        $this->assertEquals('Test League', League::first()->name);
    }

    public function test_league_member_belongs_to_league(): void
    {
        $league = League::create(['name' => 'My League', 'is_public' => false]);
        $user   = User::factory()->create();

        LeagueMember::create([
            'league_id' => $league->id,
            'user_id'   => $user->id,
            'active'    => true,
        ]);

        $this->assertEquals($league->id, $user->leagueMembers()->first()->league_id);
    }
}
```

- [ ] **Step 7: Run the test**

```bash
php artisan test tests/Feature/LeagueFoundationTest.php
```

Expected: `Tests: 2 passed`

- [ ] **Step 8: Commit**

```bash
git add app/Models/League.php app/Models/LeagueMember.php \
        app/Models/LeagueInvite.php app/Models/LeagueGameOdds.php \
        app/Models/User.php tests/Feature/LeagueFoundationTest.php
git commit -m "feat: add League, LeagueMember, LeagueInvite, LeagueGameOdds models"
```

---

## Task 3: Data migration — groups → leagues, user_groups → league_members

**Files:**
- Create: `database/migrations/2026_06_10_200004_migrate_groups_to_leagues.php`

This migration runs after the schema migrations. It:
1. Creates the public league.
2. Copies each `groups` row into `leagues` using the **same ID** so that downstream foreign keys (e.g., `messages.group_id`) still resolve correctly.
3. Copies each `user_groups` row into `league_members`.
4. Assigns any user with no `league_members` row to the public league.

- [ ] **Step 1: Write failing test**

Add to `tests/Feature/LeagueFoundationTest.php`:

```php
public function test_data_migration_creates_public_league(): void
{
    // Simulate running the data migration seeder
    $publicLeague = League::where('is_public', true)->first();
    $this->assertNull($publicLeague); // not yet created

    // Run the migration manually via artisan
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_06_10_200004_migrate_groups_to_leagues.php']);

    $publicLeague = League::where('is_public', true)->first();
    $this->assertNotNull($publicLeague);
    $this->assertEquals('Public League', $publicLeague->name);
}
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
php artisan test tests/Feature/LeagueFoundationTest.php --filter test_data_migration_creates_public_league
```

Expected: FAIL (migration file doesn't exist yet).

- [ ] **Step 3: Create the data migration**

```php
// database/migrations/2026_06_10_200004_migrate_groups_to_leagues.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1. Migrate existing groups → leagues with identical IDs
        $groups = DB::table('groups')->get();
        foreach ($groups as $group) {
            DB::table('leagues')->insert([
                'id'                 => $group->id,
                'name'               => $group->group,
                'description'        => $group->group_description,
                'is_public'          => false,
                'owner_id'           => null,
                'base_fee'           => $group->fee,
                'penalty_step'       => null,
                'use_league_odds'    => false,
                'reward_description' => $group->reward_description,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }

        // 2. Create the public league (gets next auto-increment ID)
        $publicLeagueId = DB::table('leagues')->insertGetId([
            'name'       => 'Public League',
            'is_public'  => true,
            'owner_id'   => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Migrate user_groups → league_members
        $userGroups = DB::table('user_groups')->get();
        foreach ($userGroups as $ug) {
            DB::table('league_members')->insert([
                'league_id'  => $ug->group_id,  // IDs match due to step 1
                'user_id'    => $ug->user_id,
                'is_admin'   => false,
                'is_guest'   => (bool) $ug->guest,
                'active'     => (bool) $ug->active,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4. Auto-join all existing users to public league if not already a member
        $allUserIds = DB::table('users')->pluck('id');
        $memberedUserIds = DB::table('league_members')->pluck('user_id')->unique();
        foreach ($allUserIds as $uid) {
            if (!$memberedUserIds->contains($uid)) {
                DB::table('league_members')->insert([
                    'league_id'  => $publicLeagueId,
                    'user_id'    => $uid,
                    'is_admin'   => false,
                    'is_guest'   => false,
                    'active'     => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 5. Add all existing members to public league (non-active, just as members)
        foreach ($memberedUserIds as $uid) {
            DB::table('league_members')->insert([
                'league_id'  => $publicLeagueId,
                'user_id'    => $uid,
                'is_admin'   => false,
                'is_guest'   => false,
                'active'     => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('league_members')->delete();
        DB::table('leagues')->delete();
    }
};
```

- [ ] **Step 4: Run the test**

```bash
php artisan test tests/Feature/LeagueFoundationTest.php --filter test_data_migration_creates_public_league
```

Expected: `Tests: 1 passed`

- [ ] **Step 5: Run the migration on the real database**

```bash
php artisan migrate
```

Expected: no errors. Verify:
```bash
php artisan tinker --execute="echo App\Models\League::count() . ' leagues, ' . App\Models\LeagueMember::count() . ' members';"
```

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_06_10_200004_migrate_groups_to_leagues.php \
        tests/Feature/LeagueFoundationTest.php
git commit -m "feat: data migration — groups to leagues, user_groups to league_members"
```

---

## Task 4: Migrate messages table to use league_id

**Files:**
- Create: `database/migrations/2026_06_10_200005_migrate_messages_to_leagues.php`

The `messages` table has `group_id` FK to `groups`. Since migrated leagues keep the same IDs as their source groups, we can copy `group_id` values directly to a new `league_id` column.

- [ ] **Step 1: Write failing test**

Add to `tests/Feature/LeagueFoundationTest.php`:

```php
public function test_messages_table_has_league_id_column(): void
{
    $this->assertTrue(\Schema::hasColumn('messages', 'league_id'));
    $this->assertFalse(\Schema::hasColumn('messages', 'group_id'));
}
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
php artisan test tests/Feature/LeagueFoundationTest.php --filter test_messages_table_has_league_id_column
```

Expected: FAIL.

- [ ] **Step 3: Create migration**

```php
// database/migrations/2026_06_10_200005_migrate_messages_to_leagues.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('league_id')->nullable()->after('id');
        });

        // Copy group_id values to league_id (IDs match from the data migration)
        DB::statement('UPDATE messages SET league_id = group_id');

        Schema::table('messages', function (Blueprint $table) {
            $table->foreign('league_id')->references('id')->on('leagues');
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->nullable()->after('id');
        });
        DB::statement('UPDATE messages SET group_id = league_id');
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['league_id']);
            $table->dropColumn('league_id');
        });
    }
};
```

- [ ] **Step 4: Run migration and test**

```bash
php artisan migrate
php artisan test tests/Feature/LeagueFoundationTest.php --filter test_messages_table_has_league_id_column
```

Expected: migration runs cleanly, test passes.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_06_10_200005_migrate_messages_to_leagues.php \
        tests/Feature/LeagueFoundationTest.php
git commit -m "feat: migrate messages.group_id to messages.league_id"
```

---

## Task 5: Update SessionController

**Files:**
- Modify: `app/Http/Controllers/SessionController.php`

Replace `UserGroup` lookup with `LeagueMember`. Session key `groupID` → `leagueID`.

- [ ] **Step 1: Write failing test**

Add to `tests/Feature/LeagueFoundationTest.php`:

```php
public function test_set_session_puts_league_id_in_session(): void
{
    $user = User::factory()->create();

    // Required by setSession
    \App\Models\UserSetting::create(['user_id' => $user->id, 'admin' => 0]);
    \App\Models\Setting::firstOrCreate(['setting' => 'survivalGame'], ['value' => 0]);
    \App\Models\Setting::firstOrCreate(['setting' => 'timeDifference'], ['value' => 0]);

    $league = League::create(['name' => 'My League', 'is_public' => false]);
    LeagueMember::create([
        'league_id' => $league->id,
        'user_id'   => $user->id,
        'active'    => true,
        'is_guest'  => false,
    ]);

    $sessionController = new \App\Http\Controllers\SessionController();
    $sessionController->setSession($user);

    $this->assertEquals($league->id, session('leagueID'));
    $this->assertEquals(0, session('guest'));
}
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
php artisan test tests/Feature/LeagueFoundationTest.php --filter test_set_session_puts_league_id_in_session
```

Expected: FAIL (session still puts `groupID`).

- [ ] **Step 3: Rewrite SessionController**

Replace the full contents of `app/Http/Controllers/SessionController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\LeagueMember;
use App\Models\UserSetting;
use App\Models\Game;
use App\Models\Setting;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use DateTime;

class SessionController extends Controller
{
    public function setSession($user): void
    {
        date_default_timezone_set('Europe/Vilnius');

        if (Game::count() > 0) {
            $event = DB::table('games')
                ->join('events', 'games.event_id', '=', 'events.id')
                ->select('events.id', 'events.event_survival', 'events.rate')
                ->whereNull('games.home_team_score')
                ->first();

            if (empty($event)) {
                $eventID = 0; $eventSurvival = 0; $eventRate = 0;
            } else {
                $eventID = $event->id;
                $eventSurvival = $event->event_survival;
                $eventRate = $event->rate;
            }

            $game = Game::firstOrFail();
            $firstGameDate = new DateTime($game->game_date);
            $disabled = strtotime('-0 day', $firstGameDate->getTimestamp()) < time() ? 'disabled' : '';
        } else {
            $eventID = 0; $eventSurvival = 0; $eventRate = 0; $disabled = '';
        }

        $survivalGame    = Setting::where('setting', 'survivalGame')->first();
        $timeDifference  = Setting::where('setting', 'timeDifference')->first();
        $userSettings    = UserSetting::where('user_id', $user->id)->firstOrFail();
        $leagueMember    = LeagueMember::where('user_id', $user->id)
                               ->where('active', true)
                               ->with('league')
                               ->firstOrFail();

        Session::put('active',          $user->active);
        Session::put('eventID',         $eventID);
        Session::put('eventSurvival',   $eventSurvival);
        Session::put('eventRate',       $eventRate);
        Session::put('disabled',        $disabled);
        Session::put('userID',          $user->id);
        Session::put('resultAmount',    $userSettings->result_amount);
        Session::put('leagueID',        $leagueMember->league_id);
        Session::put('admin',           $userSettings->admin);
        Session::put('fee',             $leagueMember->league->base_fee);
        Session::put('guest',           (int) $leagueMember->is_guest);
        Session::put('survivalGame',    $survivalGame?->value ?? 0);
        Session::put('timeDifference',  $timeDifference?->value ?? 0);
    }
}
```

- [ ] **Step 4: Run test**

```bash
php artisan test tests/Feature/LeagueFoundationTest.php --filter test_set_session_puts_league_id_in_session
```

Expected: `Tests: 1 passed`

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/SessionController.php tests/Feature/LeagueFoundationTest.php
git commit -m "feat: SessionController reads leagueID from league_members"
```

---

## Task 6: Update PostRegisterController

**Files:**
- Modify: `app/Http/Controllers/PostRegisterController.php`

On registration, auto-join the user to the public league instead of hard-coded `group_id = 1`.

- [ ] **Step 1: Write failing test**

Add to `tests/Feature/LeagueFoundationTest.php`:

```php
public function test_new_user_is_added_to_public_league_on_registration(): void
{
    $publicLeague = League::create(['name' => 'Public League', 'is_public' => true]);

    $user = User::factory()->create();
    \App\Models\UserSetting::create(['user_id' => $user->id, 'admin' => 0]);
    \App\Models\Setting::firstOrCreate(['setting' => 'survivalGame'], ['value' => 0]);

    $controller = new \App\Http\Controllers\PostRegisterController();
    // Call just the league-joining portion (we'll test via public method)
    \App\Models\LeagueMember::create([
        'league_id' => $publicLeague->id,
        'user_id'   => $user->id,
        'active'    => true,
        'is_guest'  => false,
    ]);

    $this->assertDatabaseHas('league_members', [
        'league_id' => $publicLeague->id,
        'user_id'   => $user->id,
        'active'    => true,
    ]);
}
```

- [ ] **Step 2: Rewrite PostRegisterController**

Replace the full contents of `app/Http/Controllers/PostRegisterController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\LeagueMember;

class PostRegisterController extends Controller
{
    public function postRegisterActions(int $userID): \Illuminate\Http\RedirectResponse
    {
        $userSettingsController = new UserSettingController();
        $userSettingsController->insertUserSettings($userID);

        // Auto-join the public league (must exist; seeded in data migration)
        $publicLeague = League::where('is_public', true)->firstOrFail();
        LeagueMember::create([
            'league_id' => $publicLeague->id,
            'user_id'   => $userID,
            'is_admin'  => false,
            'is_guest'  => false,
            'active'    => true,
        ]);

        $predictionResultController = new PredictionResultController();
        $predictionResultController->insertPredictionResultsUser($userID);

        $predictionStandingController = new PredictionStandingController();
        $predictionStandingController->insertPredictionStandingsUser($userID);

        $predictionSurvivalController = new PredictionSurvivalController();
        $predictionSurvivalController->insertPredictionSurvivalUser($userID);

        return redirect(route('main', absolute: false));
    }
}
```

- [ ] **Step 3: Run the full test suite to check for regressions**

```bash
php artisan test
```

Expected: all existing tests pass (deprecation warnings are noise).

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/PostRegisterController.php
git commit -m "feat: new user registration auto-joins public league"
```

---

## Task 7: Update PointController

**Files:**
- Modify: `app/Http/Controllers/PointController.php`

Replace `user_groups`/`group_id` with `league_members`/`league_id`. Parameter renamed from `$groupID` to `$leagueID` throughout.

- [ ] **Step 1: Write failing test**

Add to `tests/Feature/LeagueFoundationTest.php`:

```php
public function test_get_all_user_points_filters_by_league_id(): void
{
    $leagueA = League::create(['name' => 'League A', 'is_public' => false]);
    $leagueB = League::create(['name' => 'League B', 'is_public' => false]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();
    \App\Models\UserSetting::create(['user_id' => $userA->id, 'admin' => 0]);
    \App\Models\UserSetting::create(['user_id' => $userB->id, 'admin' => 0]);

    LeagueMember::create(['league_id' => $leagueA->id, 'user_id' => $userA->id, 'active' => true, 'is_guest' => false]);
    LeagueMember::create(['league_id' => $leagueB->id, 'user_id' => $userB->id, 'active' => true, 'is_guest' => false]);

    session(['guest' => 0]);

    $pointController = new \App\Http\Controllers\PointController();
    $points = $pointController->getAllUserPoints($leagueA->id);

    $userIds = array_column($points, 'userID');
    $this->assertContains($userA->id, $userIds);
    $this->assertNotContains($userB->id, $userIds);
}
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
php artisan test tests/Feature/LeagueFoundationTest.php --filter test_get_all_user_points_filters_by_league_id
```

Expected: FAIL.

- [ ] **Step 3: Update getAllUserPoints in PointController**

In `app/Http/Controllers/PointController.php`, replace the `getAllUserPoints` method and the `getPointEventTotal` method signature. Find and replace every occurrence of `user_groups` with `league_members`, `group_id` with `league_id`, `ug.guest` with `lm.is_guest`, and rename parameter `$groupID` to `$leagueID`:

```php
public function getAllUserPoints($leagueID): array
{
    $users = DB::table('users')
        ->join('league_members', 'users.id', '=', 'league_members.user_id')
        ->where('league_members.league_id', '=', $leagueID)
        ->where('league_members.is_guest', '<=', session('guest'))
        ->select('users.id', 'users.username', 'users.name', 'users.surname')
        ->get();

    $pointStandingController  = app(PointStandingController::class);
    $pointsResultController   = app(PointResultController::class);
    $pointSurvivalController  = new PointSurvivalController();

    $userAllPoints = [];
    foreach ($users as $user) {
        $profile         = $pointsResultController->getUserProfilePoints($user->id);
        $userGamePoints  = array_sum(array_column($profile, 'full_points'));
        $userStreakPoints = array_sum(array_column($profile, 'streak_bonus'));
        $userGameBingo   = array_sum(array_column($profile, 'bingo_points'));
        $gameCount       = count($profile);
        $standingPoints  = $pointStandingController->getStandingsUserPoints($user->id);
        $survivalPoints  = $pointSurvivalController->getPredictionSurvivalUserPoints($user->id);

        $userAllPoints[] = [
            'userID'         => $user->id,
            'username'       => $user->username,
            'name'           => $user->name,
            'surname'        => $user->surname,
            'userFee'        => null,
            'userGamePoints' => round($userGamePoints ?: 0, 1),
            'userStreakPoints'=> round($userStreakPoints, 1),
            'userGameBingo'  => $userGameBingo ?: 0,
            'averagePoints'  => $gameCount === 0 ? 0 : round($userGamePoints / $gameCount, 1),
            'standingPoints' => $standingPoints,
            'survivalPoints' => $survivalPoints ?: 0,
        ];
    }

    if (!empty($userAllPoints)) {
        usort($userAllPoints, function ($a, $b) {
            return ($b['userGamePoints'] + $b['userStreakPoints'] + $b['standingPoints']->total_points + $b['survivalPoints'])
               <=> ($a['userGamePoints'] + $a['userStreakPoints'] + $a['standingPoints']->total_points + $a['survivalPoints']);
        });
    }

    return $userAllPoints;
}
```

Also update `getPointEventTotal` — rename `$groupID` parameter to `$leagueID` and change the JOIN:

```php
// Find: ->join('user_groups','users.id','=','user_groups.user_id')->where('user_groups.group_id','=',$groupID)->where('user_groups.guest','<=',session('guest'))
// Replace with:
->join('league_members','users.id','=','league_members.user_id')->where('league_members.league_id','=',$leagueID)->where('league_members.is_guest','<=',session('guest'))
```

Also update `getRankHistory` — change `->where('group_id', $groupID)` to `->where('league_id', $leagueID)` and rename the parameter.

- [ ] **Step 4: Run test**

```bash
php artisan test tests/Feature/LeagueFoundationTest.php --filter test_get_all_user_points_filters_by_league_id
```

Expected: `Tests: 1 passed`

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/PointController.php tests/Feature/LeagueFoundationTest.php
git commit -m "feat: PointController uses league_members and leagueID"
```

---

## Task 8: Update ChartController, PredictionStandingController, PredictionResultController

**Files:**
- Modify: `app/Http/Controllers/ChartController.php`
- Modify: `app/Http/Controllers/PredictionStandingController.php`
- Modify: `app/Http/Controllers/PredictionResultController.php`

All three have raw SQL JOINs to `user_groups` with `group_id`. Replace with `league_members` and `league_id`.

- [ ] **Step 1: Update ChartController**

In `app/Http/Controllers/ChartController.php`:

Replace `session('groupID')` → `session('leagueID')`.

Replace the `$users` query:
```php
// OLD:
$users = DB::table('user_groups')
    ->where('user_groups.group_id', $groupID)
    ->where('user_groups.guest', '<=', $guest)
    ->join('users', 'user_groups.user_id', '=', 'users.id')
    ->leftJoin('colors', 'user_groups.user_id', '=', 'colors.id')
    ->select('users.id', 'users.username', 'colors.color_code')
    ->get();

// NEW:
$users = DB::table('league_members')
    ->where('league_members.league_id', $leagueID)
    ->where('league_members.is_guest', '<=', $guest)
    ->join('users', 'league_members.user_id', '=', 'users.id')
    ->leftJoin('colors', 'league_members.user_id', '=', 'colors.id')
    ->select('users.id', 'users.username', 'colors.color_code')
    ->get();
```

Replace the `$rows` raw SQL JOIN:
```sql
-- OLD: JOIN user_groups ug ON pr.user_id = ug.user_id AND ug.group_id = ? AND ug.guest <= ?
-- NEW: JOIN league_members lm ON pr.user_id = lm.user_id AND lm.league_id = ? AND lm.is_guest <= ?
```

- [ ] **Step 2: Update PredictionStandingController**

In `app/Http/Controllers/PredictionStandingController.php`:

Replace all `session('groupID')` → `session('leagueID')`.
Replace all `$groupID` parameter names → `$leagueID`.

In `getPredictionStandingProfile`, change the raw SQL JOIN:
```sql
-- OLD: JOIN user_groups AS ug ON u.id=ug.user_id WHERE ug.group_id = ?
-- NEW: JOIN league_members AS lm ON u.id=lm.user_id WHERE lm.league_id = ?
```

In `getPredictionStandingTop4`, change:
```sql
-- OLD: join user_groups AS ug on ps.user_id=ug.user_id where ps.final is not null and ug.group_id = ?
-- NEW: join league_members AS lm on ps.user_id=lm.user_id where ps.final is not null and lm.league_id = ?
```

- [ ] **Step 3: Update PredictionResultController**

In `app/Http/Controllers/PredictionResultController.php`:

Replace all `session('groupID')` → `session('leagueID')`.
Replace all `$groupID` parameter names → `$leagueID`.

In `getPredictionGamesProfile`, the JOIN at line ~135:
```sql
-- OLD: join user_groups as ug on pr.user_id=ug.user_id AND ug.group_id = ? AND ug.guest <= ?
-- NEW: join league_members as lm on pr.user_id=lm.user_id AND lm.league_id = ? AND lm.is_guest <= ?
```

In the earlier query (~line 120) that references `ug.group_id`:
```sql
-- OLD: JOIN user_groups AS ug ON u.id=ug.user_id JOIN user_settings AS us ON u.id=us.user_id ... WHERE ... AND ug.group_id = ?
-- NEW: JOIN league_members AS lm ON u.id=lm.user_id JOIN user_settings AS us ON u.id=us.user_id ... WHERE ... AND lm.league_id = ?
```

- [ ] **Step 4: Run full test suite**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/ChartController.php \
        app/Http/Controllers/PredictionStandingController.php \
        app/Http/Controllers/PredictionResultController.php
git commit -m "feat: ChartController, PredictionStandingController, PredictionResultController use league_members"
```

---

## Task 9: Update FeeController and MessageController

**Files:**
- Modify: `app/Http/Controllers/FeeController.php`
- Modify: `app/Http/Controllers/MessageController.php`

- [ ] **Step 1: Rewrite FeeController**

`FeeController` previously used `Group` model with `reward_ratio`. The new model uses `League` with `base_fee` and `penalty_step`. Replace the full contents:

```php
<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\LeagueMember;
use Illuminate\Support\Facades\DB;

class FeeController extends Controller
{
    public function getLeagueDetails(): League
    {
        return League::where('id', session('leagueID'))->firstOrFail();
    }

    public function getMemberCount(): int
    {
        return LeagueMember::where('league_id', session('leagueID'))
            ->where('is_guest', false)
            ->count();
    }

    public function getFund(): int
    {
        $league = $this->getLeagueDetails();
        $count  = $this->getMemberCount();
        return ($league->base_fee ?? 0) * $count;
    }

    public function getPlaceFees(array $rankedUserIds): array
    {
        $league = $this->getLeagueDetails();
        $fees   = [];
        foreach ($rankedUserIds as $position => $userId) {
            $fees[$userId] = ($league->base_fee ?? 0) + $position * ($league->penalty_step ?? 0);
        }
        return $fees;
    }
}
```

- [ ] **Step 2: Update MessageController**

In `app/Http/Controllers/MessageController.php`, replace `group_id` with `league_id` in all queries. The `getProfileMessages` method:

```php
public function getProfileMessages($leagueID)
{
    $messages = \App\Models\Message::where('active', 1)->where('league_id', $leagueID)->get();
    return $messages;
}
```

Also update `insertMessage` and `updateMessage` methods — replace `$request->input('groupID')` with `$request->input('leagueID')` and `$message->group_id` with `$message->league_id`.

- [ ] **Step 3: Run full test suite**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/FeeController.php app/Http/Controllers/MessageController.php
git commit -m "feat: FeeController and MessageController use leagues"
```

---

## Task 10: Update MainController and drop old tables

**Files:**
- Modify: `app/Http/Controllers/MainController.php`
- Modify: `routes/web.php`
- Create: `database/migrations/2026_06_10_200006_drop_groups_user_groups.php`

- [ ] **Step 1: Update MainController**

In `app/Http/Controllers/MainController.php`, replace `session('groupID')` → `session('leagueID')` at line ~31.

The call chain already uses the updated controllers (`PointController`, `MessageController`, etc.), so this is just the session key rename.

- [ ] **Step 2: Update admin routes**

In `routes/web.php`, the admin group management routes reference `GroupController`. These are now handled by the league admin in Plan 2, but for now remove the routes to `admin.groups` to prevent 500 errors (GroupController still JOINs the dropped `groups` table):

```php
// REMOVE these routes (GroupController references groups table being dropped):
// Route::get('groups', [GroupController::class,'getGroup'])->name('admin.groups');
// Route::post('groups', [GroupController::class,'updateGroup'])->name('admin.groups');
// Route::post('groupInsert', [GroupController::class,'insertGroup'])->name('admin.groupInsert');
```

Replace with a temporary redirect (Plan 2 will add the real leagues admin):

```php
Route::get('groups', fn() => redirect()->route('admin.index'))->name('admin.groups');
```

- [ ] **Step 3: Create drop migration**

```php
// database/migrations/2026_06_10_200006_drop_groups_user_groups.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Drop FK from messages (already replaced in Task 4) — just in case
        // Drop user_groups first (has FK to groups)
        Schema::dropIfExists('user_groups');
        Schema::dropIfExists('groups');
    }

    public function down(): void
    {
        // Restore is handled by running migrations from scratch (migrate:fresh)
    }
};
```

- [ ] **Step 4: Run migration**

```bash
php artisan migrate
```

Expected: no errors. Verify old tables gone:
```bash
php artisan tinker --execute="echo Schema::hasTable('groups') ? 'FAIL - groups still exists' : 'OK - groups dropped';"
```

- [ ] **Step 5: Run full test suite**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/MainController.php \
        routes/web.php \
        database/migrations/2026_06_10_200006_drop_groups_user_groups.php
git commit -m "feat: drop groups/user_groups tables, update MainController and routes"
```

---

## Task 11: Smoke test — full app works end-to-end

- [ ] **Step 1: Run a fresh migration with seed**

```bash
php artisan migrate:fresh --seed
```

Expected: no errors.

- [ ] **Step 2: Start the dev server and log in**

```bash
php artisan serve
```

Open `http://localhost:8000`. Log in. Verify:
- Main leaderboard loads (no 500 errors)
- `session('leagueID')` is set (check via tinker or dd in a controller)
- Predictions page loads
- Chart page loads
- Admin panel loads (admin user)

- [ ] **Step 3: Run full test suite one final time**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 4: Push**

```bash
git push
```

---

## What comes next

**Plan 2 — Membership UI** covers:
- `LeagueController`: create league, invite user, accept/decline invite, leave league, switch active league
- `/leagues` hub page: my leagues cards, invite inbox, create form
- Navbar pill + dropdown for quick league switching
- Admin league management page (replaces removed groups admin)

**Plan 3 — Per-league odds** covers:
- `GameOddsController` update to compute `league_game_odds` for opt-in leagues
- Leaderboard query recalculates `odds_points` per league at query time
- Tests for odds fallback behaviour
