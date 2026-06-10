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
