<?php

namespace App\Http\Controllers;

use App\Models\AuditLogin;

class AuditLoginsController extends Controller
{
    public function insertAuditLogin(int $userID, string $ipAddress, string $loginMethod): void
    {
        $auditLogin = new AuditLogin;
        $auditLogin->user_id = $userID;
        $auditLogin->ip_address = $ipAddress;
        $auditLogin->login_method = $loginMethod;
        $auditLogin->save();
    }
}
