<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameOdds;
use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function getGroup(){
        $groups = Group::all();

        return view ('admin.groups', ['groups' =>$groups]);
    }

    public function insertGroup(Request $request){
        $group = new group();
        $group->group = $request->input('group');
        $group->fee = $request->input('fee');
        $group->fee_description = $request->input('feeDescription');
        $group->reward_ratio = $request->input('rewardRatio');
        $group->reward_description = $request->input('rewardDescription');
        $group->save();
        $groupID = $group->id;

        $games = Game::all();
        foreach($games as $game) {
            $gameOddsArray[]=[
                'group_id'=>$groupID
                ,'game_id'=>$game->id
            ];
        }
        if (!empty($gameOddsArray)){
            $gameOdds = new GameOdds();
            $gameOdds :: insert($gameOddsArray);
        }


        return redirect()->route('admin.groups')->with('info','New profile inserted');
    }

    public function updateGroup(Request $request)
    {
        if ($request->has('update')) {
            $group = Group::find($request->input('groupID'));
            $group->group = $request->input('group');
            $group->fee = $request->input('fee');
            $group->fee_description = $request->input('feeDescription');
            $group->reward_ratio = $request->input('rewardRatio');
            $group->reward_description = $request->input('rewardDescription');
            $group->save();
            return redirect()->route('admin.groups')->with('info','group '. $request->input('groupID') .' updated');
        }

        if ($request->has('delete')) {
            Group::destroy($request->input('groupID'));
            return redirect()->route('admin.groups')->with('info','group '. $request->input('groupID') .' deleted');
        }

    }

}
