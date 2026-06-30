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

    public function test_tournament_model_creates_and_reads(): void
    {
        $t = \App\Models\Tournament::create([
            'name'   => 'Test Cup',
            'slug'   => 'test-cup',
            'sport'  => 'football',
            'status' => 'upcoming',
        ]);
        $this->assertDatabaseHas('tournaments', ['slug' => 'test-cup']);
        $this->assertEquals('Test Cup', \App\Models\Tournament::where('slug','test-cup')->first()->name);
    }

    public function test_league_belongs_to_tournament(): void
    {
        $t = \App\Models\Tournament::create(['name'=>'T','slug'=>'t','sport'=>'football','status'=>'upcoming']);
        $league = \App\Models\League::create(['name'=>'L','is_public'=>false,'tournament_id'=>$t->id]);
        $this->assertEquals($t->id, $league->tournament->id);
    }

    public function test_event_belongs_to_tournament(): void
    {
        $t = \App\Models\Tournament::create(['name'=>'T2','slug'=>'t2','sport'=>'football','status'=>'upcoming']);
        $event = \App\Models\Event::create([
            'event'=>'Round 1','event_day'=>1,'event_survival'=>0,'active'=>1,'rate'=>1,'tournament_id'=>$t->id
        ]);
        $this->assertEquals($t->id, $event->tournament->id);
    }
}
