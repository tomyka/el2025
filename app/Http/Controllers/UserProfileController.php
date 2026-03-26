<?php

namespace App\Http\Controllers;

use App\Services\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserProfileController extends Controller
{
    public function __construct(private ProfileService $profileService) {}

    public function getUserProfile() {
        $user = $this->profileService->getProfile(Auth::user());
        return view('userProfile')->with('user', $user);
    }

    public function updateUserProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        $this->profileService->updateProfile($user, $request->only(['username', 'name', 'surname', 'email']));

        return redirect('/profile')->with('status', 'profile-updated');
    }

    public function destroy(Request $request)
    {
        try {
            $this->profileService->deleteAccount(Auth::user(), $request->input('password'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors(), 'userDeletion');
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
