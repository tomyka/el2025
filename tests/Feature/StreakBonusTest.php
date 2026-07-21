<?php

namespace Tests\Feature;

use App\Http\Controllers\PointResultController;
use App\Models\Event;
use App\Models\Game;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StreakBonusTest extends TestCase
{
    use RefreshDatabase;

    private function makeGame(): Game
    {
        $event = Event::create(['event' => 'E', 'event_day' => 1, 'event_survival' => 0, 'rate' => 1]);
        $home = Team::create(['team' => 'H'.uniqid()]);
        $away = Team::create(['team' => 'A'.uniqid()]);

        return Game::create([
            'game_date' => now(),
            'event_id' => $event->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'home_team_score' => 1,
            'away_team_score' => 0,
        ]);
    }

    private function insertPointResult(int $userID, int $gameID, float $winnerPoints, bool $generated = false): void
    {
        DB::table('prediction_results')->insert([
            'user_id' => $userID,
            'game_id' => $gameID,
            'home_team_score' => 1,
            'away_team_score' => 0,
            'generated' => $generated ? 1 : 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('point_results')->insert([
            'user_id' => $userID,
            'game_id' => $gameID,
            'winner_points' => $winnerPoints,
            'difference_points' => 0,
            'bingo_points' => 0,
            'odds' => 1,
            'odds_points' => 0,
            'full_points' => $winnerPoints,
            'streak_bonus' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_first_correct_prediction_gets_no_bonus(): void
    {
        $user = User::factory()->create();
        $game = $this->makeGame();
        $this->insertPointResult($user->id, $game->id, winnerPoints: 5.0);

        app(PointResultController::class)->recalculateStreaks();

        $bonus = DB::table('point_results')
            ->where('user_id', $user->id)->where('game_id', $game->id)
            ->value('streak_bonus');

        $this->assertEquals(0, $bonus);
    }

    public function test_consecutive_correct_predictions_increment_streak(): void
    {
        $user = User::factory()->create();
        $g1 = $this->makeGame();
        $g2 = $this->makeGame();
        $g3 = $this->makeGame();

        // Ensure g1.id < g2.id < g3.id by using IDs as-created (auto-increment)
        $this->insertPointResult($user->id, $g1->id, winnerPoints: 5.0);
        $this->insertPointResult($user->id, $g2->id, winnerPoints: 5.0);
        $this->insertPointResult($user->id, $g3->id, winnerPoints: 5.0);

        app(PointResultController::class)->recalculateStreaks();

        $bonuses = DB::table('point_results')
            ->where('user_id', $user->id)
            ->orderBy('game_id')
            ->pluck('streak_bonus')
            ->toArray();

        $this->assertEquals([0, 1, 2], $bonuses);
    }

    public function test_wrong_prediction_resets_streak_to_zero(): void
    {
        $user = User::factory()->create();
        $g1 = $this->makeGame();
        $g2 = $this->makeGame();
        $g3 = $this->makeGame();

        $this->insertPointResult($user->id, $g1->id, winnerPoints: 5.0);
        $this->insertPointResult($user->id, $g2->id, winnerPoints: 0.0); // wrong
        $this->insertPointResult($user->id, $g3->id, winnerPoints: 5.0);

        app(PointResultController::class)->recalculateStreaks();

        $bonuses = DB::table('point_results')
            ->where('user_id', $user->id)
            ->orderBy('game_id')
            ->pluck('streak_bonus')
            ->toArray();

        $this->assertEquals([0, 0, 0], $bonuses);
    }

    public function test_generated_prediction_resets_streak(): void
    {
        $user = User::factory()->create();
        $g1 = $this->makeGame();
        $g2 = $this->makeGame();
        $g3 = $this->makeGame();

        $this->insertPointResult($user->id, $g1->id, winnerPoints: 5.0);
        $this->insertPointResult($user->id, $g2->id, winnerPoints: 5.0, generated: true); // auto-generated
        $this->insertPointResult($user->id, $g3->id, winnerPoints: 5.0);

        app(PointResultController::class)->recalculateStreaks();

        $bonuses = DB::table('point_results')
            ->where('user_id', $user->id)
            ->orderBy('game_id')
            ->pluck('streak_bonus')
            ->toArray();

        $this->assertEquals([0, 0, 0], $bonuses);
    }

    public function test_missing_point_result_row_resets_streak(): void
    {
        $user = User::factory()->create();
        $g1 = $this->makeGame();
        $g2 = $this->makeGame(); // no row for this game
        $g3 = $this->makeGame();

        $this->insertPointResult($user->id, $g1->id, winnerPoints: 5.0);
        // deliberately skip g2
        $this->insertPointResult($user->id, $g3->id, winnerPoints: 5.0);

        app(PointResultController::class)->recalculateStreaks();

        $bonuses = DB::table('point_results')
            ->where('user_id', $user->id)
            ->orderBy('game_id')
            ->pluck('streak_bonus')
            ->toArray();

        // g1=0, g2 missing (streak resets), g3=0
        $this->assertEquals([0, 0], $bonuses);
    }

    public function test_streaks_are_independent_per_user(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $g1 = $this->makeGame();
        $g2 = $this->makeGame();

        $this->insertPointResult($u1->id, $g1->id, winnerPoints: 5.0);
        $this->insertPointResult($u1->id, $g2->id, winnerPoints: 0.0); // u1 misses g2

        $this->insertPointResult($u2->id, $g1->id, winnerPoints: 5.0);
        $this->insertPointResult($u2->id, $g2->id, winnerPoints: 5.0); // u2 hits g2

        app(PointResultController::class)->recalculateStreaks();

        $u1bonuses = DB::table('point_results')->where('user_id', $u1->id)->orderBy('game_id')->pluck('streak_bonus')->toArray();
        $u2bonuses = DB::table('point_results')->where('user_id', $u2->id)->orderBy('game_id')->pluck('streak_bonus')->toArray();

        $this->assertEquals([0, 0], $u1bonuses);
        $this->assertEquals([0, 1], $u2bonuses);
    }

    public function test_recalculate_is_idempotent(): void
    {
        $user = User::factory()->create();
        $g1 = $this->makeGame();
        $g2 = $this->makeGame();

        $this->insertPointResult($user->id, $g1->id, winnerPoints: 5.0);
        $this->insertPointResult($user->id, $g2->id, winnerPoints: 5.0);

        $controller = app(PointResultController::class);
        $controller->recalculateStreaks();
        $controller->recalculateStreaks();

        $bonuses = DB::table('point_results')
            ->where('user_id', $user->id)
            ->orderBy('game_id')
            ->pluck('streak_bonus')
            ->toArray();

        $this->assertEquals([0, 1], $bonuses);
    }

    public function test_streak_bonus_returned_separately_in_user_profile_points(): void
    {
        $user = User::factory()->create();
        $g1 = $this->makeGame();
        $g2 = $this->makeGame();

        $this->insertPointResult($user->id, $g1->id, winnerPoints: 5.0);
        $this->insertPointResult($user->id, $g2->id, winnerPoints: 5.0);

        app(PointResultController::class)->recalculateStreaks();

        $profilePoints = app(PointResultController::class)->getUserProfilePoints($user->id);
        $baseTotal = array_sum(array_column($profilePoints, 'full_points'));
        $streakTotal = array_sum(array_column($profilePoints, 'streak_bonus'));

        $this->assertEquals(10.0, $baseTotal);   // base: 5+5
        $this->assertEquals(1.0, $streakTotal);  // streak: 0+1
    }
}
