<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\UserSetting;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function getEvent(){
        $events = Event::all();

        return view ('admin.events', ['events' =>$events]);
    }

    public function insertEvent(Request $request){

       $event = new Event();
       $event->event = $request->input('event');
       $event->event_day = $request->input('eventDay');
       $event->event_survival = (($request->input('eventSurvival') == "on") ? 1 : 0);
       $event->is_knockout    = (($request->input('isKnockout')    == "on") ? 1 : 0);
       $event->active = (($request->input('active') == "on") ? 1 : 0);
       $event->rate = $request->input('rate');
       $event->save();

        return redirect()->route('admin.events')->with('info','Event inserted');
    }

    public function updateEvent(Request $request)
    {
        if ($request->has('delete')) {
            $userID = session('userID');
            if (!$userID || !UserSetting::where('user_id', $userID)->where('admin', '>=', 9)->exists()) {
                return redirect()->route('admin.events')->with('error', 'Insufficient permissions to delete.');
            }
            Event::destroy($request->input('eventID'));
            return redirect()->route('admin.events')->with('info','Event '. $request->input('eventID') .' deleted');
        }

        $event = Event::find($request->input('eventID'));
        $event->event = $request->input('event');
        $event->event_day = $request->input('eventDay');
        $event->event_survival = (($request->input('eventSurvival') == "on") ? 1 : 0);
        $event->is_knockout    = (($request->input('isKnockout')    == "on") ? 1 : 0);
        $event->active = (($request->input('active') == "on") ? 1 : 0);
        $event->rate = $request->input('rate');
        $event->save();
        return redirect()->route('admin.events')->with('info','Event '. $request->input('eventID') .' updated');
    }
}
