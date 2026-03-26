<?php

namespace App\Http\Controllers\Audit;

use App\AuditLogin;
use App\Http\Controllers\Controller;


class AuditLoginsController extends Controller
{
    public function insertAuditLogin($userID) {
        $auditLogin = new AuditLogin();
        $auditLogin->userID = $userID;
        $auditLogin->save();
    }

}
