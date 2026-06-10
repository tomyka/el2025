<?php
namespace Tests\Feature;

use App\Models\League;
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
        League::create(['name' => 'Public League', 'is_public' => true]);
    }

    public function test_authenticated_user_can_create_league(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['userID' => $user->id])
            ->post(route('leagues.create'), [
                'name'         => 'Friends Liga',
                'description'  => 'Our private league',
                'base_fee'     => 10,
                'penalty_step' => 5,
            ])->assertRedirect(route('leagues.index'));

        $league = League::where('name', 'Friends Liga')->first();
        $this->assertNotNull($league);
        $this->assertEquals($user->id, $league->owner_id);
        $this->assertFalse($league->is_public);

        $member = LeagueMember::where('league_id', $league->id)
            ->where('user_id', $user->id)
            ->first();
        $this->assertNotNull($member);
        $this->assertTrue($member->is_admin);
    }

    public function test_league_admin_can_invite_user(): void
    {
        $owner   = User::factory()->create();
        $invitee = User::factory()->create();

        $league = League::create(['name' => 'Test', 'is_public' => false, 'owner_id' => $owner->id]);
        LeagueMember::create(['league_id' => $league->id, 'user_id' => $owner->id, 'is_admin' => true, 'active' => false, 'is_guest' => false]);

        $this->actingAs($owner)->withSession(['userID' => $owner->id])
            ->post(route('leagues.invite'), [
                'leagueID'      => $league->id,
                'invitedUserID' => $invitee->id,
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

        $league = League::create(['name' => 'Test', 'is_public' => false, 'owner_id' => $owner->id]);
        LeagueMember::create(['league_id' => $league->id, 'user_id' => $owner->id, 'is_admin' => true, 'active' => false, 'is_guest' => false]);

        $invite = \App\Models\LeagueInvite::create([
            'league_id'       => $league->id,
            'invited_user_id' => $invitee->id,
            'invited_by_id'   => $owner->id,
            'status'          => 'pending',
        ]);

        $this->actingAs($invitee)->withSession(['userID' => $invitee->id])
            ->post(route('leagues.accept'), ['inviteID' => $invite->id])
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

        $this->actingAs($invitee)->withSession(['userID' => $invitee->id])
            ->post(route('leagues.decline'), ['inviteID' => $invite->id])
            ->assertRedirect(route('leagues.index'));

        $this->assertDatabaseMissing('league_invites', ['id' => $invite->id]);
        $this->assertDatabaseMissing('league_members', ['league_id' => $league->id, 'user_id' => $invitee->id]);
    }
}
