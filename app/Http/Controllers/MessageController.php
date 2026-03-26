<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Group;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function getProfileMessages ($groupID)
    {
        $messages = Message::where('active', 1)->where('group_id',$groupID)->get();
        return $messages;
    }

    public function getMessageAll(){
        $messages = Message::all();
        $groups = Group::pluck('group','id');

        return view ('admin.messages')->with('messages',$messages)->with('groups',$groups);
    }

    public function insertMessage(Request $request){
        $message = new message();
        $message->message = $request->input('message');
        $message->active = (($request->input('active') == "on") ? 1 : 0);
        $message->group_id = $request->input('groupID');
        $message->save();
        return redirect()->route('admin.messages')->with('info','message inserted');
    }

    public function updateMessage(Request $request)
    {
        if ($request->has('update')) {
            $message = Message::find($request->input('messageID'));
            $message->message = $request->input('message');
            $message->active = (($request->input('active') == "on") ? 1 : 0);
            $message->group_id = $request->input('groupID');
            $message->save();
            return redirect()->route('admin.messages')->with('info','message '. $request->input('messageID') .' updated');
        }

        if ($request->has('delete')) {
            message::destroy($request->input('messageID'));
            return redirect()->route('admin.messages')->with('info','message '. $request->input('messageID') .' deleted');
        }

    }
}
