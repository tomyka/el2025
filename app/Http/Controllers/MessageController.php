<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\Message;
use App\Models\UserSetting;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function getProfileMessages($leagueID)
    {
        return Message::where('active', 1)->where('league_id', $leagueID)->get();
    }

    public function getMessageAll()
    {
        $messages = Message::all();
        $groups = League::pluck('name', 'id');

        return view('admin.messages')->with('messages', $messages)->with('groups', $groups);
    }

    public function insertMessage(Request $request)
    {
        $message = new Message();
        $message->message = $request->input('message');
        $message->active = (($request->input('active') == 'on') ? 1 : 0);
        $message->league_id = $request->input('leagueID');
        $message->save();
        return redirect()->route('admin.messages')->with('info', 'message inserted');
    }

    public function updateMessage(Request $request)
    {
        if ($request->has('update')) {
            $message = Message::find($request->input('messageID'));
            $message->message = $request->input('message');
            $message->active = (($request->input('active') == 'on') ? 1 : 0);
            $message->league_id = $request->input('leagueID');
            $message->save();
            return redirect()->route('admin.messages')->with('info', 'message ' . $request->input('messageID') . ' updated');
        }

        if ($request->has('delete')) {
            $userID = session('userID');
            if (!$userID || !UserSetting::where('user_id', $userID)->where('admin', '>=', 9)->exists()) {
                return redirect()->route('admin.messages')->with('error', 'Insufficient permissions to delete.');
            }
            Message::destroy($request->input('messageID'));
            return redirect()->route('admin.messages')->with('info', 'message ' . $request->input('messageID') . ' deleted');
        }
    }
}
