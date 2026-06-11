<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Controllers\PostRegisterController;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    use ChecksRegistrationDeadline;

    public function create(): View|RedirectResponse
    {
        if (!$this->registrationIsOpen()) {
            return redirect()->route('main');
        }

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        if (!$this->registrationIsOpen()) {
            return redirect()->route('main');
        }

        // Honeypot: real users never fill this field; bots typically do
        if ($request->filled('website')) {
            return redirect()->route('main');
        }

        $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'name'     => ['required', 'string', 'max:255'],
            'surname'  => ['nullable', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'username' => $request->username,
            'name'     => $request->name,
            'surname'  => $request->surname ?? '',
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        $postRegisterController = new PostRegisterController();
        $postRegisterController->postRegisterActions($user->id);

        return redirect(route('main', absolute: false));
    }
}
