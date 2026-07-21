<?php

namespace Tests\Feature;

use App\Models\League;
use App\Models\LeagueMember;
use App\Models\Tournament;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for a real production incident: creating a new
 * tournament left its leagues/registration flow entangled with the old
 * (finished) tournament, because several places either hard-defaulted to
 * tournament_id=1 or looked up "the" public league with no tournament scope
 * at all.
 */
class TournamentIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $admin->id, 'admin' => 9]);

        return $admin;
    }

    public function test_creating_a_tournament_auto_creates_its_own_public_league(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->withSession(['userID' => $admin->id])
            ->post(route('admin.tournaments.store'), [
                'name' => 'New Cup', 'slug' => 'new-cup', 'sport' => 'football',
                'status' => 'active', 'is_public' => 1, 'survival_game' => 0,
            ])->assertRedirect(route('admin.tournaments'));

        $tournament = Tournament::where('slug', 'new-cup')->firstOrFail();

        $this->assertDatabaseHas('leagues', [
            'tournament_id' => $tournament->id,
            'is_public' => true,
        ]);
    }

    public function test_registration_joins_the_intended_tournaments_public_league(): void
    {
        $oldTournament = Tournament::create(['name' => 'Old Cup', 'slug' => 'old-cup', 'sport' => 'football', 'status' => 'finished']);
        $oldPublicLeague = League::create(['name' => 'Old Public League', 'is_public' => true, 'tournament_id' => $oldTournament->id]);

        $newTournament = Tournament::create(['name' => 'New Cup', 'slug' => 'new-cup', 'sport' => 'football', 'status' => 'active']);
        $newPublicLeague = League::create(['name' => 'New Public League', 'is_public' => true, 'tournament_id' => $newTournament->id]);

        // Simulates clicking "Prisijungti ir dalyvauti" from the new tournament's
        // page, which sets ?tournament=new-cup on the login/register GET.
        $this->get(route('register', ['tournament' => $newTournament->slug]))->assertOk();

        $this->post(route('register'), [
            'username' => 'newuser', 'name' => 'New', 'surname' => 'User',
            'email' => 'newuser@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect(route('main', absolute: false));

        $user = User::where('email', 'newuser@example.com')->firstOrFail();

        $this->assertDatabaseHas('league_members', [
            'league_id' => $newPublicLeague->id,
            'user_id' => $user->id,
            'active' => true,
        ]);
        $this->assertDatabaseMissing('league_members', [
            'league_id' => $oldPublicLeague->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_registration_without_intended_tournament_joins_the_active_one(): void
    {
        $finished = Tournament::create(['name' => 'Finished Cup', 'slug' => 'finished-cup', 'sport' => 'football', 'status' => 'finished']);
        League::create(['name' => 'Finished Public League', 'is_public' => true, 'tournament_id' => $finished->id]);

        $active = Tournament::create(['name' => 'Active Cup', 'slug' => 'active-cup', 'sport' => 'football', 'status' => 'active', 'start_date' => now()]);
        $activePublicLeague = League::create(['name' => 'Active Public League', 'is_public' => true, 'tournament_id' => $active->id]);

        // Direct navigation to /register, no tournament context at all.
        $this->post(route('register'), [
            'username' => 'directuser', 'name' => 'Direct', 'surname' => 'User',
            'email' => 'direct@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect(route('main', absolute: false));

        $user = User::where('email', 'direct@example.com')->firstOrFail();

        $this->assertDatabaseHas('league_members', [
            'league_id' => $activePublicLeague->id,
            'user_id' => $user->id,
            'active' => true,
        ]);
    }

    public function test_league_creation_belongs_to_the_current_tournament_not_tournament_one(): void
    {
        Tournament::create(['name' => 'Other Old Cup', 'slug' => 'other-old-cup', 'sport' => 'football', 'status' => 'finished']);

        $newTournament = Tournament::create(['name' => 'New Cup', 'slug' => 'new-cup', 'sport' => 'football', 'status' => 'active']);
        $user = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->withSession(['userID' => $user->id, 'tournamentID' => $newTournament->id])
            ->post(route('leagues.create'), ['name' => 'My Private League'])
            ->assertRedirect(route('leagues.index'));

        $league = League::where('name', 'My Private League')->firstOrFail();
        $this->assertEquals($newTournament->id, $league->tournament_id);
        $this->assertNotEquals(1, $league->tournament_id);
    }

    public function test_leagues_index_only_shows_leagues_in_the_current_tournament(): void
    {
        $oldTournament = Tournament::create(['name' => 'Old Cup', 'slug' => 'old-cup', 'sport' => 'football', 'status' => 'finished']);
        $oldLeague = League::create(['name' => 'Old League', 'is_public' => false, 'tournament_id' => $oldTournament->id]);

        $newTournament = Tournament::create(['name' => 'New Cup', 'slug' => 'new-cup', 'sport' => 'football', 'status' => 'active']);
        $newLeague = League::create(['name' => 'New League', 'is_public' => false, 'tournament_id' => $newTournament->id]);

        $user = User::factory()->create();
        LeagueMember::create(['league_id' => $oldLeague->id, 'user_id' => $user->id, 'active' => false, 'is_guest' => false]);
        LeagueMember::create(['league_id' => $newLeague->id, 'user_id' => $user->id, 'active' => true, 'is_guest' => false]);

        $response = $this->actingAs($user)
            ->withSession(['userID' => $user->id, 'tournamentID' => $newTournament->id])
            ->get(route('leagues.index'));

        $response->assertOk();
        $response->assertSee('New League', false);
        $response->assertDontSee('Old League', false);
    }
}
