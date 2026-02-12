<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Data\User\UpdateProfileData;
use App\Data\User\UserData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\User\DeleteAccountRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Requests\User\UpdateSettingsRequest;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * Get current user profile.
     */
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user = $this->userService->getProfile($user);

        return $this->success(UserData::fromModel($user)->toArray(), 'User profile retrieved');
    }

    /**
     * Update current user profile.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = UpdateProfileData::from($request->validated());

        $user = $this->userService->updateProfile($user, $data);

        return $this->success(UserData::fromModel($user)->toArray(), 'Profile updated successfully');
    }

    /**
     * Update current user settings.
     */
    public function updateSettings(UpdateSettingsRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array<string, mixed> $settings */
        $settings = $request->validated('settings');

        $user = $this->userService->updateSettings($user, $settings);

        return $this->success(['settings' => $user->settings], 'Settings updated successfully');
    }

    /**
     * Delete current user account.
     */
    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->userService->deleteAccount($user, $request->validated('password'));

        return $this->success(null, 'Account deleted successfully');
    }
}
