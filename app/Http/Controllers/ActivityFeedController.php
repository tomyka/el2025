<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ActivityFeedController extends Controller
{
    public function getFeed(int $leagueID, int $limit = 20): array
    {
        $guest = (int) session('guest', 0);

        $bingos  = $this->getBingos($leagueID, $guest);
        $streaks = $this->getStreaks($leagueID, $guest);

        return array_merge($bingos, $streaks);
    }

    // ── Bingos grouped by game ────────────────────────────────────────────────

    private function getBingos(int $leagueID, int $guest): array
    {
        $rows = DB::table('point_results as pr')
            ->join('games as g', 'g.id', '=', 'pr.game_id')
            ->join('league_members as lm', 'lm.user_id', '=', 'pr.user_id')
            ->join('users as u', 'u.id', '=', 'pr.user_id')
            ->join('teams as ht', 'ht.id', '=', 'g.home_team_id')
            ->join('teams as at', 'at.id', '=', 'g.away_team_id')
            ->where('lm.league_id', $leagueID)
            ->where('lm.active', true)
            ->where('lm.is_guest', '<=', $guest)
            ->where('pr.bingo_points', '>', 0)
            ->whereNotNull('g.home_team_score')
            ->whereNotNull('g.away_team_score')
            ->groupByRaw('g.id, g.game_date, g.home_team_score, g.away_team_score, ht.team, at.team')
            ->orderByDesc('g.game_date')
            ->limit(3)
            ->selectRaw(
                "g.game_date, g.home_team_score, g.away_team_score,
                 ht.team as home_team, at.team as away_team,
                 GROUP_CONCAT(u.username ORDER BY u.username SEPARATOR ', ') as players"
            )
            ->get();

        return $rows->map(fn($r) => [
            'type'    => 'bingo',
            'game'    => "{$r->home_team} {$r->home_team_score}–{$r->away_team_score} {$r->away_team}",
            'players' => $r->players,
            'ago'     => Carbon::parse($r->game_date)->diffForHumans(now(), true),
        ])->toArray();
    }

    // ── Streaks grouped by length (3+) ───────────────────────────────────────

    private function getStreaks(int $leagueID, int $guest): array
    {
        // Find each user's last scored game — only streaks alive on that game count as active
        $latestGame = DB::table('point_results as pr2')
            ->join('games as g2', 'g2.id', '=', 'pr2.game_id')
            ->whereNotNull('g2.home_team_score')
            ->whereNotNull('g2.away_team_score')
            ->groupBy('pr2.user_id')
            ->selectRaw('pr2.user_id, MAX(g2.id) as last_game_id');

        $rows = DB::table('point_results as pr')
            ->join('games as g', 'g.id', '=', 'pr.game_id')
            ->join('events as e', 'e.id', '=', 'g.event_id')
            ->join('league_members as lm', 'lm.user_id', '=', 'pr.user_id')
            ->join('users as u', 'u.id', '=', 'pr.user_id')
            ->joinSub($latestGame, 'latest', function ($join) {
                $join->on('latest.user_id', '=', 'pr.user_id')
                     ->on('latest.last_game_id', '=', 'pr.game_id');
            })
            ->where('lm.league_id', $leagueID)
            ->where('lm.active', true)
            ->where('lm.is_guest', '<=', $guest)
            ->whereNotNull('g.home_team_score')
            ->whereNotNull('g.away_team_score')
            ->whereRaw('pr.streak_bonus >= 2 * e.rate')
            ->orderByRaw('ROUND(pr.streak_bonus / NULLIF(e.rate, 0)) + 1 DESC')
            ->limit(5)
            ->selectRaw(
                "u.username,
                 ROUND(pr.streak_bonus / NULLIF(e.rate, 0)) + 1 as streak_length,
                 g.game_date"
            )
            ->get();

        return $rows->map(fn($r) => [
            'type'     => 'streak',
            'username' => $r->username,
            'length'   => (int) $r->streak_length,
            'ago'      => Carbon::parse($r->game_date)->diffForHumans(now(), true),
        ])->toArray();
    }

}
