<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Content;

use App\Enums\Content\TaskPriority;
use App\Enums\Content\TaskStatus;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Content\WorkspaceTask;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\WorkspaceTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class WorkspaceTaskController extends Controller
{
    public function __construct(
        private readonly WorkspaceTaskService $taskService,
    ) {}

    /**
     * List tasks for a workspace.
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

        $filters = $request->only(['status', 'assigned_to_user_id', 'priority']);
        $tasks = $this->taskService->list($workspace->id, $filters);

        return $this->paginated($tasks, 'Tasks retrieved successfully');
    }

    /**
     * Create a new task.
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'assigned_to_user_id' => 'nullable|uuid|exists:users,id',
            'post_id' => 'nullable|uuid|exists:posts,id',
            'due_date' => 'nullable|date|after_or_equal:today',
            'priority' => ['nullable', Rule::in(TaskPriority::values())],
        ]);

        $task = $this->taskService->create($workspace->id, $user->id, $validated);

        return $this->created($task, 'Task created successfully');
    }

    /**
     * Show a single task.
     */
    public function show(Request $request, Workspace $workspace, WorkspaceTask $task): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($task->workspace_id !== $workspace->id) {
            return $this->notFound('Task not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $task->load(['assignedTo', 'createdBy', 'post']);

        return $this->success($task, 'Task retrieved successfully');
    }

    /**
     * Update a task.
     */
    public function update(Request $request, Workspace $workspace, WorkspaceTask $task): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($task->workspace_id !== $workspace->id) {
            return $this->notFound('Task not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:5000',
            'assigned_to_user_id' => 'nullable|uuid|exists:users,id',
            'status' => ['sometimes', Rule::in(TaskStatus::values())],
            'due_date' => 'nullable|date',
            'priority' => ['sometimes', Rule::in(TaskPriority::values())],
        ]);

        $task = $this->taskService->update($task, $validated);

        return $this->success($task, 'Task updated successfully');
    }

    /**
     * Delete a task.
     */
    public function destroy(Request $request, Workspace $workspace, WorkspaceTask $task): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($task->workspace_id !== $workspace->id) {
            return $this->notFound('Task not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $this->taskService->delete($task);

        return $this->success(null, 'Task deleted successfully');
    }

    /**
     * Mark a task as complete.
     */
    public function complete(Request $request, Workspace $workspace, WorkspaceTask $task): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($task->workspace_id !== $workspace->id) {
            return $this->notFound('Task not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $this->taskService->complete($task);

        return $this->success($task->fresh(), 'Task completed successfully');
    }
}
