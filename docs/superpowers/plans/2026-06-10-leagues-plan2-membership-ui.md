# Leagues — Plan 2 of 3: Membership UI

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Prerequisite:** Plan 1 (Foundation) must be complete. `leagues`, `league_members`, `league_invites` tables exist. `SessionController` writes `session('leagueID')`.

**Goal:** Build the full user-facing leagues experience: create a league, invite other users, accept/decline invites, switch active league, leave a private league, and manage members (admin).

**Architecture:** One new `LeagueController` handles all user-facing league actions. A `/leagues` hub page (Blade view) covers full management. The top navbar gains a pill dropdown for quick switching. No JavaScript framework beyond Alpine.js — keep consistent with the rest of the app.

**Tech Stack:** Laravel 11 · Blade · Bootstrap 5 · Alpine.js · PHPUnit

**Spec:** `docs/superpowers/specs/2026-06-10-leagues-design.md`

**Related plans:**
- Plan 1 — Foundation (prerequisite)
- Plan 3 — Per-league odds (next)

---

## Files

| Action | Path |
|---|---|
| Create | `app/Http/Controllers/LeagueController.php` |
| Create | `resources/views/leagues/index.blade.php` |
| Create | `resources/views/partials/league-switcher.blade.php` |
| Modify | `resources/views/layouts/master.blade.php` |
| Modify | `routes/web.php` |
| Modify | `app/Http/Controllers/SessionController.php` (add `switchLeague` helper) |
| Create | `tests/Feature/LeagueMembershipTest.php` |

---

## Task 1: LeagueController — create and manage leagues

**Files:**
- Create: `app/Http/Controllers/LeagueController.php`
- Create: `tests/Feature/LeagueMembershipTest.php`

- [ ] **Step 1: Write failing test — create league**

```php
// tests/Feature/LeagueMembershipTest.php
<?php
namespace Tests\Feature;

use App\Models\League;
use App\Models\LeagueMember;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeagueMembershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Public league must exist (created in data migration; here we seed it for tests)
        League::create(['name' => 'Public League', 'is_public' => true]);
    }

    public function test_authenticated_user_can_create_league(): void
    {
        $user = User::factory()->create();
        UserSetting::create(['user_id' => $user->id, 'admin' => 0]);

        $this->actingAs($user)->post(route('leagues.create'), [
            'name'         => 'Friends Liga',
            'description'  => 'Our private league',
            'base_fee'     => 10,
            'penalty_step' => 5,
        ])->assertRedirect(route('leagues.index'));

        $league = League::where('name', 'Friends Liga')->first();
        $this->assertNotNull($league);
        $this->assertEquals($user->id, $league->owner_id);
        $this->assertFalse((bool) $league->is_public);

        // Creator should be auto-added as admin member with active=true
        $member = LeagueMember::where('league_id', $league->id)
            ->where('user_id', $user->id)
            ->first();
        $this->assertNotNull($member);
        $this->assertTrue((bool) $member->is_admin);
    }
}
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
php artisan test tests/Feature/LeagueMembershipTest.php --filter test_authenticated_user_can_create_league
```

Expected: FAIL — route `leagues.create` not defined.

- [ ] **Step 3: Create LeagueController with create method**

```php
// app/Http/Controllers/LeagueController.php
<?php
namespace App\Http\Controllers;

use App\Models\League;
use App\Models\LeagueMember;
use App\Models\LeagueInvite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeagueController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $userId = session('userID');

        $myLeagues = LeagueMember::where('user_id', $userId)
            ->with('league')
            ->get();

        $pendingInvites = LeagueInvite::where('invited_user_id', $userId)
            ->where('status', 'pending')
            ->with(['league', 'invitedBy'])
            ->get();

        return view('leagues.index', compact('myLeagues', 'pendingInvites'));
    }

    public function create(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $userId = session('userID');

        $league = League::create([
            'name'               => $request->input('name'),
            'description'        => $request->input('description'),
            'is_public'          => false,
            'owner_id'           => $userId,
            'base_fee'           => $request->input('base_fee'),
            'penalty_step'       => $request->input('penalty_step'),
            'use_league_odds'    => false,
            'reward_description' => $request->input('reward_description'),
        ]);

        LeagueMember::create([
            'league_id' => $league->id,
            'user_id'   => $userId,
            'is_admin'  => true,
            'is_guest'  => false,
            'active'    => false, // new league doesn't steal active status
        ]);

        return redirect()->route('leagues.index')->with('info', 'Liga sukurta');
    }
}
```

- [ ] **Step 4: Add routes in routes/web.php**

Inside the authenticated middleware group, add:

```php
Route::get('/leagues', [LeagueController::class, 'index'])->name('leagues.index');
Route::post('/leagues/create', [LeagueController::class, 'create'])->name('leagues.create');
```

- [ ] **Step 5: Run test**

```bash
php artisan test tests/Feature/LeagueMembershipTest.php --filter test_authenticated_user_can_create_league
```

Expected: `Tests: 1 passed`

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/LeagueController.php \
        routes/web.php \
        tests/Feature/LeagueMembershipTest.php
git commit -m "feat: LeagueController create method and routes"
```

---

## Task 2: Invite and accept/decline

**Files:**
- Modify: `app/Http/Controllers/LeagueController.php`
- Modify: `tests/Feature/LeagueMembershipTest.php`

- [ ] **Step 1: Write failing tests**

Add to `tests/Feature/LeagueMembershipTest.php`:

```php
public function test_league_admin_can_invite_user(): void
{
    $owner   = User::factory()->create();
    $invitee = User::factory()->create();
    UserSetting::create(['user_id' => $owner->id, 'admin' => 0]);
    UserSetting::create(['user_id' => $invitee->id, 'admin' => 0]);

    $league = League::create(['name' => 'Test', 'is_public' => false, 'owner_id' => $owner->id]);
    LeagueMember::create(['league_id' => $league->id, 'user_id' => $owner->id, 'is_admin' => true, 'active' => false]);

    $this->actingAs($owner);
    session(['userID' => $owner->id]);

    $this->post(route('leagues.invite'), [
        'leagueID'       => $league->id,
        'invitedUserID'  => $invitee->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('league_invites', [
        'league_id'       => $league->id,
        'invited_user_id' => $invitee->id,
        'status'          => 'pending',
    ]);
}

public function test_user_can_accept_invite(): void
{
    $owner   = User::factory()->create();
    $invitee = User::factory()->create();
    UserSetting::create(['user_id' => $owner->id, 'admin' => 0]);
    UserSetting::create(['user_id' => $invitee->id, 'admin' => 0]);

    $league = League::create(['name' => 'Test', 'is_public' => false, 'owner_id' => $owner->id]);
    LeagueMember::create(['league_id' => $league->id, 'user_id' => $owner->id, 'is_admin' => true, 'active' => false]);

    $invite = \App\Models\LeagueInvite::create([
        'league_id'       => $league->id,
        'invited_user_id' => $invitee->id,
        'invited_by_id'   => $owner->id,
        'status'          => 'pending',
    ]);

    $this->actingAs($invitee);
    session(['userID' => $invitee->id]);

    $this->post(route('leagues.accept'), ['inviteID' => $invite->id])
         ->assertRedirect(route('leagues.index'));

    $this->assertDatabaseHas('league_members', [
        'league_id' => $league->id,
        'user_id'   => $invitee->id,
    ]);
    $this->assertDatabaseMissing('league_invites', ['id' => $invite->id]);
}

public function test_user_can_decline_invite(): void
{
    $owner   = User::factory()->create();
    $invitee = User::factory()->create();

    $league = League::create(['name' => 'Test', 'is_public' => false, 'owner_id' => $owner->id]);

    $invite = \App\Models\LeagueInvite::create([
        'league_id'       => $league->id,
        'invited_user_id' => $invitee->id,
        'invited_by_id'   => $owner->id,
        'status'          => 'pending',
    ]);

    $this->actingAs($invitee);
    session(['userID' => $invitee->id]);

    $this->post(route('leagues.decline'), ['inviteID' => $invite->id])
         ->assertRedirect(route('leagues.index'));

    $this->assertDatabaseMissing('league_invites', ['id' => $invite->id]);
    $this->assertDatabaseMissing('league_members', [
        'league_id' => $league->id,
        'user_id'   => $invitee->id,
    ]);
}
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
php artisan test tests/Feature/LeagueMembershipTest.php
```

Expected: 3 new tests FAIL.

- [ ] **Step 3: Add invite/accept/decline methods to LeagueController**

Add to `app/Http/Controllers/LeagueController.php`:

```php
public function invite(Request $request): \Illuminate\Http\RedirectResponse
{
    $leagueId = $request->input('leagueID');
    $userId   = session('userID');

    // Verify caller is admin
    $callerMembership = LeagueMember::where('league_id', $leagueId)
        ->where('user_id', $userId)
        ->where('is_admin', true)
        ->firstOrFail();

    $inviteeId = $request->input('invitedUserID');

    // Block if already a member or has pending invite
    $alreadyMember = LeagueMember::where('league_id', $leagueId)->where('user_id', $inviteeId)->exists();
    $alreadyInvited = LeagueInvite::where('league_id', $leagueId)->where('invited_user_id', $inviteeId)->where('status', 'pending')->exists();

    if ($alreadyMember || $alreadyInvited) {
        return redirect()->back()->with('error', 'Vartotojas jau narys arba pakviestas');
    }

    LeagueInvite::create([
        'league_id'       => $leagueId,
        'invited_user_id' => $inviteeId,
        'invited_by_id'   => $userId,
        'status'          => 'pending',
    ]);

    return redirect()->back()->with('info', 'Kvietimas išsiųstas');
}

public function acceptInvite(Request $request): \Illuminate\Http\RedirectResponse
{
    $userId   = session('userID');
    $invite   = LeagueInvite::where('id', $request->input('inviteID'))
        ->where('invited_user_id', $userId)
        ->where('status', 'pending')
        ->firstOrFail();

    LeagueMember::create([
        'league_id' => $invite->league_id,
        'user_id'   => $userId,
        'is_admin'  => false,
        'is_guest'  => false,
        'active'    => false,
    ]);

    $invite->delete();

    return redirect()->route('leagues.index')->with('info', 'Prisijungta prie lygos');
}

public function declineInvite(Request $request): \Illuminate\Http\RedirectResponse
{
    $userId = session('userID');
    $invite = LeagueInvite::where('id', $request->input('inviteID'))
        ->where('invited_user_id', $userId)
        ->firstOrFail();

    $invite->delete();

    return redirect()->route('leagues.index')->with('info', 'Kvietimas atmestas');
}
```

- [ ] **Step 4: Add routes**

Add to `routes/web.php` (inside authenticated group):

```php
Route::post('/leagues/invite', [LeagueController::class, 'invite'])->name('leagues.invite');
Route::post('/leagues/accept', [LeagueController::class, 'acceptInvite'])->name('leagues.accept');
Route::post('/leagues/decline', [LeagueController::class, 'declineInvite'])->name('leagues.decline');
```

- [ ] **Step 5: Run tests**

```bash
php artisan test tests/Feature/LeagueMembershipTest.php
```

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/LeagueController.php \
        routes/web.php \
        tests/Feature/LeagueMembershipTest.php
git commit -m "feat: league invite, accept, and decline flows"
```

---

## Task 3: Switch active league and leave league

**Files:**
- Modify: `app/Http/Controllers/LeagueController.php`
- Modify: `tests/Feature/LeagueMembershipTest.php`

- [ ] **Step 1: Write failing tests**

Add to `tests/Feature/LeagueMembershipTest.php`:

```php
public function test_user_can_switch_active_league(): void
{
    $user = User::factory()->create();
    UserSetting::create(['user_id' => $user->id, 'admin' => 0]);

    $publicLeague  = League::where('is_public', true)->first();
    $privateLeague = League::create(['name' => 'Private', 'is_public' => false, 'owner_id' => $user->id]);

    LeagueMember::create(['league_id' => $publicLeague->id,  'user_id' => $user->id, 'active' => true,  'is_guest' => false]);
    LeagueMember::create(['league_id' => $privateLeague->id, 'user_id' => $user->id, 'active' => false, 'is_guest' => false]);

    $this->actingAs($user);
    session(['userID' => $user->id, 'leagueID' => $publicLeague->id]);

    $this->post(route('leagues.switch'), ['leagueID' => $privateLeague->id])
         ->assertRedirect();

    $this->assertDatabaseHas('league_members', ['league_id' => $publicLeague->id,  'user_id' => $user->id, 'active' => false]);
    $this->assertDatabaseHas('league_members', ['league_id' => $privateLeague->id, 'user_id' => $user->id, 'active' => true]);
}

public function test_user_can_leave_private_league(): void
{
    $owner = User::factory()->create();
    $user  = User::factory()->create();
    $publicLeague  = League::where('is_public', true)->first();
    $privateLeague = League::create(['name' => 'Private', 'is_public' => false, 'owner_id' => $owner->id]);

    LeagueMember::create(['league_id' => $publicLeague->id,  'user_id' => $user->id, 'active' => false, 'is_guest' => false]);
    LeagueMember::create(['league_id' => $privateLeague->id, 'user_id' => $user->id, 'active' => true,  'is_guest' => false, 'is_admin' => false]);

    $this->actingAs($user);
    session(['userID' => $user->id, 'leagueID' => $privateLeague->id]);

    $this->post(route('leagues.leave'), ['leagueID' => $privateLeague->id])
         ->assertRedirect(route('leagues.index'));

    $this->assertDatabaseMissing('league_members', [
        'league_id' => $privateLeague->id,
        'user_id'   => $user->id,
    ]);

    // Public league should become active
    $this->assertDatabaseHas('league_members', [
        'league_id' => $publicLeague->id,
        'user_id'   => $user->id,
        'active'    => true,
    ]);
}

public function test_user_cannot_leave_public_league(): void
{
    $user         = User::factory()->create();
    $publicLeague = League::where('is_public', true)->first();

    LeagueMember::create(['league_id' => $publicLeague->id, 'user_id' => $user->id, 'active' => true, 'is_guest' => false]);

    $this->actingAs($user);
    session(['userID' => $user->id]);

    $this->post(route('leagues.leave'), ['leagueID' => $publicLeague->id])
         ->assertStatus(403);
}
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
php artisan test tests/Feature/LeagueMembershipTest.php
```

Expected: 3 new tests FAIL.

- [ ] **Step 3: Add switch and leave methods to LeagueController**

Add to `app/Http/Controllers/LeagueController.php`:

```php
public function switchLeague(Request $request): \Illuminate\Http\RedirectResponse
{
    $userId     = session('userID');
    $newLeagueId = $request->input('leagueID');

    // Verify user is a member of the target league
    $newMembership = LeagueMember::where('user_id', $userId)
        ->where('league_id', $newLeagueId)
        ->firstOrFail();

    // Deactivate all leagues for this user
    LeagueMember::where('user_id', $userId)->update(['active' => false]);

    // Activate the chosen one
    $newMembership->update(['active' => true]);

    // Update session
    session(['leagueID' => $newLeagueId]);
    $league = $newMembership->league;
    if ($league) {
        session(['fee' => $league->base_fee]);
    }

    return redirect()->back()->with('info', 'Liga pakeista');
}

public function leaveLeague(Request $request): \Illuminate\Http\RedirectResponse
{
    $userId   = session('userID');
    $leagueId = $request->input('leagueID');

    $league = League::findOrFail($leagueId);

    // Cannot leave public league
    if ($league->is_public) {
        abort(403, 'Negalima palikti viešos lygos');
    }

    // Cannot leave own league without transferring ownership
    if ($league->owner_id === $userId) {
        return redirect()->back()->with('error', 'Perduokite lygos valdymą kitam nariui prieš išeidami');
    }

    $membership = LeagueMember::where('user_id', $userId)
        ->where('league_id', $leagueId)
        ->firstOrFail();

    $wasActive = (bool) $membership->active;
    $membership->delete();

    // If they left their active league, switch to public
    if ($wasActive) {
        $publicLeague  = League::where('is_public', true)->firstOrFail();
        $publicMembership = LeagueMember::where('user_id', $userId)
            ->where('league_id', $publicLeague->id)
            ->firstOrFail();
        $publicMembership->update(['active' => true]);
        session(['leagueID' => $publicLeague->id]);
    }

    return redirect()->route('leagues.index')->with('info', 'Palikote lygą');
}
```

- [ ] **Step 4: Add routes**

Add to `routes/web.php` (inside authenticated group):

```php
Route::post('/leagues/switch', [LeagueController::class, 'switchLeague'])->name('leagues.switch');
Route::post('/leagues/leave',  [LeagueController::class, 'leaveLeague'])->name('leagues.leave');
```

- [ ] **Step 5: Run tests**

```bash
php artisan test tests/Feature/LeagueMembershipTest.php
```

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/LeagueController.php \
        routes/web.php \
        tests/Feature/LeagueMembershipTest.php
git commit -m "feat: league switch active and leave flows"
```

---

## Task 4: User search for invites

**Files:**
- Modify: `app/Http/Controllers/LeagueController.php`
- Modify: `tests/Feature/LeagueMembershipTest.php`

League admins need to search for users by name/username to invite them.

- [ ] **Step 1: Write failing test**

Add to `tests/Feature/LeagueMembershipTest.php`:

```php
public function test_league_admin_can_search_users(): void
{
    $admin = User::factory()->create(['username' => 'tomas_k', 'name' => 'Tomas', 'surname' => 'K']);
    $other = User::factory()->create(['username' => 'john_d',  'name' => 'John',  'surname' => 'D']);
    $league = League::create(['name' => 'Test', 'is_public' => false, 'owner_id' => $admin->id]);
    LeagueMember::create(['league_id' => $league->id, 'user_id' => $admin->id, 'is_admin' => true, 'active' => false]);

    $this->actingAs($admin);
    session(['userID' => $admin->id]);

    $response = $this->getJson(route('leagues.searchUsers', [
        'query'    => 'john',
        'leagueID' => $league->id,
    ]));

    $response->assertOk();
    $data = $response->json();
    $this->assertCount(1, $data);
    $this->assertEquals($other->id, $data[0]['id']);
}
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
php artisan test tests/Feature/LeagueMembershipTest.php --filter test_league_admin_can_search_users
```

Expected: FAIL.

- [ ] **Step 3: Add searchUsers method to LeagueController**

Add to `app/Http/Controllers/LeagueController.php`:

```php
public function searchUsers(Request $request): \Illuminate\Http\JsonResponse
{
    $userId   = session('userID');
    $leagueId = $request->input('leagueID');
    $query    = $request->input('query', '');

    // Verify caller is admin of this league
    LeagueMember::where('league_id', $leagueId)
        ->where('user_id', $userId)
        ->where('is_admin', true)
        ->firstOrFail();

    if (strlen($query) < 2) {
        return response()->json([]);
    }

    // Exclude existing members and the caller
    $existingMemberIds = LeagueMember::where('league_id', $leagueId)->pluck('user_id');

    $users = User::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('surname', 'like', "%{$query}%")
              ->orWhere('username', 'like', "%{$query}%");
        })
        ->whereNotIn('id', $existingMemberIds)
        ->select('id', 'username', 'name', 'surname')
        ->limit(10)
        ->get();

    return response()->json($users);
}
```

- [ ] **Step 4: Add route**

```php
Route::get('/leagues/searchUsers', [LeagueController::class, 'searchUsers'])->name('leagues.searchUsers');
```

- [ ] **Step 5: Run tests**

```bash
php artisan test tests/Feature/LeagueMembershipTest.php
```

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/LeagueController.php \
        routes/web.php \
        tests/Feature/LeagueMembershipTest.php
git commit -m "feat: user search endpoint for league invites"
```

---

## Task 5: Leagues hub page (`/leagues`)

**Files:**
- Create: `resources/views/leagues/index.blade.php`

- [ ] **Step 1: Create the directory and Blade view**

```bash
mkdir -p resources/views/leagues
```

Create `resources/views/leagues/index.blade.php`:

```blade
@extends('layouts.master')

@section('content')
<div class="container py-4">

  @if(session('info'))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
      {{ session('info') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- Invite Inbox --}}
  @if($pendingInvites->count())
  <div class="mb-4">
    <h5 class="fw-bold mb-3">Kvietimai <span class="badge bg-danger">{{ $pendingInvites->count() }}</span></h5>
    @foreach($pendingInvites as $invite)
    <div class="card mb-2">
      <div class="card-body d-flex align-items-center justify-content-between py-2">
        <div>
          <strong>{{ $invite->league->name }}</strong>
          <span class="text-muted small ms-2">pakvietė {{ $invite->invitedBy->username ?? '—' }}</span>
        </div>
        <div class="d-flex gap-2">
          <form method="POST" action="{{ route('leagues.accept') }}">
            @csrf
            <input type="hidden" name="inviteID" value="{{ $invite->id }}">
            <button type="submit" class="btn btn-success btn-sm">Priimti</button>
          </form>
          <form method="POST" action="{{ route('leagues.decline') }}">
            @csrf
            <input type="hidden" name="inviteID" value="{{ $invite->id }}">
            <button type="submit" class="btn btn-outline-secondary btn-sm">Atmesti</button>
          </form>
        </div>
      </div>
    </div>
    @endforeach
  </div>
  @endif

  {{-- My Leagues --}}
  <h5 class="fw-bold mb-3">Mano lygos</h5>
  <div class="row g-3 mb-4">
    @foreach($myLeagues as $membership)
    @php $league = $membership->league; @endphp
    <div class="col-md-6">
      <div class="card h-100 {{ $membership->active ? 'border-primary' : '' }}">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <h6 class="mb-1 fw-bold">
                {{ $league->name }}
                @if($membership->active)
                  <span class="badge bg-primary ms-1" style="font-size:.65rem;">AKTYVI</span>
                @endif
                @if($league->is_public)
                  <span class="badge bg-secondary ms-1" style="font-size:.65rem;">VIEŠA</span>
                @endif
              </h6>
              <div class="text-muted small">
                {{ $league->members()->count() }} nariai
                @if($league->base_fee)
                  · Įmoka: {{ $league->base_fee }}€
                  @if($league->penalty_step)
                    + {{ $league->penalty_step }}€/vieta
                  @endif
                @endif
              </div>
              @if($league->description)
                <div class="text-muted small mt-1">{{ $league->description }}</div>
              @endif
            </div>
          </div>

          <div class="mt-3 d-flex gap-2 flex-wrap">
            @if(!$membership->active)
            <form method="POST" action="{{ route('leagues.switch') }}">
              @csrf
              <input type="hidden" name="leagueID" value="{{ $league->id }}">
              <button type="submit" class="btn btn-outline-primary btn-sm">Perjungti</button>
            </form>
            @endif

            @if($membership->is_admin)
            <button type="button" class="btn btn-outline-secondary btn-sm"
                    onclick="openManageModal({{ $league->id }}, {{ json_encode($league->name) }})">
              Valdyti
            </button>
            @endif

            @if(!$league->is_public)
              @if($league->owner_id !== session('userID'))
              <form method="POST" action="{{ route('leagues.leave') }}"
                    onsubmit="return confirm('Palikti lygą {{ addslashes($league->name) }}?')">
                @csrf
                <input type="hidden" name="leagueID" value="{{ $league->id }}">
                <button type="submit" class="btn btn-outline-danger btn-sm">Palikti</button>
              </form>
              @else
              <span class="text-muted small align-self-center">Savininkas</span>
              @endif
            @endif
          </div>
        </div>
      </div>
    </div>
    @endforeach
  </div>

  {{-- Create League --}}
  <h5 class="fw-bold mb-3">Sukurti naują lygą</h5>
  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('leagues.create') }}">
        @csrf
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small">Pavadinimas *</label>
            <input type="text" name="name" class="form-control form-control-sm" required maxlength="100">
          </div>
          <div class="col-md-6">
            <label class="form-label small">Aprašymas</label>
            <input type="text" name="description" class="form-control form-control-sm" maxlength="255">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Bazinė įmoka (€)</label>
            <input type="number" name="base_fee" class="form-control form-control-sm" min="0">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Bauda už vietą (€)</label>
            <input type="number" name="penalty_step" class="form-control form-control-sm" min="0">
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-primary btn-sm">Sukurti lygą</button>
          </div>
        </div>
      </form>
    </div>
  </div>

</div>

{{-- Manage League Modal --}}
<div class="modal fade" id="manageModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="manageModalTitle">Valdyti lygą</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        {{-- Invite Section --}}
        <h6>Pakviesti narį</h6>
        <div class="input-group mb-2">
          <input type="text" id="inviteSearch" class="form-control form-control-sm"
                 placeholder="Ieškoti pagal vardą arba vartotojo vardą..."
                 oninput="searchUsers(this.value)">
        </div>
        <div id="searchResults" class="list-group mb-3"></div>

        <form id="inviteForm" method="POST" action="{{ route('leagues.invite') }}">
          @csrf
          <input type="hidden" id="inviteLeagueID" name="leagueID">
          <input type="hidden" id="invitedUserID" name="invitedUserID">
        </form>

      </div>
    </div>
  </div>
</div>

<script>
let activeManageLeagueId = null;

function openManageModal(leagueId, leagueName) {
    activeManageLeagueId = leagueId;
    document.getElementById('manageModalTitle').textContent = 'Valdyti: ' + leagueName;
    document.getElementById('inviteLeagueID').value = leagueId;
    document.getElementById('searchResults').innerHTML = '';
    document.getElementById('inviteSearch').value = '';
    new bootstrap.Modal(document.getElementById('manageModal')).show();
}

function searchUsers(query) {
    if (query.length < 2) {
        document.getElementById('searchResults').innerHTML = '';
        return;
    }
    fetch(`{{ route('leagues.searchUsers') }}?query=${encodeURIComponent(query)}&leagueID=${activeManageLeagueId}`)
        .then(r => r.json())
        .then(users => {
            const container = document.getElementById('searchResults');
            if (users.length === 0) {
                container.innerHTML = '<div class="list-group-item list-group-item-action text-muted small">Nerasta</div>';
                return;
            }
            container.innerHTML = users.map(u =>
                `<button type="button" class="list-group-item list-group-item-action small"
                         onclick="selectInvitee(${u.id})">
                   ${u.name} ${u.surname} <span class="text-muted">(${u.username})</span>
                 </button>`
            ).join('');
        });
}

function selectInvitee(userId) {
    document.getElementById('invitedUserID').value = userId;
    document.getElementById('inviteForm').submit();
}
</script>
@endsection
```

- [ ] **Step 2: Verify the page loads**

```bash
php artisan serve
```

Visit `http://localhost:8000/leagues` while logged in. Verify:
- Page renders without errors
- My Leagues section shows leagues
- Invite inbox shows if any pending invites
- Create League form renders

- [ ] **Step 3: Commit**

```bash
git add resources/views/leagues/index.blade.php
git commit -m "feat: leagues hub page with my leagues, inbox, and create form"
```

---

## Task 6: Navbar league switcher

**Files:**
- Create: `resources/views/partials/league-switcher.blade.php`
- Modify: `resources/views/layouts/master.blade.php`

- [ ] **Step 1: Create the partial**

Create `resources/views/partials/league-switcher.blade.php`:

```blade
@php
    $userId = session('userID');
    $activeLeagueId = session('leagueID');
    $myLeagues = \App\Models\LeagueMember::where('user_id', $userId)->with('league')->get();
    $activeLeague = $myLeagues->firstWhere('league_id', $activeLeagueId);
    $pendingInviteCount = \App\Models\LeagueInvite::where('invited_user_id', $userId)->where('status', 'pending')->count();
@endphp

<div class="dropdown d-inline-block me-2" id="leagueSwitcher">
  <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button"
          data-bs-toggle="dropdown" aria-expanded="false"
          style="font-size:.8rem; padding:3px 10px; border-radius:20px;">
    🏆 {{ $activeLeague?->league->name ?? 'Liga' }}
    @if($pendingInviteCount > 0)
      <span class="badge bg-danger ms-1" style="font-size:.6rem;">{{ $pendingInviteCount }}</span>
    @endif
  </button>
  <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:180px;">
    @foreach($myLeagues as $membership)
    <li>
      @if($membership->league_id === $activeLeagueId)
        <span class="dropdown-item active small">✓ {{ $membership->league->name }}</span>
      @else
        <form method="POST" action="{{ route('leagues.switch') }}" class="d-inline">
          @csrf
          <input type="hidden" name="leagueID" value="{{ $membership->league_id }}">
          <button type="submit" class="dropdown-item small">{{ $membership->league->name }}</button>
        </form>
      @endif
    </li>
    @endforeach
    <li><hr class="dropdown-divider"></li>
    <li>
      <a class="dropdown-item small text-muted" href="{{ route('leagues.index') }}">
        Visos lygos
        @if($pendingInviteCount > 0)
          <span class="badge bg-danger ms-1">{{ $pendingInviteCount }}</span>
        @endif
      </a>
    </li>
  </ul>
</div>
```

- [ ] **Step 2: Inject the switcher into the navbar**

In `resources/views/layouts/master.blade.php`, find the navbar section where user-related nav items appear (just before the user dropdown or the logout link). Add this line:

```blade
@if(session('userID'))
  @include('partials.league-switcher')
@endif
```

Place it inside the `<nav>` element, inside the collapsible navbar section, just before the user menu/username link.

- [ ] **Step 3: Start dev server and verify**

```bash
php artisan serve
```

Log in at `http://localhost:8000`. Verify:
- League pill appears in the navbar with the active league name
- Clicking the pill opens a dropdown listing all leagues
- Selecting another league fires a POST to `/leagues/switch` and reloads the page
- "Visos lygos" link leads to `/leagues`
- If pending invites exist, the red badge appears

- [ ] **Step 4: Commit**

```bash
git add resources/views/partials/league-switcher.blade.php \
        resources/views/layouts/master.blade.php
git commit -m "feat: navbar league switcher dropdown with invite badge"
```

---

## Task 7: Full regression — run all tests and smoke test UI

- [ ] **Step 1: Run full test suite**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 2: Smoke test the full flow manually**

Perform these steps in `http://localhost:8000`:

1. Log in as user A.
2. Visit `/leagues` → see Public League card.
3. Create a new private league via the Create form.
4. Open Manage modal → search for user B → send invite.
5. Log in as user B → visit `/leagues` → see invite in inbox → accept.
6. Log in as user A → visit `/leagues` → see 2 leagues.
7. Switch to private league via navbar dropdown.
8. Verify leaderboard shows only members of private league.
9. Log in as user B → leave private league → confirm redirected with public league active.

- [ ] **Step 3: Push**

```bash
git push
```

---

## What comes next

**Plan 3 — Per-league odds** covers:
- `GameOddsController` update to compute `league_game_odds` rows for opt-in leagues (≥ 20 members)
- Leaderboard `odds_points` recalculated at query time using per-league odds when available
- Tests for fallback behaviour (< 20 members → global odds)
