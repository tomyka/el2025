<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\Team;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_game_accepts_datetime_local_input(): void
    {
        $admin = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $admin->id, 'admin' => 1]);
        $home  = Team::create(['team' => 'Germany', 'group_name' => 'A', 'group_position' => 1]);
        $away  = Team::create(['team' => 'France',  'group_name' => 'B', 'group_position' => 1]);
        $event = Event::create(['event' => 'Group Stage', 'event_day' => 1, 'event_survival' => 0, 'active' => 1, 'rate' => 1]);
        $game  = Game::create([
            'game_date'    => '2026-06-10 18:00:00',
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'event_id'     => $event->id,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin' => 1, 'eventID' => $event->id, 'userID' => $admin->id])
            ->post(route('admin.updateGame'), [
                'gameID'       => $game->id,
                'gameDateTime' => '2026-06-11T21:30',
                'homeTeamID'   => $home->id,
                'awayTeamID'   => $away->id,
                'eventID'      => $event->id,
            ]);

        $response->assertRedirect(route('admin.games'));
        $this->assertSame('2026-06-11 18:30:00', $game->fresh()->game_date);
    }

    public function test_insert_game_accepts_datetime_local_input(): void
    {
        $admin = User::factory()->create();
        UserSetting::factory()->create(['user_id' => $admin->id, 'admin' => 1]);
        $home  = Team::create(['team' => 'Germany', 'group_name' => 'A', 'group_position' => 1]);
        $away  = Team::create(['team' => 'France',  'group_name' => 'B', 'group_position' => 1]);
        $event = Event::create(['event' => 'Group Stage', 'event_day' => 1, 'event_survival' => 0, 'active' => 1, 'rate' => 1]);

        $response = $this->actingAs($admin)
            ->withSession(['admin' => 1, 'eventID' => $event->id, 'userID' => $admin->id])
            ->post(route('admin.insertGame'), [
                'gameDateTime' => '2026-06-12T19:00',
                'homeTeamID'   => $home->id,
                'awayTeamID'   => $away->id,
                'eventID'      => $event->id,
            ]);

        $response->assertRedirect(route('admin.games'));
        $game = Game::where('home_team_id', $home->id)->where('away_team_id', $away->id)->first();
        $this->assertNotNull($game);
        $this->assertSame('2026-06-12 16:00:00', $game->game_date);
    }
}
