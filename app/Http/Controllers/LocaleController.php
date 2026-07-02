<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request)
    {
        $request->validate(['locale' => 'required|in:lt,en']);

        $locale = $request->input('locale');

        if ($request->user()) {
            UserSetting::where('user_id', $request->user()->id)
                ->update(['locale' => $locale]);
        }

        session(['locale' => $locale]);

        return redirect()->back();
    }
}
