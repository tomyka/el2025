<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ActivityFeedController extends Controller
{
    public function getFeed(int $leagueID, int $limit = 20): array
    {
        $rows = DB::table('point_results as pr')
            ->join('games as g', 'g.id', '=', 'pr.game_id')
            ->join('league_members as lm', 'lm.user_id', '=', 'pr.user_id')
            ->join('users as u', 'u.id', '=', 'pr.user_id')
            ->join('teams as ht', 'ht.id', '=', 'g.home_team_id')
            ->join('teams as at', 'at.id', '=', 'g.away_team_id')
            ->where('lm.league_id', $leagueID)
            ->where('lm.is_guest', '<=', session('guest', 0))
            ->whereNotNull('g.home_team_score')
            ->whereNotNull('g.away_team_score')
            ->where(function ($q) {
                $q->where('pr.bingo_points', '>', 0)
                  ->orWhere('pr.streak_bonus', '>', 0)
                  ->orWhere(function ($q2) {
                      $q2->where('pr.winner_points', '>', 0)
                         ->where('pr.odds', '>', 0.3);
                  });
            })
            ->orderByDesc('g.game_date')
            ->orderByDesc('pr.bingo_points')
            ->limit($limit)
            ->select(
                'u.username',
                'g.game_date',
                'g.home_team_score',
                'g.away_team_score',
                'ht.team as home_team',
                'at.team as away_team',
                'pr.bingo_points',
                'pr.winner_points',
                'pr.streak_bonus',
                'pr.odds'
            )
            ->get();

        return $rows->map(function ($r) {
            if ($r->bingo_points > 0) {
                $icon = '🎯';
                $text = "tiksliai: {$r->home_team} {$r->home_team_score}–{$r->away_team_score} {$r->away_team}";
            } elseif ($r->streak_bonus > 0) {
                $icon = '🔥';
                $text = 'serija!';
            } else {
                $icon = '⭐';
                $mult = number_format(1 + (float) $r->odds, 2);
                $text = "nugalėtojas ×{$mult}";
            }

            return [
                'icon'     => $icon,
                'username' => $r->username,
                'text'     => $text,
                'ago'      => Carbon::parse($r->game_date)->diffForHumans(now(), true),
            ];
        })->toArray();
    }
}
