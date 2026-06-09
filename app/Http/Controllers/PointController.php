<?php

namespace App\Http\Controllers;

use App\Models\PointResult;
use App\Models\PointSurvival;
use Illuminate\Support\Facades\DB;

class PointController extends Controller
{

    public function getAllUserPoints($groupID){
        $users=DB::table('users')->join('user_groups','users.id','=','user_groups.user_id')->where('user_groups.group_id','=',$groupID)->where('user_groups.guest','<=',session('guest'))->select('users.id','users.username','users.name','users.surname','user_groups.fee as user_fee')->get();

        $pointStandingController = app(PointStandingController::class);
        $pointsResultController = app(PointResultController::class);
        $pointSurvivalController = new PointSurvivalController();

        foreach ($users as $user){
            $userGamePoints = array_sum(array_column($pointsResultController->getUserProfilePoints($user->id),'full_points'));
            $userGameBingo = array_sum(array_column($pointsResultController->getUserProfilePoints($user->id),'bingo_points'));
            $gameCount = count($pointsResultController->getUserProfilePoints($user->id));
            $standingPoints = $pointStandingController->getStandingsUserPoints($user->id);
            $survivalPoints = $pointSurvivalController->getPredictionSurvivalUserPoints($user->id);


            $userAllPoints[] = [
                'userID'            => $user->id,
                'username'          => $user->username,
                'name'              => $user->name,
                'surname'           => $user->surname,
                'userFee'           => $user->user_fee,
                'userGamePoints'    => round((($userGamePoints=="")?0:$userGamePoints),1),
                'userGameBingo'     => (($userGameBingo=="")?0:$userGameBingo),
                'averagePoints'     => (($gameCount==0)?0:round($userGamePoints/$gameCount,1)),
                'standingPoints'    => $standingPoints,
                'survivalPoints'    => (($survivalPoints=="")?0:$survivalPoints)
            ];
        }

        if (!empty($userAllPoints)) {
            usort($userAllPoints, function ($a, $b) {
                return $b['userGamePoints'] + $b['standingPoints']->total_points + $b['survivalPoints'] <=> $a['userGamePoints'] + $a['standingPoints']->total_points + $a['survivalPoints'];
            });
        }
        else {
            $points = [];
        }

        return $userAllPoints;
    }


    /**
     * @deprecated No longer used — Praėjusio turo lyderiai section removed from main view.
     */
    public function getPointEventTotal($eventID, $groupID){
        $users=DB::table('users')->join('user_groups','users.id','=','user_groups.user_id')->where('user_groups.group_id','=',$groupID)->where('user_groups.guest','<=',session('guest'))->select('users.id','users.username')->get();

        foreach ($users as $user){
            $pointResultUserEvent = $this->getPointPredictionUserEvent($user->id,$eventID);
            $pointSurvivalUserEvent = $this->getPointSurvivalUserEvent($user->id,$eventID);

            $predictionGamesRoundPoints[] = [
                'userID'            => $user->id,
                'username'          => $user->username,
                'pointResult'       => $pointResultUserEvent,
                'pointSurvival'     => $pointSurvivalUserEvent
            ];
        }
        if (isset($predictionGamesRoundPoints)) {
            usort($predictionGamesRoundPoints, function ($a, $b) {
                return $b['pointResult']->full_points + $b['pointSurvival'] <=> $a['pointResult']->full_points + $a['pointSurvival'];
            });
        }

        return $predictionGamesRoundPoints;
    }

    public function getPointPredictionUserEvent($userID, $eventID){
        $points = DB::table('events')
            ->join('games','events.id','=','games.event_id')
            ->join('point_results','games.id','=','point_results.game_id')
            ->where('point_results.user_id','=',$userID)
            ->where('events.id','=',$eventID)
            ->selectRaw('
        IFNULL(ROUND(SUM(full_points), 1), 0) AS full_points,
        IFNULL(ROUND(AVG(full_points), 1), 0) AS avg_points,
        IFNULL(SUM(CASE WHEN winner_points > 0 THEN 1 ELSE 0 END), 0) AS correct_guess
    ')->first();
        return $points;
    }

    public function getPointSurvivalUserEvent($userID, $eventID){
        $points = PointSurvival::where('user_id',$userID)->where('event_id',$eventID)->first();
        return $points->survival_points ?? 0;
    }
    public function getAllUsersGameHistory(int $groupID): array
    {
        $userIDs = DB::table('users')
            ->join('user_groups', 'users.id', '=', 'user_groups.user_id')
            ->where('user_groups.group_id', '=', $groupID)
            ->where('user_groups.guest', '<=', session('guest'))
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
            ->select('user_id', 'game_id', 'full_points')
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

    public function getRankHistory(int $groupID, int $userID): array
    {
        $guest = session('guest', 0);

        $userIDs = DB::table('user_groups')
            ->where('group_id', $groupID)
            ->where('guest', '<=', $guest)
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
            ->select('user_id', 'game_id', 'full_points')
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
                                         t.team
                                          ,SUM(IFNULL(pos.group_position_points,0)) AS group_position_points
                                            ,SUM(IFNULL(pos.last16_points,0)) AS last16_points
                                            ,SUM(IFNULL(pos.quarterfinal_points,0)) AS quarterfinal_points
                                            ,SUM(IFNULL(pos.final_points,0)) AS final_points
                                          FROM point_standings AS pos
                                                JOIN teams AS t on t.id=pos.team_id
                                          WHERE
                                                pos.user_id = ?
                                          GROUP BY t.team, t.id
                                          ORDER BY t.id',
            [$userID]);

        return $PredictionStandingsUserPoints;
    }



}
