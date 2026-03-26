<?php

namespace App\Http\Controllers;

use DB;

class RulesController extends Controller
{
    public function getRulesDetails ()
    {
        if (session('userID')!='') {
            $feeController = new FeeController();
            return view('rules')->with('groupDetails',$feeController->getGroupDetails())->with('userDetails',$feeController->getUserDetails())->with('fund',$feeController->getFund())->with('fundCollected',$feeController->getFundCollected());
        }
        else
            return view('rules');
    }
}
