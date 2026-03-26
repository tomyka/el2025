<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Factory;


class UserSettingController extends Controller
{
    public function getUserSettings () {
        $userID = (session('userID'));
        $userSettings = UserSetting::where('user_id',$userID)->with('user')->firstOrFail();
        return view('userSettings')->with('userSettings',$userSettings);
    }

    public function insertUserSettings($user_id){
        $userSettings = new UserSetting();
        $userSettings->user_id =$user_id;
        $userSettings->admin=(($user_id==1)?9:1);
        $userSettings->save();
    }

    public function updateUserSettings(Request $request, Factory $validator)
    {
        $validation = $validator->make($request->all(), [
            'resultAmount' => 'nullable|integer|max:128|min:1',
        ]);

        if ($validation->fails()) {
            return redirect()->back()->withErrors($validation);
        }

        if ($request->has('update')) {

            $userSettings = UserSetting::find($request->input('user_settingsID'));
            $userSettings->result_amount = $request->input('resultAmount');
            $userSettings->save();

            Session::put('resultAmount',$request->input('resultAmount'));

            return redirect()->route('userSettings')->with('info','Vartotojo nustatymai pakeisti');
        }



    }
}
