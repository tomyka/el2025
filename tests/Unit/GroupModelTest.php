<?php

namespace Tests\Unit;

use App\Models\Group;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_can_be_created(): void
    {
        $group = Group::factory()->create([
            'group' => 'Euro 2024',
            'group_description' => 'Group A',
            'fee' => 50,
        ]);

        $this->assertSame('Euro 2024', $group->group);
        $this->assertSame('Group A', $group->group_description);
        $this->assertSame(50, $group->fee);
    }

    public function test_group_fillable_attributes(): void
    {
        $group = Group::create([
            'group' => 'Champions League',
            'group_description' => 'Group Stage',
            'fee' => 100,
            'reward_ratio' => 2.5,
            'reward_description' => '2.5x payout',
        ]);

        $this->assertNotNull($group->id);
        $this->assertSame('Champions League', $group->group);
    }
}
