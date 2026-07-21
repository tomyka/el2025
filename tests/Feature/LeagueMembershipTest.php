<?php

namespace Tests\Feature;

use App\Models\League;
use App\Models\LeagueInvite;
use App\Models\LeagueMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeagueMembershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        League::create(['name' => 'Public League', 'is_public' => true, 'tournament_id' => 1]);
    }

    public function test_authenticated_user_can_create_league(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['userID' => $user->id, 'tournamentID' => 1])
            ->post(route('leagues.create'), [
                'name' => 'Friends Liga',
                'description' => 'Our private league',
                'base_fee' => 10,
                'penalty_step' => 5,
            ])->assertRedirect(route('leagues.index'));

        $league = League::where('name', 'Friends Liga')->first();
        $this->assertNotNull($league);
        $this->assertEquals($user->id, $league->owner_id);
        $this->assertFalse($league->is_public);
        $this->assertEquals(1, $league->tournament_id);

        $member = LeagueMember::where('league_id', $league->id)
            ->where('user_id', $user->id)
            ->first();
        $this->assertNotNull($member);
        $this->assertTrue($member->is_admin);
    }

    public function test_league_admin_can_invite_user(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create();

        $league = League::create(['name' => 'Test', 'is_public' => false, 'owner_id' => $owner->id, 'tournament_id' => 1]);
        LeagueMember::create(['league_id' => $league->id, 'user_id' => $owner->id, 'is_admin' => true, 'active' => false, 'is_guest' => false]);

        $this->actingAs($owner)->withSession(['userID' => $owner->id])
            ->post(route('leagues.invite'), [
                'leagueID' => $league->id,
                'invitedUserID' => $invitee->id,
            ])->assertRedirect();

        $this->assertDatabaseHas('league_invites', [
            'league_id' => $league->id,
            'invited_user_id' => $invitee->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_accept_invite(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create();

        $league = League::create(['name' => 'Test', 'is_public' => false, 'owner_id' => $owner->id, 'tournament_id' => 1]);
        LeagueMember::create(['league_id' => $league->id, 'user_id' => $owner->id, 'is_admin' => true, 'active' => false, 'is_guest' => false]);

        $invite = LeagueInvite::create([
            'league_id' => $league->id,
            'invited_user_id' => $invitee->id,
            'invited_by_id' => $owner->id,
            'status' => 'pending',
        ]);

        $this->actingAs($invitee)->withSession(['userID' => $invitee->id])
            ->post(route('leagues.accept'), ['inviteID' => $invite->id])
            ->assertRedirect(route('leagues.index'));

        $this->assertDatabaseHas('league_members', [
            'league_id' => $league->id,
            'user_id' => $invitee->id,
        ]);
        $this->assertDatabaseMissing('league_invites', ['id' => $invite->id]);
    }

    public function test_user_can_decline_invite(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create();

        $league = League::create(['name' => 'Test', 'is_public' => false, 'owner_id' => $owner->id, 'tournament_id' => 1]);

        $invite = LeagueInvite::create([
            'league_id' => $league->id,
            'invited_user_id' => $invitee->id,
            'invited_by_id' => $owner->id,
            'status' => 'pending',
        ]);

        $this->actingAs($invitee)->withSession(['userID' => $invitee->id])
            ->post(route('leagues.decline'), ['inviteID' => $invite->id])
            ->assertRedirect(route('leagues.index'));

        $this->assertDatabaseMissing('league_invites', ['id' => $invite->id]);
        $this->assertDatabaseMissing('league_members', ['league_id' => $league->id, 'user_id' => $invitee->id]);
    }

    public function test_user_can_switch_active_league(): void
    {
        $user = User::factory()->create();

        $publicLeague = League::where('is_public', true)->first();
        $privateLeague = League::create(['name' => 'Private', 'is_public' => false, 'owner_id' => $user->id, 'tournament_id' => 1]);

        LeagueMember::create(['league_id' => $publicLeague->id,  'user_id' => $user->id, 'active' => true,  'is_guest' => false, 'is_admin' => false]);
        LeagueMember::create(['league_id' => $privateLeague->id, 'user_id' => $user->id, 'active' => false, 'is_guest' => false, 'is_admin' => true]);

        $this->actingAs($user)->withSession(['userID' => $user->id, 'leagueID' => $publicLeague->id])
            ->post(route('leagues.switch'), ['leagueID' => $privateLeague->id])
            ->assertRedirect();

        $this->assertDatabaseHas('league_members', ['league_id' => $publicLeague->id,  'user_id' => $user->id, 'active' => false]);
        $this->assertDatabaseHas('league_members', ['league_id' => $privateLeague->id, 'user_id' => $user->id, 'active' => true]);
    }

    public function test_user_can_leave_private_league(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $publicLeague = League::where('is_public', true)->first();
        $privateLeague = League::create(['name' => 'Private', 'is_public' => false, 'owner_id' => $owner->id, 'tournament_id' => 1]);

        LeagueMember::create(['league_id' => $publicLeague->id,  'user_id' => $user->id, 'active' => false, 'is_guest' => false, 'is_admin' => false]);
        LeagueMember::create(['league_id' => $privateLeague->id, 'user_id' => $user->id, 'active' => true,  'is_guest' => false, 'is_admin' => false]);

        $this->actingAs($user)->withSession(['userID' => $user->id, 'leagueID' => $privateLeague->id])
            ->post(route('leagues.leave'), ['leagueID' => $privateLeague->id])
            ->assertRedirect(route('leagues.index'));

        $this->assertDatabaseMissing('league_members', ['league_id' => $privateLeague->id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('league_members', ['league_id' => $publicLeague->id, 'user_id' => $user->id, 'active' => true]);
    }

    public function test_user_cannot_leave_public_league(): void
    {
        $user = User::factory()->create();
        $publicLeague = League::where('is_public', true)->first();

        LeagueMember::create(['league_id' => $publicLeague->id, 'user_id' => $user->id, 'active' => true, 'is_guest' => false, 'is_admin' => false]);

        $this->actingAs($user)->withSession(['userID' => $user->id])
            ->post(route('leagues.leave'), ['leagueID' => $publicLeague->id])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('league_members', ['league_id' => $publicLeague->id, 'user_id' => $user->id]);
    }

    public function test_league_admin_can_search_users(): void
    {
        $admin = User::factory()->create(['username' => 'tomas_k', 'name' => 'Tomas', 'surname' => 'K']);
        $other = User::factory()->create(['username' => 'john_d',  'name' => 'John',  'surname' => 'D']);
        $league = League::create(['name' => 'Test', 'is_public' => false, 'owner_id' => $admin->id, 'tournament_id' => 1]);
        LeagueMember::create(['league_id' => $league->id, 'user_id' => $admin->id, 'is_admin' => true, 'active' => false, 'is_guest' => false]);

        $this->actingAs($admin)->withSession(['userID' => $admin->id])
            ->getJson(route('leagues.searchUsers', ['query' => 'john', 'leagueID' => $league->id]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['id' => $other->id]);
    }
}
