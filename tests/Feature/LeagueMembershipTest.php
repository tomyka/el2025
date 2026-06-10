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
        League::create(['name' => 'Public League', 'is_public' => true]);
    }

    public function test_authenticated_user_can_create_league(): void
    {
        $user = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $user->id, 'admin' => 0]);

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
        $this->assertFalse((bool) $league->is_public);

        $member = LeagueMember::where('league_id', $league->id)
            ->where('user_id', $user->id)
            ->first();
        $this->assertNotNull($member);
        $this->assertTrue((bool) $member->is_admin);
    }
}
