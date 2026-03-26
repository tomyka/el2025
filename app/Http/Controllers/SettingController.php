<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function getSettingAll(){
        $settings = Setting::all();
        return view ('admin.settings')->with('settings',$settings);
    }

    public function insertSetting(Request $request){
        $setting = new setting();
        $setting->setting = $request->input('setting');
        $setting->value = $request->input('value');
        $setting->save();
        return redirect()->route('admin.settings')->with('info','setting inserted');
    }

    public function updateSetting(Request $request)
    {
        if ($request->has('update')) {
            $setting = Setting::find($request->input('settingID'));
            $setting->setting = $request->input('setting');
            $setting->value = $request->input('value');
            $setting->save();
            return redirect()->route('admin.settings')->with('info','Nustatymas '. $request->input('settingID') .' pakeistas');
        }

    }
}
