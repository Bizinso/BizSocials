<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Content\ApprovalWorkflow;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ApprovalWorkflowService extends BaseService
{
    /**
     * List approval workflows for a workspace.
     */
    public function list(string $workspaceId): LengthAwarePaginator
    {
        return ApprovalWorkflow::forWorkspace($workspaceId)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate(15);
    }

    /**
     * Create a new approval workflow.
     *
     * @param array<string, mixed> $data
     */
    public function create(string $workspaceId, array $data): ApprovalWorkflow
    {
        $workflow = ApprovalWorkflow::create([
            'workspace_id' => $workspaceId,
            'name' => $data['name'],
            'is_active' => $data['is_active'] ?? true,
            'is_default' => false,
            'steps' => $data['steps'],
        ]);

        $this->log('Approval workflow created', ['workflow_id' => $workflow->id]);

        return $workflow;
    }

    /**
     * Update an approval workflow.
     *
     * @param array<string, mixed> $data
     */
    public function update(ApprovalWorkflow $workflow, array $data): ApprovalWorkflow
    {
        $workflow->update($data);

        $this->log('Approval workflow updated', ['workflow_id' => $workflow->id]);

        return $workflow;
    }

    /**
     * Delete an approval workflow.
     */
    public function delete(ApprovalWorkflow $workflow): void
    {
        $workflow->delete();

        $this->log('Approval workflow deleted', ['workflow_id' => $workflow->id]);
    }

    /**
     * Set a workflow as the default for its workspace.
     * Unsets any other default workflow in the same workspace.
     */
    public function setDefault(ApprovalWorkflow $workflow): void
    {
        $this->transaction(function () use ($workflow) {
            // Unset existing defaults in this workspace
            ApprovalWorkflow::forWorkspace($workflow->workspace_id)
                ->where('is_default', true)
                ->update(['is_default' => false]);

            // Set this workflow as default
            $workflow->update(['is_default' => true]);

            $this->log('Approval workflow set as default', ['workflow_id' => $workflow->id]);
        });
    }
}
