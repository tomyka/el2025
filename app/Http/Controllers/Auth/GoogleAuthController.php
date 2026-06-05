<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PostRegisterController;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('/')->with('error', 'Google prisijungimas nepavyko. Bandykite dar kartą.');
        }

        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            Auth::login($user);
            return redirect()->route('main');
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            $user->update(['google_id' => $googleUser->getId()]);
            Auth::login($user);
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
        return redirect()->route('main');
    }
}
