<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\Content\TaskPriority;
use App\Enums\Content\TaskStatus;
use App\Models\Content\WorkspaceTask;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class WorkspaceTaskService extends BaseService
{
    /**
     * List tasks for a workspace with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function list(string $workspaceId, array $filters = []): LengthAwarePaginator
    {
        $query = WorkspaceTask::forWorkspace($workspaceId)
            ->with(['assignedTo', 'createdBy', 'post']);

        if (!empty($filters['status'])) {
            $query->forStatus(TaskStatus::from($filters['status']));
        }

        if (!empty($filters['assigned_to_user_id'])) {
            $query->forAssignee($filters['assigned_to_user_id']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    /**
     * Create a new workspace task.
     *
     * @param array<string, mixed> $data
     */
    public function create(string $workspaceId, string $userId, array $data): WorkspaceTask
    {
        $task = WorkspaceTask::create([
            'workspace_id' => $workspaceId,
            'created_by_user_id' => $userId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
            'post_id' => $data['post_id'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'todo',
        ]);

        $task->load(['assignedTo', 'createdBy']);

        $this->log('Workspace task created', ['task_id' => $task->id]);

        return $task;
    }

    /**
     * Update a workspace task.
     *
     * @param array<string, mixed> $data
     */
    public function update(WorkspaceTask $task, array $data): WorkspaceTask
    {
        $task->update($data);

        $task->load(['assignedTo', 'createdBy']);

        $this->log('Workspace task updated', ['task_id' => $task->id]);

        return $task;
    }

    /**
     * Delete a workspace task.
     */
    public function delete(WorkspaceTask $task): void
    {
        $task->delete();

        $this->log('Workspace task deleted', ['task_id' => $task->id]);
    }

    /**
     * Mark a task as complete.
     */
    public function complete(WorkspaceTask $task): void
    {
        $task->update([
            'status' => TaskStatus::DONE,
            'completed_at' => now(),
        ]);

        $this->log('Workspace task completed', ['task_id' => $task->id]);
    }
}
