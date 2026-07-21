<?php

namespace App\Http\Middleware;

use App\Models\UserSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $userID = session('userID');

        if (! $userID || ! UserSetting::where('user_id', $userID)->where('admin', '>', 0)->exists()) {
            return redirect('/');
        }

        return $next($request);
    }
}
