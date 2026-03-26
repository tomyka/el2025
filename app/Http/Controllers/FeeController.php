<?php

namespace App\Http\Controllers;

use App\Models\UserGroup;
use DB;
use App\Models\Group;

class FeeController extends Controller
{
    public function getGroupDetails ()
    {
        $groupDetails = Group::where('id',session('groupID'))->select('fee','fee_description','reward_ratio','reward_description')->firstOrFail();
        return $groupDetails;
    }


    public function getUserDetails ()
    {
        $userDetails = DB::select('SELECT
                                     COUNT(u.id) AS users
                                    ,SUM(CASE WHEN Fee!=0 THEN 1 ELSE 0 END) AS usersActive
                                   FROM users AS u
                                    JOIN user_groups AS ug ON u.id=ug.user_id
                                   WHERE ug.group_id='.session('groupID'). ' AND ug.guest=0');
        return $userDetails[0];
    }

    public function getFund ()
    {
        $group = Group::where('id',session('groupID'))->select('fee','reward_ratio')->firstOrFail();

        $userCount = UserGroup::where('group_id',session('groupID'))->where('guest',0)->count();

            $fund = $group->fee*$group->reward_ratio*$userCount;
            return $fund;
    }

    public function getFundCollected ()
    {
        $groupDetails = DB::select('SELECT
                                   SUM(ug.fee) AS fee
                                  ,MAX(g.reward_ratio) AS reward_ratio
                                FROM `groups` AS g
                                  JOIN user_groups AS ug ON g.id=ug.group_id
                                WHERE g.id=' . session('groupID'));


        $fundCollected = $groupDetails[0]->fee*$groupDetails[0]->reward_ratio;
        return $fundCollected;
    }
}
