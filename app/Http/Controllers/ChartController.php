<?php

namespace App\Http\Controllers;

use DB;

class ChartController extends Controller
{
    public function getChartData()
    {
        $groupID = session('leagueID');
        $guest   = session('guest', 0);

        // All scored games in chronological order
        $games = DB::select('
            SELECT g.id, g.game_date,
                   ht.team AS home_team,
                   at.team AS away_team
            FROM games g
                JOIN teams ht ON g.home_team_id = ht.id
                JOIN teams at ON g.away_team_id = at.id
            WHERE g.home_team_score IS NOT NULL
            ORDER BY g.game_date ASC, g.id ASC
        ');

        // Users in this group (respect guest visibility setting)
        $users = DB::table('league_members')
            ->where('league_members.league_id', $groupID)
            ->where('league_members.is_guest', '<=', $guest)
            ->join('users', 'league_members.user_id', '=', 'users.id')
            ->leftJoin('colors', 'league_members.user_id', '=', 'colors.id')
            ->select('users.id', 'users.username', 'colors.color_code')
            ->get();

        // Full points (including streak bonus) per user per game
        $rows = DB::select('
            SELECT pr.game_id, pr.user_id,
                   ROUND(IFNULL(por.full_points, 0), 2)    AS points,
                   ROUND(IFNULL(por.streak_bonus, 0), 2)   AS streak_bonus
            FROM prediction_results pr
                JOIN games g ON pr.game_id = g.id
                JOIN league_members lm ON pr.user_id = lm.user_id
                    AND lm.league_id = ?
                    AND lm.is_guest <= ?
                LEFT JOIN point_results por
                    ON por.user_id = pr.user_id AND por.game_id = pr.game_id
            WHERE g.home_team_score IS NOT NULL
        ', [$groupID, $guest]);

        // Index: pointsMap[user_id][game_id] = points
        $pointsMap = [];
        foreach ($rows as $row) {
            $pointsMap[$row->user_id][$row->game_id] = (float) $row->points + (float) $row->streak_bonus;
        }

        $palette = [
            '#3b82f6', '#ef4444', '#22c55e', '#f59e0b', '#8b5cf6',
            '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#84cc16',
        ];

        $gameIds = array_column($games, 'id');

        $datasets = [];
        foreach ($users as $idx => $user) {
            $color      = $user->color_code ?: $palette[$idx % count($palette)];
            $cumulative = 0;
            $data       = [];
            foreach ($gameIds as $gameId) {
                $cumulative += $pointsMap[$user->id][$gameId] ?? 0;
                $data[] = round($cumulative, 1);
            }
            $datasets[] = [
                'label'           => $user->username,
                'data'            => $data,
                'borderColor'     => $color,
                'backgroundColor' => $color . '26',
                'borderWidth'     => 2,
                'pointRadius'     => count($games) > 40 ? 0 : 3,
                'pointHoverRadius'=> 6,
                'tension'         => 0.3,
                'fill'            => false,
            ];
        }

        // Game labels for tooltip (team abbreviations)
        $gameLabels = array_map(
            fn($g) => strtoupper(substr($g->home_team, 0, 3))
                    . ' - '
                    . strtoupper(substr($g->away_team, 0, 3)),
            $games
        );

        $jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

        return view('summary.chart')
            ->with('datasets',   json_encode($datasets,   $jsonFlags))
            ->with('gameLabels', json_encode($gameLabels, $jsonFlags))
            ->with('gameCount',  count($games));
    }
}
