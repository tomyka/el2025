<?php

namespace App\Http\Controllers;

use App\Models\UserGroup;
use App\Models\Group;
use Illuminate\Http\Request;

class UserGroupController extends Controller
{

    public function insertNewUserIntoGroup($user_id, $group_id, $active, $fee, $guest){
        $userGroup = new UserGroup();
        $userGroup->user_id =$user_id;
        $userGroup->group_id=$group_id;
        $userGroup->active = $active;
        $userGroup->guest = $guest;
        $userGroup->fee = $fee;
        $userGroup->save();
    }

    public function getUserGroup () {
        $userID = session('userID');
        $userGroups = UserGroup::where('user_id',$userID)->with('group')->get();
        $assignedGroups = UserGroup::where('user_id',$userID)->pluck('id')->all();
        $groups = Group::wherenotin('id',$assignedGroups)->pluck('group','id');
        return view('userGroups')->with('userGroups',$userGroups)->with('groups',$groups);
    }

    public function getUserGroupAll () {

        $userGroups = UserGroup::all();
        return view('admin.usergroups')->with('userGroups',$userGroups);
    }

    public function insertUserGroup(Request $request){
        $userGroup = new UserGroup();
        $userGroup->user_id = session('userID');
        $userGroup->group_id = $request->input('groupID');
        $userGroup->guest = (($request->input('guest') == "on") ? 1 : 0);
        $userGroup->save();
        return redirect()->route('userGroup')->with('info','Dalyvių grupė '.$request->input('group').' sukurta');
    }

    public function updateUserGroup(Request $request)
    {
        $userGroup = UserGroup::find($request->input('user_groupID'));
        $userGroup->active = (($request->input('active') == "on") ? 1 : 0);
        $userGroup->guest = (($request->input('guest') == "on") ? 1 : 0);
        $userGroup->save();
        return redirect()->route('userGroup')->with('info', 'Dalyvių grupė ' . $request->input('group') . ' atnaujinta');
    }

    public function deleteUserGroup(Request $request)
    {
        UserGroup::where('user_group_id',$request->input('user_group_id'))->delete();
        return redirect()->route('getUserGroup')->with('info','Dalyvių grupė '. $request->input('group') .' ištrinta');
    }
}
