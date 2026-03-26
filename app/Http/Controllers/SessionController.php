<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use App\Models\UserGroup;
use App\Models\Game;
use App\Models\Setting;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use DateTime;


class SessionController extends Controller
{
    public function setSession($user) {
        date_default_timezone_set('Europe/Vilnius');



        if (Game::count()>0) {
            $event = DB::table('games')->join('events','games.event_id','=','events.id')->select('events.id','events.event_survival','events.rate')->whereNull('games.home_team_score')->first();
            if (empty($event)){
                $eventID= 0;
                $eventSurvival = 0;
                $eventRate = 0;
            }
            else{
                $eventID = $event->id;
                $eventSurvival = $event->event_survival;
                $eventRate = $event->rate;
            }


            $game = Game::firstOrFail();
            $firstGameDate = new DateTime($game->game_date);
            $disabled = "";
            if (strtotime('-0 day', $firstGameDate->getTimestamp()) < time()) {
                $disabled = "disabled";
            }
        }
        else {
            $eventID=0;
            $eventSurvival=0;
            $eventRate = 0;
            $disabled="";
        }

        $survivalGame = Setting::where('setting','survivalGame')->first();
        $timeDifference = Setting::where('setting','timeDifference')->first();
        $userSettings = UserSetting::where('user_id',$user->id)->firstOrFail();
        $userGroup = UserGroup::where('user_id',$user->id)->where('active',true)->firstOrFail();



        Session::put('active',$user->active);
        Session::put('eventID', $eventID);
        Session::put('eventSurvival', $eventSurvival);
        Session::put('eventRate', $eventRate);
        Session::put('disabled', $disabled);
        Session::put('userID',$user->id);
        Session::put('resultAmount',$userSettings->result_amount);
        Session::put('groupID',$userGroup->group_id);
        Session::put('admin',$userSettings->admin);
        Session::put('fee',$userGroup->fee);
        Session::put('guest',$userGroup->guest);
        Session::put('survivalGame',$survivalGame->value);
        Session::put('timeDifference',$timeDifference->value);

    }
}
