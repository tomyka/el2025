<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Game;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_game_has_home_and_away_teams(): void
    {
        $event = Event::create(['event' => 'Euro 2024', 'event_day' => 1, 'event_survival' => 0, 'active' => 1, 'rate' => 1]);
        $homeTeam = Team::create(['team' => 'France', 'group_name' => 'A', 'group_position' => 1]);
        $awayTeam = Team::create(['team' => 'Germany', 'group_name' => 'A', 'group_position' => 2]);

        $game = Game::create([
            'game_date' => now()->addDay(),
            'event_id' => $event->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
        ]);

        $this->assertSame('France', $game->home_team->team);
        $this->assertSame('Germany', $game->away_team->team);
        $this->assertSame('Euro 2024', $game->event->event);
    }

    public function test_game_can_record_score(): void
    {
        $event = Event::create(['event' => 'Euro 2024', 'event_day' => 1, 'event_survival' => 0, 'active' => 1, 'rate' => 1]);
        $homeTeam = Team::create(['team' => 'France', 'group_name' => 'A', 'group_position' => 1]);
        $awayTeam = Team::create(['team' => 'Spain', 'group_name' => 'A', 'group_position' => 2]);

        $game = Game::create([
            'game_date' => now(),
            'event_id' => $event->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'home_team_score' => 2,
            'away_team_score' => 1,
        ]);

        $this->assertSame(2, $game->home_team_score);
        $this->assertSame(1, $game->away_team_score);
    }
}
