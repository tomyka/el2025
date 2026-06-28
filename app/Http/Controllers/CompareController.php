<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class CompareController extends Controller
{
    public function show(int $userID): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $leagueID = session('leagueID');
        $myID     = (int) session('userID');

        if ($userID === $myID) {
            return redirect()->route('main');
        }

        $pointController = app(PointController::class);
        $allPoints       = $pointController->getAllUserPoints($leagueID);

        $myData    = null;
        $myRank    = null;
        $theirData = null;
        $theirRank = null;

        foreach ($allPoints as $rank => $p) {
            if ($p['userID'] === $myID) {
                $myData = $p;
                $myRank = $rank + 1;
            }
            if ($p['userID'] === $userID) {
                $theirData = $p;
                $theirRank = $rank + 1;
            }
        }

        if (!$theirData) {
            abort(404);
        }

        $rounds = $this->getPerRoundComparison($myID, $userID);

        if (request()->ajax()) {
            return view('compare._card', compact('myData', 'myRank', 'theirData', 'theirRank', 'rounds'));
        }

        return view('compare.show', compact('myData', 'myRank', 'theirData', 'theirRank', 'rounds'));
    }

    private function getPerRoundComparison(int $myID, int $theirID): array
    {
        $rows = DB::table('point_results as pr')
            ->join('games as g', 'pr.game_id', '=', 'g.id')
            ->join('events as e', 'g.event_id', '=', 'e.id')
            ->whereIn('pr.user_id', [$myID, $theirID])
            ->selectRaw('e.id as event_id, e.event as event_name, pr.user_id,
                         SUM(pr.full_points + COALESCE(pr.streak_bonus, 0)) as pts')
            ->groupBy('e.id', 'e.event', 'pr.user_id')
            ->orderBy('e.id')
            ->get();

        $events = [];
        foreach ($rows as $row) {
            $eid = $row->event_id;
            if (!isset($events[$eid])) {
                $events[$eid] = ['name' => $row->event_name, 'my' => 0.0, 'their' => 0.0];
            }
            if ($row->user_id === $myID) {
                $events[$eid]['my'] = round((float) $row->pts, 1);
            } else {
                $events[$eid]['their'] = round((float) $row->pts, 1);
            }
        }

        return array_values($events);
    }
}
