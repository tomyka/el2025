<?php

namespace App\Http\Controllers;

use App\Models\League;
use Illuminate\Support\Facades\DB;

class FeeController extends Controller
{
    public function getGroupDetails()
    {
        return League::where('id', session('leagueID'))
            ->select('base_fee', 'penalty_step', 'reward_description')
            ->firstOrFail();
    }

    public function getUserDetails()
    {
        $userDetails = DB::select('SELECT
                                     COUNT(u.id) AS users
                                    ,SUM(CASE WHEN lm.is_guest = 0 THEN 1 ELSE 0 END) AS usersActive
                                   FROM users AS u
                                    JOIN league_members AS lm ON u.id = lm.user_id
                                   WHERE lm.league_id = ? AND lm.is_guest = 0',
            [session('leagueID')]);

        return $userDetails[0];
    }

    public function getFund()
    {
        $league = League::where('id', session('leagueID'))
            ->select('base_fee')
            ->firstOrFail();

        $userCount = DB::table('league_members')
            ->where('league_id', session('leagueID'))
            ->where('is_guest', 0)
            ->count();

        return $league->base_fee * $userCount;
    }

    public function getFundCollected()
    {
        return $this->getFund();
    }
}
