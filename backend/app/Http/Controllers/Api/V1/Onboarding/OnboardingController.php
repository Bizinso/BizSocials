<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Onboarding;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Onboarding\SubmitOnboardingWorkspaceRequest;
use App\Http\Requests\Onboarding\SubmitOrganizationRequest;
use App\Models\User;
use App\Services\Onboarding\OnboardingService;
use Illuminate\Http\JsonResponse;

final class OnboardingController extends Controller
{
    public function __construct(
        private readonly OnboardingService $onboardingService,
    ) {}

    /**
     * Submit organization details during onboarding.
     */
    public function submitOrganization(SubmitOrganizationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tenant = $user->tenant;

        if (! $tenant) {
            return $this->error('No tenant associated with your account.', 403);
        }

        $profile = $this->onboardingService->submitOrganization(
            $tenant,
            $user,
            $request->validated(),
        );

        return $this->success([
            'profile' => $profile,
            'onboarding' => $tenant->onboarding->fresh(),
        ], 'Organization setup completed');
    }

    /**
     * Submit workspace creation during onboarding.
     */
    public function submitWorkspace(SubmitOnboardingWorkspaceRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tenant = $user->tenant;

        if (! $tenant) {
            return $this->error('No tenant associated with your account.', 403);
        }

        $workspace = $this->onboardingService->submitWorkspace(
            $tenant,
            $user,
            $request->validated(),
        );

        return $this->success([
            'workspace' => $workspace,
            'onboarding' => $tenant->onboarding->fresh(),
        ], 'Workspace created successfully');
    }
}
