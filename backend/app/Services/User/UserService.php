<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Data\User\UpdateProfileData;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class UserService extends BaseService
{
    /**
     * Get user profile with relationships.
     */
    public function getProfile(User $user): User
    {
        return $user->load(['tenant']);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(User $user, UpdateProfileData $data): User
    {
        $updateData = array_filter([
            'name' => $data->name,
            'timezone' => $data->timezone,
            'phone' => $data->phone,
        ], fn ($value) => $value !== null);

        // Handle job_title in settings
        if ($data->job_title !== null) {
            $user->setSetting('job_title', $data->job_title);
        }

        if (! empty($updateData)) {
            $user->update($updateData);
        }

        $this->log('Profile updated', ['user_id' => $user->id]);

        return $user->fresh() ?? $user;
    }

    /**
     * Update user settings.
     *
     * @param  array<string, mixed>  $settings
     */
    public function updateSettings(User $user, array $settings): User
    {
        $currentSettings = $user->settings ?? [];
        $user->settings = array_merge($currentSettings, $settings);
        $user->save();

        $this->log('Settings updated', ['user_id' => $user->id]);

        return $user;
    }

    /**
     * Delete user account.
     *
     * @throws ValidationException
     */
    public function deleteAccount(User $user, string $password): void
    {
        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The password is incorrect.'],
            ]);
        }

        // Revoke all tokens
        $user->tokens()->delete();

        // Soft delete the user
        $user->delete();

        $this->log('Account deleted', ['user_id' => $user->id]);
    }
}
