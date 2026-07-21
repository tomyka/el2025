<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\AuditLoginsController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request): View
    {
        // Remember which tournament a guest arrived here to join (e.g. from a
        // tournament's public page) so registration can join them to that
        // tournament's league instead of an unrelated default one.
        if ($request->filled('tournament')) {
            $request->session()->put('intended_tournament', $request->query('tournament'));
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        (new AuditLoginsController)->insertAuditLogin(
            Auth::id(),
            $request->ip(),
            'email'
        );

        return redirect()->intended(route('main', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
