<?php

namespace App\Http\Controllers;

use App\Models\AuditLogin;
use App\Models\AuditPredictionGame;
use App\Models\LeagueMember;
use App\Models\PointResult;
use App\Models\PointStanding;
use App\Models\PointSurvival;
use App\Models\PredictionResult;
use App\Models\PredictionStanding;
use App\Models\PredictionSurvival;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function getAllUsers(): View
    {
        $userGroups = LeagueMember::where('league_id', session('leagueID'))
            ->where('is_guest', 0)
            ->with('user')
            ->get();

        return view('users', ['userGroups' => $userGroups]);
    }

    public function getAllUsersFull(): View
    {
        $users = json_decode(User::with('userSetting')->orderBy('id')->get());

        return view('admin.users', ['users' => $users]);
    }

    public function toggleHidden(Request $request): JsonResponse
    {
        $userSetting = UserSetting::where('user_id', $request->input('userID'))->firstOrFail();
        $userSetting->active = ! $userSetting->active;
        $userSetting->save();

        return response()->json(['active' => $userSetting->active]);
    }

    public function updateUser(Request $request): RedirectResponse
    {
        $request->validate(['admin' => 'required|integer|in:0,1,5,8,9']);

        $userSetting = UserSetting::where('user_id', $request->input('userID'))->firstOrFail();
        $userSetting->admin = (int) $request->input('admin');
        $userSetting->save();

        return redirect()->route('admin.users')
            ->with('info', __('Vartotojo :username duomenys buvo atnaujinti.', ['username' => $request->input('username')]));
    }

    public function promoteSelf(): RedirectResponse
    {
        $userID = (int) session('userID');
        $userSetting = UserSetting::where('user_id', $userID)->firstOrFail();

        if ($userSetting->admin !== 8) {
            return redirect()->route('admin.index');
        }

        $userSetting->admin = 9;
        $userSetting->save();
        session(['admin' => 9]);

        return redirect()->route('admin.users')->with('info', 'Elevated to superadmin.');
    }

    public function deleteUser(Request $request): RedirectResponse
    {
        $userID = (int) $request->input('userID');

        if ($userID === (int) session('userID')) {
            return redirect()->route('admin.users')->with('error', 'Cannot delete your own account.');
        }

        AuditLogin::where('user_id', $userID)->delete();
        AuditPredictionGame::where('user_id', $userID)->delete();
        LeagueMember::where('user_id', $userID)->delete();
        PointResult::where('user_id', $userID)->delete();
        PointStanding::where('user_id', $userID)->delete();
        PointSurvival::where('user_id', $userID)->delete();
        UserSetting::where('user_id', $userID)->delete();
        PredictionSurvival::where('user_id', $userID)->delete();
        PredictionResult::where('user_id', $userID)->delete();
        PredictionStanding::where('user_id', $userID)->delete();
        User::where('id', $userID)->delete();

        return redirect()->route('admin.users')
            ->with('info', 'User '.$userID.' deleted');
    }
}
