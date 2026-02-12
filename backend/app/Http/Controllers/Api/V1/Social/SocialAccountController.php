<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Social;

use App\Data\Social\ConnectAccountData;
use App\Data\Social\SocialAccountData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Social\ConnectAccountRequest;
use App\Models\Social\SocialAccount;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Social\SocialAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SocialAccountController extends Controller
{
    public function __construct(
        private readonly SocialAccountService $socialAccountService,
    ) {}

    /**
     * List social accounts for a workspace.
     */
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Check if user has access to this workspace
        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $filters = [
            'status' => $request->query('status'),
            'platform' => $request->query('platform'),
            'connected' => $request->boolean('connected'),
            'per_page' => $request->query('per_page', 15),
        ];

        $accounts = $this->socialAccountService->listForWorkspace($workspace, $filters);

        // Transform paginated data
        $transformedItems = collect($accounts->items())->map(
            fn (SocialAccount $account) => SocialAccountData::fromModel($account)->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Social accounts retrieved successfully',
            'data' => $transformedItems,
            'meta' => [
                'current_page' => $accounts->currentPage(),
                'last_page' => $accounts->lastPage(),
                'per_page' => $accounts->perPage(),
                'total' => $accounts->total(),
                'from' => $accounts->firstItem(),
                'to' => $accounts->lastItem(),
            ],
            'links' => [
                'first' => $accounts->url(1),
                'last' => $accounts->url($accounts->lastPage()),
                'prev' => $accounts->previousPageUrl(),
                'next' => $accounts->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get a single social account.
     */
    public function show(Request $request, Workspace $workspace, SocialAccount $socialAccount): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Verify social account belongs to this workspace
        if ($socialAccount->workspace_id !== $workspace->id) {
            return $this->notFound('Social account not found');
        }

        // Check if user has access to this workspace
        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        return $this->success(
            SocialAccountData::fromModel($socialAccount)->toArray(),
            'Social account retrieved successfully'
        );
    }

    /**
     * Connect a new social account to the workspace.
     */
    public function store(ConnectAccountRequest $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = ConnectAccountData::from($request->validated());

        $account = $this->socialAccountService->connect($workspace, $user, $data);

        return $this->created(
            SocialAccountData::fromModel($account)->toArray(),
            'Social account connected successfully'
        );
    }

    /**
     * Disconnect (delete) a social account.
     */
    public function destroy(Request $request, Workspace $workspace, SocialAccount $socialAccount): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Verify social account belongs to this workspace
        if ($socialAccount->workspace_id !== $workspace->id) {
            return $this->notFound('Social account not found');
        }

        // Check if user can manage social accounts
        try {
            $this->socialAccountService->validateUserCanManageSocialAccounts($user, $workspace);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->forbidden($e->getMessage());
        }

        $this->socialAccountService->disconnect($socialAccount);

        return $this->success(null, 'Social account disconnected successfully');
    }

    /**
     * Refresh tokens for a social account.
     */
    public function refresh(Request $request, Workspace $workspace, SocialAccount $socialAccount): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Verify social account belongs to this workspace
        if ($socialAccount->workspace_id !== $workspace->id) {
            return $this->notFound('Social account not found');
        }

        // Check if user can manage social accounts
        try {
            $this->socialAccountService->validateUserCanManageSocialAccounts($user, $workspace);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->forbidden($e->getMessage());
        }

        try {
            $refreshedAccount = $this->socialAccountService->refresh($socialAccount);

            return $this->success(
                SocialAccountData::fromModel($refreshedAccount)->toArray(),
                'Social account tokens refreshed successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Get health status for all social accounts in a workspace.
     */
    public function health(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Check if user has access to this workspace
        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $healthStatus = $this->socialAccountService->getHealthStatus($workspace);

        return $this->success(
            $healthStatus->toArray(),
            'Health status retrieved successfully'
        );
    }
}
