<?php

namespace Tests\Unit;

use App\Models\League;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_league_can_be_created(): void
    {
        $league = League::factory()->create([
            'name'     => 'Euro 2024',
            'base_fee' => 50,
        ]);

        $this->assertSame('Euro 2024', $league->name);
        $this->assertSame(50, $league->base_fee);
    }

    public function test_league_fillable_attributes(): void
    {
        $league = League::create([
            'name'               => 'Champions League',
            'base_fee'           => 100,
            'penalty_step'       => 10,
            'reward_description' => '2.5x payout',
            'is_public'          => false,
            'use_league_odds'    => false,
        ]);

        $this->assertNotNull($league->id);
        $this->assertSame('Champions League', $league->name);
    }
}
