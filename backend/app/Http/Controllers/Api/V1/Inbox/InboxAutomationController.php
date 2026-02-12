<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inbox;

use App\Enums\Inbox\InboxAutomationAction;
use App\Enums\Inbox\InboxAutomationTrigger;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Inbox\InboxAutomationRule;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Inbox\InboxAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class InboxAutomationController extends Controller
{
    public function __construct(
        private readonly InboxAutomationService $automationService,
    ) {}

    /**
     * List automation rules for a workspace.
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

        $filters = [
            'is_active' => $request->query('is_active'),
            'per_page' => $request->query('per_page', 20),
        ];

        $rules = $this->automationService->list($workspace, $filters);

        return $this->paginated($rules, 'Automation rules retrieved successfully');
    }

    /**
     * Create a new automation rule.
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
            'trigger_type' => ['required', 'string', Rule::in(InboxAutomationTrigger::values())],
            'trigger_conditions' => 'nullable|array',
            'action_type' => ['required', 'string', Rule::in(InboxAutomationAction::values())],
            'action_params' => 'nullable|array',
            'priority' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $rule = $this->automationService->create($workspace, $validated);

        return $this->created($rule->toArray(), 'Automation rule created successfully');
    }

    /**
     * Show a single automation rule.
     */
    public function show(Request $request, Workspace $workspace, InboxAutomationRule $automationRule): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($automationRule->workspace_id !== $workspace->id) {
            return $this->notFound('Automation rule not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        return $this->success($automationRule->toArray(), 'Automation rule retrieved successfully');
    }

    /**
     * Update an automation rule.
     */
    public function update(Request $request, Workspace $workspace, InboxAutomationRule $automationRule): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($automationRule->workspace_id !== $workspace->id) {
            return $this->notFound('Automation rule not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'trigger_type' => ['sometimes', 'string', Rule::in(InboxAutomationTrigger::values())],
            'trigger_conditions' => 'nullable|array',
            'action_type' => ['sometimes', 'string', Rule::in(InboxAutomationAction::values())],
            'action_params' => 'nullable|array',
            'priority' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $rule = $this->automationService->update($automationRule, $validated);

        return $this->success($rule->toArray(), 'Automation rule updated successfully');
    }

    /**
     * Delete an automation rule.
     */
    public function destroy(Request $request, Workspace $workspace, InboxAutomationRule $automationRule): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($automationRule->workspace_id !== $workspace->id) {
            return $this->notFound('Automation rule not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $this->automationService->delete($automationRule);

        return $this->noContent();
    }
}
