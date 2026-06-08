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
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getAllUsers(): \Illuminate\View\View
    {
        $userGroups = UserGroup::where('group_id', session('groupID'))
            ->where('guest', 0)
            ->with('user')
            ->get();

        return view('users', ['userGroups' => $userGroups]);
    }

    public function getAllUsersFull(): \Illuminate\View\View
    {
        $users = json_decode(User::with('userSetting')->orderBy('id')->get());

        return view('admin.users', ['users' => $users]);
    }

    public function updateUser(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['admin' => 'required|integer|in:0,1,5,9']);

        $userSetting        = UserSetting::where('user_id', $request->input('userID'))->firstOrFail();
        $userSetting->admin = (int) $request->input('admin');
        $userSetting->save();

        return redirect()->route('admin.users')
            ->with('info', 'Vartotojo ' . $request->input('username') . ' duomenys buvo atnaujinti.');
    }

    public function deleteUser(Request $request): \Illuminate\Http\RedirectResponse
    {
        if (session('admin') < 9) {
            return redirect()->route('admin.users')->with('error', 'Insufficient permissions to delete users.');
        }

        $userID = (int) $request->input('userID');

        if ($userID === (int) session('userID')) {
            return redirect()->route('admin.users')->with('error', 'Cannot delete your own account.');
        }

        UserGroup::where('user_id', $userID)->delete();
        PointResult::where('user_id', $userID)->delete();
        PointStanding::where('user_id', $userID)->delete();
        PointSurvival::where('user_id', $userID)->delete();
        UserSetting::where('user_id', $userID)->delete();
        PredictionSurvival::where('user_id', $userID)->delete();
        PredictionResult::where('user_id', $userID)->delete();
        PredictionStanding::where('user_id', $userID)->delete();
        User::where('id', $userID)->delete();

        return redirect()->route('admin.users')
            ->with('info', 'User ' . $userID . ' deleted');
    }
}
