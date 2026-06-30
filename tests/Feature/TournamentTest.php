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
