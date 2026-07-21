<?php

namespace App\Http\Controllers;

use App\Models\PointStanding;
use App\Models\PredictionStanding;
use App\Models\Team;
use App\Services\StandingScoringService;
use Illuminate\Http\RedirectResponse;
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
            $points = new \stdClass;
            $points->group_position_points = '0';
            $points->last32_points = '0';
            $points->last16_points = '0';
            $points->quarterfinal_points = '0';
            $points->semifinal_points = '0';
            $points->final_points = '0';
            $points->total_points = '0';
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

        $zero = new \stdClass;
        $zero->group_position_points = '0';
        $zero->last32_points = '0';
        $zero->last16_points = '0';
        $zero->quarterfinal_points = '0';
        $zero->semifinal_points = '0';
        $zero->final_points = '0';
        $zero->total_points = '0';

        foreach ($userIds as $uid) {
            if (! isset($result[$uid])) {
                $result[$uid] = clone $zero;
            }
        }

        return $result;
    }

    public function updateStandingPoints(): RedirectResponse
    {
        PointStanding::truncate();

        $teams = Team::all();
        $allPreds = PredictionStanding::all()->groupBy('team_id');

        $active = [
            'last32' => $teams->contains(fn ($t) => $t->last32),
            'last16' => $teams->contains(fn ($t) => $t->last16),
            'quarterfinal' => $teams->contains(fn ($t) => $t->quarterfinal),
            'semifinal' => $teams->contains(fn ($t) => $t->semifinal),
            'final' => $teams->contains(fn ($t) => $t->final > 0),
        ];

        $stageBase = ['last32' => 3, 'last16' => 6, 'quarterfinal' => 9, 'semifinal' => 12];

        foreach ($teams as $team) {
            $teamPreds = $allPreds->get($team->id, collect());

            foreach ($teamPreds as $prediction) {
                $ps = new PointStanding;
                $ps->team_id = $team->id;
                $ps->user_id = $prediction->user_id;

                // Group position — odds apply only on exact match (base == 3)
                // Denominator: only users who actually submitted a group position prediction
                $gpBase = $this->scoring->calculateGroupPositionPoints($team->group_position, $prediction->group_position);
                $gpOdds = null;
                $gpTotal = $teamPreds->whereNotNull('group_position')->count();
                if ($gpBase === 3 && $gpTotal > 0 && $prediction->group_position !== null) {
                    $same = $teamPreds->where('group_position', $prediction->group_position)->count();
                    $gpOdds = $same > 0 ? round(log($gpTotal / $same, 2), 4) : 0.0;
                }
                $ps->group_position_points = $gpOdds > 0 ? round($gpBase * (1 + $gpOdds), 4) : $gpBase;
                $ps->group_position_odds = $gpOdds;

                // Knockout stages — odds apply on correct advancement
                // Denominator: only users who submitted a prediction for this stage (not null)
                foreach ($stageBase as $stage => $base) {
                    $ptCol = "{$stage}_points";
                    $odCol = "{$stage}_odds";
                    $stOdds = null;

                    if (! $active[$stage]) {
                        $ps->$ptCol = null;
                        $ps->$odCol = null;

                        continue;
                    }

                    $stBase = $this->scoring->calculateKnockoutPoints($team->$stage, $prediction->$stage, $base);
                    $stTotal = $teamPreds->whereNotNull($stage)->count();

                    if ($stBase > 0 && $stTotal > 0) {
                        $same = $teamPreds->where($stage, 1)->count();
                        $stOdds = $same > 0 ? round(log($stTotal / $same, 2), 4) : 0.0;
                    }

                    $ps->$ptCol = $stOdds > 0 ? round($stBase * (1 + $stOdds), 4) : $stBase;
                    $ps->$odCol = $stOdds;
                }

                // Final — odds apply on exact position match
                // Denominator: only users who submitted a final prediction (> 0)
                $finBase = $active['final'] ? $this->scoring->calculateFinalPoints($team->final, $prediction->final) : null;
                $finOdds = null;
                $finTotal = $teamPreds->where('final', '>', 0)->count();
                if ($finBase > 0 && $finTotal > 0 && $team->final > 0 && $prediction->final == $team->final) {
                    $same = $teamPreds->where('final', $prediction->final)->count();
                    $finOdds = $same > 0 ? round(log($finTotal / $same, 2), 4) : 0.0;
                }
                $ps->final_points = $finBase !== null
                    ? ($finOdds > 0 ? round($finBase * (1 + $finOdds), 4) : $finBase)
                    : null;
                $ps->final_odds = $finOdds;

                $ps->save();
            }
        }

        return redirect()->route('admin.teams');
    }
}
