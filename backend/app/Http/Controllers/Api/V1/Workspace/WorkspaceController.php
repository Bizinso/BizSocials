<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Workspace;

use App\Data\Workspace\CreateWorkspaceData;
use App\Data\Workspace\UpdateWorkspaceData;
use App\Data\Workspace\WorkspaceData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Workspace\CreateWorkspaceRequest;
use App\Http\Requests\Workspace\UpdateWorkspaceRequest;
use App\Http\Requests\Workspace\UpdateWorkspaceSettingsRequest;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Models\Workspace\WorkspaceMembership;
use App\Services\Tenant\TenantService;
use App\Services\Workspace\WorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WorkspaceController extends Controller
{
    public function __construct(
        private readonly WorkspaceService $workspaceService,
        private readonly TenantService $tenantService,
    ) {}

    /**
     * List workspaces for the current tenant.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $tenant = $this->tenantService->getCurrent($user);

        $filters = [
            'status' => $request->query('status'),
            'search' => $request->query('search'),
            'include_archived' => $request->boolean('include_archived'),
            'per_page' => $request->query('per_page', 15),
        ];

        $workspaces = $this->workspaceService->listForTenant($tenant, $filters);

        // Transform paginated data with current user's role per workspace
        $transformedItems = collect($workspaces->items())->map(
            function (Workspace $workspace) use ($user) {
                $role = $workspace->getMemberRole($user->id);
                return WorkspaceData::fromModel($workspace, $role?->value)->toArray();
            }
        );

        return response()->json([
            'success' => true,
            'message' => 'Workspaces retrieved successfully',
            'data' => $transformedItems,
            'meta' => [
                'current_page' => $workspaces->currentPage(),
                'last_page' => $workspaces->lastPage(),
                'per_page' => $workspaces->perPage(),
                'total' => $workspaces->total(),
                'from' => $workspaces->firstItem(),
                'to' => $workspaces->lastItem(),
            ],
            'links' => [
                'first' => $workspaces->url(1),
                'last' => $workspaces->url($workspaces->lastPage()),
                'prev' => $workspaces->previousPageUrl(),
                'next' => $workspaces->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Create a new workspace.
     */
    public function store(CreateWorkspaceRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $tenant = $this->tenantService->getCurrent($user);
        $data = CreateWorkspaceData::from($request->validated());

        $workspace = $this->workspaceService->create($tenant, $user, $data);

        $role = $workspace->getMemberRole($user->id);

        return $this->created(
            WorkspaceData::fromModel($workspace, $role?->value)->toArray(),
            'Workspace created successfully'
        );
    }

    /**
     * Get a workspace.
     */
    public function show(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Check if user has access to this workspace
        $membership = WorkspaceMembership::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership === null && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        return $this->success(
            WorkspaceData::fromModel($workspace, $membership?->role->value)->toArray(),
            'Workspace retrieved successfully'
        );
    }

    /**
     * Update a workspace.
     */
    public function update(UpdateWorkspaceRequest $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        $data = UpdateWorkspaceData::from($request->validated());

        $updatedWorkspace = $this->workspaceService->update($workspace, $data);
        $role = $updatedWorkspace->getMemberRole($user->id);

        return $this->success(
            WorkspaceData::fromModel($updatedWorkspace, $role?->value)->toArray(),
            'Workspace updated successfully'
        );
    }

    /**
     * Update workspace settings.
     */
    public function updateSettings(UpdateWorkspaceSettingsRequest $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        $updatedWorkspace = $this->workspaceService->updateSettings($workspace, $request->validated());
        $role = $updatedWorkspace->getMemberRole($user->id);

        return $this->success(
            WorkspaceData::fromModel($updatedWorkspace, $role?->value)->toArray(),
            'Workspace settings updated successfully'
        );
    }

    /**
     * Delete a workspace.
     */
    public function destroy(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Check if user can delete workspace (must be owner)
        $membership = WorkspaceMembership::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership === null || !$membership->role->canDeleteWorkspace()) {
            return $this->forbidden('Only workspace owners can delete workspaces');
        }

        $this->workspaceService->delete($workspace);

        return $this->success(null, 'Workspace deleted successfully');
    }

    /**
     * Archive a workspace.
     */
    public function archive(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Check if user can manage workspace
        $membership = WorkspaceMembership::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership === null || !$membership->canManageWorkspace()) {
            return $this->forbidden('You do not have permission to archive this workspace');
        }

        $archivedWorkspace = $this->workspaceService->archive($workspace);

        return $this->success(
            WorkspaceData::fromModel($archivedWorkspace, $membership->role->value)->toArray(),
            'Workspace archived successfully'
        );
    }

    /**
     * Restore an archived workspace.
     */
    public function restore(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Check if user can manage workspace
        $membership = WorkspaceMembership::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership === null || !$membership->canManageWorkspace()) {
            return $this->forbidden('You do not have permission to restore this workspace');
        }

        $restoredWorkspace = $this->workspaceService->restore($workspace);

        return $this->success(
            WorkspaceData::fromModel($restoredWorkspace, $membership->role->value)->toArray(),
            'Workspace restored successfully'
        );
    }

    /**
     * Switch to a workspace (set as current workspace context).
     */
    public function switch(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $switchedWorkspace = $this->workspaceService->switchWorkspace($user, $workspace);
            $role = $switchedWorkspace->getMemberRole($user->id);

            return $this->success(
                WorkspaceData::fromModel($switchedWorkspace, $role?->value)->toArray(),
                'Switched to workspace successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), $e->getMessage());
        }
    }

    /**
     * Get the current workspace context.
     */
    public function current(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $currentWorkspace = $this->workspaceService->getCurrentWorkspace($user);

        if ($currentWorkspace === null) {
            return $this->success(null, 'No current workspace set');
        }

        $role = $currentWorkspace->getMemberRole($user->id);

        return $this->success(
            WorkspaceData::fromModel($currentWorkspace, $role?->value)->toArray(),
            'Current workspace retrieved successfully'
        );
    }
}
