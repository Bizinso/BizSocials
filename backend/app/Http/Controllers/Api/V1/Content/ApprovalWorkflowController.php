<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Content\ApprovalWorkflow;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\ApprovalWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ApprovalWorkflowController extends Controller
{
    public function __construct(
        private readonly ApprovalWorkflowService $workflowService,
    ) {}

    /**
     * List all approval workflows for a workspace.
     */
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $workflows = $this->workflowService->list($workspace->id);

        return $this->paginated($workflows, 'Approval workflows retrieved successfully');
    }

    /**
     * Create a new approval workflow.
     */
    public function store(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'sometimes|boolean',
            'steps' => 'required|array|min:1',
            'steps.*.order' => 'required|integer|min:1',
            'steps.*.approver_user_ids' => 'required|array|min:1',
            'steps.*.approver_user_ids.*' => 'uuid',
            'steps.*.require_all' => 'required|boolean',
        ]);

        $workflow = $this->workflowService->create($workspace->id, $validated);

        return $this->created($workflow, 'Approval workflow created successfully');
    }

    /**
     * Show a single approval workflow.
     */
    public function show(Request $request, Workspace $workspace, ApprovalWorkflow $approvalWorkflow): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($approvalWorkflow->workspace_id !== $workspace->id) {
            return $this->notFound('Approval workflow not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        return $this->success($approvalWorkflow, 'Approval workflow retrieved successfully');
    }

    /**
     * Update an approval workflow.
     */
    public function update(Request $request, Workspace $workspace, ApprovalWorkflow $approvalWorkflow): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($approvalWorkflow->workspace_id !== $workspace->id) {
            return $this->notFound('Approval workflow not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
            'steps' => 'sometimes|array|min:1',
            'steps.*.order' => 'required_with:steps|integer|min:1',
            'steps.*.approver_user_ids' => 'required_with:steps|array|min:1',
            'steps.*.approver_user_ids.*' => 'uuid',
            'steps.*.require_all' => 'required_with:steps|boolean',
        ]);

        $workflow = $this->workflowService->update($approvalWorkflow, $validated);

        return $this->success($workflow, 'Approval workflow updated successfully');
    }

    /**
     * Delete an approval workflow.
     */
    public function destroy(Request $request, Workspace $workspace, ApprovalWorkflow $approvalWorkflow): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($approvalWorkflow->workspace_id !== $workspace->id) {
            return $this->notFound('Approval workflow not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $this->workflowService->delete($approvalWorkflow);

        return $this->success(null, 'Approval workflow deleted successfully');
    }

    /**
     * Set an approval workflow as the default for the workspace.
     */
    public function setDefault(Request $request, Workspace $workspace, ApprovalWorkflow $workflow): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($workflow->workspace_id !== $workspace->id) {
            return $this->notFound('Approval workflow not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $this->workflowService->setDefault($workflow);

        return $this->success($workflow->fresh(), 'Approval workflow set as default');
    }
}
