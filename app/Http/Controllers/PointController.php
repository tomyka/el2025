<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class PointController extends Controller
{

    public function getAllUserPoints($leagueID): array
    {
        $users = DB::table('users')
            ->join('league_members', 'users.id', '=', 'league_members.user_id')
            ->where('league_members.league_id', '=', $leagueID)
            ->where('league_members.is_guest', '<=', session('guest'))
            ->select('users.id', 'users.username', 'users.name', 'users.surname')
            ->get();

        if ($users->isEmpty()) {
            return [];
        }

        $userIds = $users->pluck('id')->toArray();

        $pointsResultController  = app(PointResultController::class);
        $pointStandingController = app(PointStandingController::class);
        $pointSurvivalController = new PointSurvivalController();

        $gamePoints     = $pointsResultController->getBulkUserGamePoints($userIds, $leagueID);
        $standingPoints = $pointStandingController->getBulkUserStandingPoints($userIds);
        $survivalPoints = $pointSurvivalController->getBulkUserSurvivalPoints($userIds);

        $userAllPoints = [];
        foreach ($users as $user) {
            $gp = $gamePoints[$user->id] ?? ['game_points' => 0.0, 'streak_points' => 0.0,
                                              'bingo_count' => 0, 'game_count' => 0];

            $userAllPoints[] = [
                'userID'          => $user->id,
                'username'        => $user->username,
                'name'            => $user->name,
                'surname'         => $user->surname,
                'userFee'         => null,
                'userGamePoints'  => round($gp['game_points'], 1),
                'userStreakPoints' => round($gp['streak_points'], 1),
                'userGameBingo'   => $gp['bingo_count'],
                'averagePoints'   => $gp['game_count'] > 0
                                        ? round($gp['game_points'] / $gp['game_count'], 1)
                                        : 0,
                'standingPoints'  => $standingPoints[$user->id],
                'survivalPoints'  => $survivalPoints[$user->id],
            ];
        }

        usort($userAllPoints, function ($a, $b) {
            return $b['userGamePoints'] + $b['userStreakPoints']
                 + $b['standingPoints']->total_points + $b['survivalPoints']
               <=> $a['userGamePoints'] + $a['userStreakPoints']
                 + $a['standingPoints']->total_points + $a['survivalPoints'];
        });

        return $userAllPoints;
    }
    public function getAllUsersGameHistory(int $leagueID): array
    {
        $userIDs = DB::table('users')
            ->join('league_members', 'users.id', '=', 'league_members.user_id')
            ->where('league_members.league_id', '=', $leagueID)
            ->where('league_members.is_guest', '<=', session('guest'))
            ->pluck('users.id')
            ->toArray();

        if (empty($userIDs)) {
            return [];
        }

        // All scored game IDs in chronological order
        $allGameIds = DB::table('point_results')
            ->join('games', 'games.id', '=', 'point_results.game_id')
            ->whereIn('point_results.user_id', $userIDs)
            ->select('games.id', 'games.game_date')
            ->groupBy('games.id', 'games.game_date')
            ->orderBy('games.game_date')
            ->orderBy('games.id')
            ->pluck('games.id')
            ->toArray();

        if (empty($allGameIds)) {
            return [];
        }

        $last10     = array_slice($allGameIds, -10);
        $last10Flip = array_flip($last10);

        // All result points keyed by game_id → user_id
        $allResults = DB::table('point_results')
            ->whereIn('user_id', $userIDs)
            ->selectRaw('user_id, game_id, full_points + COALESCE(streak_bonus, 0) as full_points')
            ->get()
            ->groupBy('game_id')
            ->map(fn ($rows) => $rows->keyBy('user_id'));

        $standingTotals = DB::table('point_standings')
            ->whereIn('user_id', $userIDs)
            ->selectRaw('user_id, SUM(
                IFNULL(group_position_points,0) + IFNULL(last32_points,0)
                + IFNULL(last16_points,0) + IFNULL(quarterfinal_points,0)
                + IFNULL(semifinal_points,0) + IFNULL(final_points,0)
            ) as total_points')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $survivalTotals = DB::table('point_survivals')
            ->whereIn('user_id', $userIDs)
            ->selectRaw('user_id, SUM(survival_points) as total')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $cumResultPts = array_fill_keys($userIDs, 0.0);
        $history      = [];
        $gameIdx      = 0;

        foreach ($allGameIds as $gameId) {
            $gameByUser = $allResults->get($gameId, collect());

            foreach ($userIDs as $uid) {
                $cumResultPts[$uid] += (float) ($gameByUser->get($uid)?->full_points ?? 0);
            }

            if (!isset($last10Flip[$gameId])) {
                continue;
            }

            $gameIdx++;

            $totals = [];
            foreach ($userIDs as $uid) {
                $stPts  = (float) ($standingTotals->get($uid)?->total_points ?? 0);
                $surPts = (float) ($survivalTotals->get($uid)?->total        ?? 0);
                $totals[$uid] = round($cumResultPts[$uid] + $stPts + $surPts, 1);
            }

            arsort($totals);
            $ranks = [];
            $rank  = 1;
            foreach ($totals as $uid => $_) {
                $ranks[$uid] = $rank++;
            }

            foreach ($userIDs as $uid) {
                $history[$uid][] = [
                    'game_idx'          => $gameIdx,
                    'game_points'       => round((float) ($gameByUser->get($uid)?->full_points ?? 0), 1),
                    'cumulative_points' => $totals[$uid],
                    'rank'              => $ranks[$uid],
                ];
            }
        }

        return $history;
    }

    public function getRankHistory(int $leagueID, int $userID): array
    {
        $guest = session('guest', 0);

        $userIDs = DB::table('league_members')
            ->where('league_id', $leagueID)
            ->where('is_guest', '<=', $guest)
            ->pluck('user_id')
            ->toArray();

        if (empty($userIDs) || !in_array($userID, $userIDs)) {
            return [];
        }

        $gameIDs = DB::table('games')
            ->whereNotNull('home_team_score')
            ->whereNotNull('away_team_score')
            ->orderBy('game_date')
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        if (empty($gameIDs)) {
            return [];
        }

        $rows = DB::table('point_results')
            ->whereIn('user_id', $userIDs)
            ->whereIn('game_id', $gameIDs)
            ->selectRaw('user_id, game_id, full_points + COALESCE(streak_bonus, 0) as full_points')
            ->get();

        $pointsMap = [];
        foreach ($rows as $row) {
            $pointsMap[$row->user_id][$row->game_id] = (float) $row->full_points;
        }

        $totals = array_fill_keys($userIDs, 0.0);
        $ranks  = [];

        foreach ($gameIDs as $gameID) {
            foreach ($userIDs as $uid) {
                $totals[$uid] += $pointsMap[$uid][$gameID] ?? 0.0;
            }
            $myTotal = $totals[$userID];
            $higher  = array_filter($totals, fn($t) => $t > $myTotal);
            $rank    = count(array_unique($higher)) + 1;
            $ranks[] = $rank;
        }

        return $ranks;
    }

    public function getPredictionStandingsUserPoints($userID){
        $PredictionStandingsUserPoints = DB::select('SELECT
                                        t.id,
                                        t.team,
                                        SUM(IFNULL(pos.group_position_points,0)) AS group_position_points,
                                        SUM(IFNULL(pos.last32_points,0))         AS last32_points,
                                        SUM(IFNULL(pos.last16_points,0))         AS last16_points,
                                        SUM(IFNULL(pos.quarterfinal_points,0))   AS quarterfinal_points,
                                        SUM(IFNULL(pos.semifinal_points,0))      AS semifinal_points,
                                        SUM(IFNULL(pos.final_points,0))          AS final_points
                                        FROM point_standings AS pos
                                        JOIN teams AS t on t.id=pos.team_id
                                        WHERE pos.user_id = ?
                                        GROUP BY t.team, t.id
                                        ORDER BY t.id',
            [$userID]);

        return $PredictionStandingsUserPoints;
    }



}
