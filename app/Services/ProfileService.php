<?php

namespace App\Services;

use App\Models\LeagueMember;
use App\Models\PointResult;
use App\Models\PointStanding;
use App\Models\PointSurvival;
use App\Models\PredictionResult;
use App\Models\PredictionStanding;
use App\Models\PredictionSurvival;
use App\Models\User;
use App\Models\UserSetting;
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
    public function deleteAccount(User $user, ?string $password): void
    {
        if ($user->password !== null && ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'The password is incorrect.',
            ]);
        }

        $id = $user->id;

        LeagueMember::where('user_id', $id)->delete();
        PointResult::where('user_id', $id)->delete();
        PointStanding::where('user_id', $id)->delete();
        PointSurvival::where('user_id', $id)->delete();
        UserSetting::where('user_id', $id)->delete();
        PredictionSurvival::where('user_id', $id)->delete();
        PredictionResult::where('user_id', $id)->delete();
        PredictionStanding::where('user_id', $id)->delete();

        User::where('id', $id)->delete();
    }
}
