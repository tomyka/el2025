<?php

namespace App\Http\Controllers;

use App\Models\PointResult;
use App\Models\PointStanding;
use App\Models\PointSurvival;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\UserSetting;
use App\Models\PredictionResult;
use App\Models\PredictionStanding;
use App\Models\PredictionSurvival;
use App\Models\PredictionSurvivalTeam;
use Illuminate\Http\Request;
use Illuminate\Validation\Factory;

class UserController extends Controller
{
    public function getAllUsers () {
        $userGroups = UserGroup::where('group_id',session('groupID'))->where('guest',0)->with('user')->get();
        return view('users',['userGroups'=>$userGroups]);
    }
    public function getAllUsersFull () {
        $users = json_decode(User::with('userSetting')->orderBy('id')->get());
        return view('admin.users',['users'=>$users]);
    }

    public function updateUser(Request $request)
    {
    /*     $userProfile = UserGroup::find($request->input('user_groupID'));
        $userProfile->guest = (($request->input('guest') == "on") ? 1 : 0);
        $userProfile->fee = $request->input('fee');
        $userProfile->save();
*/
        $userSetting = UserSetting::find($request->input('userID'));
        $userSetting->admin = $request->input('admin');
        $userSetting->save();

        return redirect()->route('admin.users')->with('info', 'Vartotojo ' . $request->input('username') . ' duomenys buvo atnaujinti.');
    }

    public function deleteUser(Request $request)
    {

        userGroup::where('user_id',$request->input('userID'))->delete();
        PointResult::where('user_id',$request->input('userID'))->delete();
        PointStanding::where('user_id',$request->input('userID'))->delete();
        PointSurvival::where('user_id',$request->input('userID'))->delete();
        userSetting::where('user_id',$request->input('userID'))->delete();
        PredictionSurvival::where('user_id',$request->input('userID'))->delete();
        PredictionResult::where('user_id',$request->input('userID'))->delete();
        PredictionStanding::where('user_id',$request->input('userID'))->delete();
        User::where('id',$request->input('userID'))->delete();
        return redirect()->route('admin.users')->with('info','User '. $request->input('userID') .' deleted');
    }
    public function updatePassword ($currentPassword, $newPassword, $newPasswordConfirmation) {


    }
}
