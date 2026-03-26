<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    /**
     * Get the authenticated user's profile.
     */
    public function getProfile(User $user): User
    {
        return $user;
    }

    /**
     * Update user profile information.
     *
     * @throws ValidationException
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->fill($data);
        $user->save();

        return $user;
    }

    /**
     * Delete user account with password verification.
     *
     * @throws ValidationException
     */
    public function deleteAccount(User $user, string $password): void
    {
        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'The password is incorrect.',
            ]);
        }

        // Reload fresh instance to ensure proper deletion
        $user = User::find($user->id);
        $user->delete();
    }
}
