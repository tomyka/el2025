<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Game;
use App\Models\League;
use App\Models\LeagueMember;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChartXssTest extends TestCase
{
    use RefreshDatabase;

    public function test_chart_escapes_html_in_username(): void
    {
        $user = User::factory()->create(['username' => '</script><script>alert(1)']);
        $league = League::factory()->create();
        LeagueMember::factory()->create(['user_id' => $user->id, 'league_id' => $league->id]);

        $event = Event::create(['event' => 'T', 'event_day' => 1, 'event_survival' => 0, 'active' => 1, 'rate' => 1]);
        $homeTeam = Team::create(['team' => 'Home']);
        $awayTeam = Team::create(['team' => 'Away']);
        Game::create([
            'event_id' => $event->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'game_date' => now()->subHour(),
            'home_team_score' => 1,
            'away_team_score' => 0,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['leagueID' => $league->id, 'guest' => 0])
            ->get(route('summary.chart'));

        $response->assertOk();
        // Literal <script> tag injected via username must NOT appear in inline JS
        $this->assertStringNotContainsString('<script>alert(1)', $response->getContent());
        // JSON_HEX_TAG encodes < as < — verify the escaped form is present
        $this->assertStringContainsString('\u003C', $response->getContent());
    }

    public function test_chart_escapes_html_in_team_names(): void
    {
        $user = User::factory()->create(['username' => 'normal']);
        $league = League::factory()->create();
        LeagueMember::factory()->create(['user_id' => $user->id, 'league_id' => $league->id]);

        $event = Event::create(['event' => 'T', 'event_day' => 1, 'event_survival' => 0, 'active' => 1, 'rate' => 1]);
        $homeTeam = Team::create(['team' => '<b>Bold</b>']);
        $awayTeam = Team::create(['team' => 'Away']);
        Game::create([
            'event_id' => $event->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'game_date' => now()->subHour(),
            'home_team_score' => 1,
            'away_team_score' => 0,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['leagueID' => $league->id, 'guest' => 0])
            ->get(route('summary.chart'));

        $response->assertOk();
        // Raw <b> tag must not appear in the JSON-encoded game labels
        $this->assertStringNotContainsString('<b>Bold', $response->getContent());
        // JSON_HEX_TAG encodes < as < — verify the escaped form is present
        $this->assertStringContainsString('\u003C', $response->getContent());
    }
}
