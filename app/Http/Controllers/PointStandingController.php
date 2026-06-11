<?php

namespace App\Http\Controllers;

use App\Models\PointStanding;
use App\Models\PredictionStanding;
use App\Models\Team;
use App\Services\StandingScoringService;
use Illuminate\Support\Facades\DB;

class PointStandingController extends Controller
{
    public function __construct(private readonly StandingScoringService $scoring) {}

    public function getStandingsUserPoints(int $userID): object
    {
        $points = DB::table('point_standings')
            ->selectRaw('
                SUM(IFNULL(group_position_points,0))  AS group_position_points,
                SUM(IFNULL(last32_points,0))          AS last32_points,
                SUM(IFNULL(last16_points,0))          AS last16_points,
                SUM(IFNULL(quarterfinal_points,0))    AS quarterfinal_points,
                SUM(IFNULL(semifinal_points,0))       AS semifinal_points,
                SUM(IFNULL(final_points,0))           AS final_points,
                SUM(
                    IFNULL(group_position_points,0)
                    + IFNULL(last32_points,0)
                    + IFNULL(last16_points,0)
                    + IFNULL(quarterfinal_points,0)
                    + IFNULL(semifinal_points,0)
                    + IFNULL(final_points,0)
                ) AS total_points
            ')
            ->where('user_id', $userID)
            ->first();

        if (empty($points)) {
            $points                        = new \stdClass();
            $points->group_position_points = '0';
            $points->last32_points         = '0';
            $points->last16_points         = '0';
            $points->quarterfinal_points   = '0';
            $points->semifinal_points      = '0';
            $points->final_points          = '0';
            $points->total_points          = '0';
        }

        return $points;
    }

    public function getBulkUserStandingPoints(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $rows = DB::table('point_standings')
            ->selectRaw('
                user_id,
                SUM(IFNULL(group_position_points,0))  AS group_position_points,
                SUM(IFNULL(last32_points,0))          AS last32_points,
                SUM(IFNULL(last16_points,0))          AS last16_points,
                SUM(IFNULL(quarterfinal_points,0))    AS quarterfinal_points,
                SUM(IFNULL(semifinal_points,0))       AS semifinal_points,
                SUM(IFNULL(final_points,0))           AS final_points,
                SUM(
                    IFNULL(group_position_points,0) + IFNULL(last32_points,0)
                    + IFNULL(last16_points,0) + IFNULL(quarterfinal_points,0)
                    + IFNULL(semifinal_points,0) + IFNULL(final_points,0)
                ) AS total_points
            ')
            ->whereIn('user_id', $userIds)
            ->groupBy('user_id')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->user_id] = $row;
        }

        $zero                        = new \stdClass();
        $zero->group_position_points = '0';
        $zero->last32_points         = '0';
        $zero->last16_points         = '0';
        $zero->quarterfinal_points   = '0';
        $zero->semifinal_points      = '0';
        $zero->final_points          = '0';
        $zero->total_points          = '0';

        foreach ($userIds as $uid) {
            if (!isset($result[$uid])) {
                $result[$uid] = clone $zero;
            }
        }

        return $result;
    }

    public function updateStandingPoints(): \Illuminate\Http\RedirectResponse
    {
        PointStanding::truncate();

        $teams         = Team::all();
        $numberOfTeams = $teams->count();

        foreach ($teams as $team) {
            foreach (PredictionStanding::where('team_id', $team->id)->get() as $prediction) {
                $pointStanding                          = new PointStanding();
                $pointStanding->team_id                 = $team->id;
                $pointStanding->user_id                 = $prediction->user_id;
                $pointStanding->group_position_points   = $this->scoring->calculateGroupPositionPoints($team->group_position, $prediction->group_position, $numberOfTeams);
                $pointStanding->last32_points           = $this->scoring->calculateKnockoutPoints($team->last32, $prediction->last32, 40);
                $pointStanding->last16_points           = $this->scoring->calculateKnockoutPoints($team->last16, $prediction->last16, 60);
                $pointStanding->quarterfinal_points     = $this->scoring->calculateKnockoutPoints($team->quarterfinal, $prediction->quarterfinal, 90);
                $pointStanding->semifinal_points        = $this->scoring->calculateKnockoutPoints($team->semifinal, $prediction->semifinal, 120);
                $pointStanding->final_points            = $this->scoring->calculateFinalPoints($team->final, $prediction->final);
                $pointStanding->save();
            }
        }

        return redirect()->route('admin.teams');
    }
}
