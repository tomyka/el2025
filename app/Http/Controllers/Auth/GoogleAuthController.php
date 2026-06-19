<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\AuditLoginsController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PostRegisterController;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    use ChecksRegistrationDeadline;

    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function rememberAndRedirect(Request $request)
    {
        session(['remember_me' => $request->boolean('remember')]);
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('/')->with('error', 'Google prisijungimas nepavyko. Bandykite dar kartą.');
        }

        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            Auth::login($user);
            (new AuditLoginsController())->insertAuditLogin($user->id, $request->ip(), 'google');
            return redirect()->route('main');
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            $user->update(['google_id' => $googleUser->getId()]);
            Auth::login($user);
            (new AuditLoginsController())->insertAuditLogin($user->id, $request->ip(), 'google');
            return redirect()->route('main');
        }

        if (!$this->registrationIsOpen()) {
            return redirect()->route('main');
        }

        $nameParts = explode(' ', $googleUser->getName(), 2);
        $user = User::create([
            'google_id'         => $googleUser->getId(),
            'email'             => $googleUser->getEmail(),
            'email_verified_at' => now(),
            'username'          => strstr($googleUser->getEmail(), '@', true),
            'name'              => $nameParts[0],
            'surname'           => $nameParts[1] ?? '',
            'password'          => null,
        ]);

        event(new Registered($user));
        (new PostRegisterController())->postRegisterActions($user->id);

        Auth::login($user);
        (new AuditLoginsController())->insertAuditLogin($user->id, $request->ip(), 'google');
        return redirect()->route('main');
    }
}
