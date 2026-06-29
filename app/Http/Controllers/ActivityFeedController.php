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
        $wins    = $this->getWins($leagueID, $guest);

        return array_merge($bingos, $streaks, $wins);
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
            ->limit(5)
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
        $inner = DB::table('point_results as pr')
            ->join('games as g', 'g.id', '=', 'pr.game_id')
            ->join('events as e', 'e.id', '=', 'g.event_id')
            ->join('league_members as lm', 'lm.user_id', '=', 'pr.user_id')
            ->join('users as u', 'u.id', '=', 'pr.user_id')
            ->where('lm.league_id', $leagueID)
            ->where('lm.active', true)
            ->where('lm.is_guest', '<=', $guest)
            ->whereNotNull('g.home_team_score')
            ->whereNotNull('g.away_team_score')
            ->whereRaw('pr.streak_bonus >= 2 * e.rate')
            ->selectRaw(
                "ROUND(pr.streak_bonus / NULLIF(e.rate, 0)) + 1 as streak_length,
                 u.username, g.game_date"
            );

        $rows = DB::table(DB::raw("({$inner->toSql()}) as sub"))
            ->mergeBindings($inner)
            ->groupBy('streak_length')
            ->orderByDesc('streak_length')
            ->limit(5)
            ->selectRaw(
                "streak_length,
                 GROUP_CONCAT(DISTINCT username ORDER BY username SEPARATOR ', ') as players,
                 MAX(game_date) as game_date"
            )
            ->get();

        return $rows->map(fn($r) => [
            'type'    => 'streak',
            'length'  => (int) $r->streak_length,
            'players' => $r->players,
            'ago'     => Carbon::parse($r->game_date)->diffForHumans(now(), true),
        ])->toArray();
    }

    // ── Contrarian wins (individual) ─────────────────────────────────────────

    private function getWins(int $leagueID, int $guest): array
    {
        $rows = DB::table('point_results as pr')
            ->join('games as g', 'g.id', '=', 'pr.game_id')
            ->join('league_members as lm', 'lm.user_id', '=', 'pr.user_id')
            ->join('users as u', 'u.id', '=', 'pr.user_id')
            ->where('lm.league_id', $leagueID)
            ->where('lm.active', true)
            ->where('lm.is_guest', '<=', $guest)
            ->where('pr.winner_points', '>', 0)
            ->where('pr.odds', '>', 0.3)
            ->whereNotNull('g.home_team_score')
            ->whereNotNull('g.away_team_score')
            ->orderByDesc('g.game_date')
            ->orderByDesc('pr.odds')
            ->limit(5)
            ->select('u.username', 'g.game_date', 'pr.odds')
            ->get();

        return $rows->map(fn($r) => [
            'type'     => 'win',
            'username' => $r->username,
            'text'     => 'nugalėtojas ×' . number_format(1 + (float) $r->odds, 2),
            'ago'      => Carbon::parse($r->game_date)->diffForHumans(now(), true),
        ])->toArray();
    }
}
