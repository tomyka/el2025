<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserGroup;
use App\Models\UserSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Factory;

class UserProfileController extends Controller
{
    public function getUserProfile () {
        $user = User::where('id',session('userID'))->first();
        return view('userProfile')->with('user',$user);
    }
    public function updateUserProfile (Request $request, Factory $validator) {

            $user = User::find($request->input('userID'));
            $user->username = $request->input('username');
            $user->name = $request->input('name');
            $user->surname = $request->input('surname');
            $user->email = $request->input('email');
            $user->save();

        return view('userProfile')->with('user',$user);
   }

}
