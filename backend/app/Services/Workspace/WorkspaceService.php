<?php

declare(strict_types=1);

namespace App\Services\Workspace;

use App\Data\Workspace\CreateWorkspaceData;
use App\Data\Workspace\UpdateWorkspaceData;
use App\Enums\Workspace\WorkspaceRole;
use App\Enums\Workspace\WorkspaceStatus;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

final class WorkspaceService extends BaseService
{
    /**
     * List workspaces for a tenant.
     *
     * @param array<string, mixed> $filters
     */
    public function listForTenant(Tenant $tenant, array $filters = []): LengthAwarePaginator
    {
        $query = Workspace::where('tenant_id', $tenant->id);

        // Apply status filter
        if (!empty($filters['status'])) {
            $status = WorkspaceStatus::tryFrom($filters['status']);
            if ($status !== null) {
                $query->where('status', $status);
            }
        } else {
            // By default, exclude deleted workspaces
            $query->where('status', '!=', WorkspaceStatus::DELETED);
        }

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Include archived filter
        if (isset($filters['include_archived']) && $filters['include_archived']) {
            // Already included
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = min($perPage, 100); // Max 100 per page

        return $query
            ->withCount('memberships')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create a new workspace.
     */
    public function create(Tenant $tenant, User $creator, CreateWorkspaceData $data): Workspace
    {
        return $this->transaction(function () use ($tenant, $creator, $data) {
            // Build initial settings
            $settings = [
                'timezone' => $tenant->getSetting('timezone', 'Asia/Kolkata'),
                'date_format' => 'DD/MM/YYYY',
                'approval_workflow' => [
                    'enabled' => true,
                    'required_for_roles' => ['editor'],
                ],
                'default_social_accounts' => [],
                'content_categories' => ['Marketing', 'Product', 'Support'],
                'hashtag_groups' => [
                    'brand' => [],
                    'campaign' => [],
                ],
            ];

            if ($data->icon !== null) {
                $settings['icon'] = $data->icon;
            }
            if ($data->color !== null) {
                $settings['color'] = $data->color;
            }

            $workspace = Workspace::create([
                'tenant_id' => $tenant->id,
                'name' => $data->name,
                'description' => $data->description,
                'status' => WorkspaceStatus::ACTIVE,
                'settings' => $settings,
            ]);

            // Add creator as owner
            $workspace->addMember($creator, WorkspaceRole::OWNER);

            $this->log('Workspace created', [
                'workspace_id' => $workspace->id,
                'tenant_id' => $tenant->id,
                'creator_id' => $creator->id,
            ]);

            return $workspace;
        });
    }

    /**
     * Get a workspace by ID.
     */
    public function get(string $workspaceId): Workspace
    {
        $workspace = Workspace::find($workspaceId);

        if ($workspace === null) {
            throw ValidationException::withMessages([
                'workspace' => ['Workspace not found.'],
            ]);
        }

        return $workspace;
    }

    /**
     * Update a workspace.
     */
    public function update(Workspace $workspace, UpdateWorkspaceData $data): Workspace
    {
        return $this->transaction(function () use ($workspace, $data) {
            $updateData = [];

            if ($data->name !== null && !($data->name instanceof \Spatie\LaravelData\Optional)) {
                $updateData['name'] = $data->name;
            }

            if ($data->description !== null && !($data->description instanceof \Spatie\LaravelData\Optional)) {
                $updateData['description'] = $data->description;
            }

            if (!empty($updateData)) {
                $workspace->update($updateData);
            }

            // Update icon and color in settings
            if ($data->icon !== null && !($data->icon instanceof \Spatie\LaravelData\Optional)) {
                $workspace->setSetting('icon', $data->icon);
            }

            if ($data->color !== null && !($data->color instanceof \Spatie\LaravelData\Optional)) {
                $workspace->setSetting('color', $data->color);
            }

            $this->log('Workspace updated', ['workspace_id' => $workspace->id]);

            return $workspace->fresh();
        });
    }

    /**
     * Update workspace settings.
     *
     * @param array<string, mixed> $settings
     */
    public function updateSettings(Workspace $workspace, array $settings): Workspace
    {
        return $this->transaction(function () use ($workspace, $settings) {
            $currentSettings = $workspace->settings ?? [];
            $mergedSettings = array_merge($currentSettings, $settings);
            $workspace->settings = $mergedSettings;
            $workspace->save();

            $this->log('Workspace settings updated', ['workspace_id' => $workspace->id]);

            return $workspace->fresh();
        });
    }

    /**
     * Delete a workspace.
     */
    public function delete(Workspace $workspace): void
    {
        $this->transaction(function () use ($workspace) {
            $workspace->status = WorkspaceStatus::DELETED;
            $workspace->save();
            $workspace->delete();

            $this->log('Workspace deleted', ['workspace_id' => $workspace->id]);
        });
    }

    /**
     * Archive a workspace.
     */
    public function archive(Workspace $workspace): Workspace
    {
        return $this->transaction(function () use ($workspace) {
            if ($workspace->status !== WorkspaceStatus::ACTIVE) {
                throw ValidationException::withMessages([
                    'status' => ['Only active workspaces can be archived.'],
                ]);
            }

            $workspace->status = WorkspaceStatus::SUSPENDED;
            $workspace->save();

            $this->log('Workspace archived', ['workspace_id' => $workspace->id]);

            return $workspace;
        });
    }

    /**
     * Restore a workspace from archive.
     */
    public function restore(Workspace $workspace): Workspace
    {
        return $this->transaction(function () use ($workspace) {
            if ($workspace->status !== WorkspaceStatus::SUSPENDED) {
                throw ValidationException::withMessages([
                    'status' => ['Only archived workspaces can be restored.'],
                ]);
            }

            $workspace->status = WorkspaceStatus::ACTIVE;
            $workspace->save();

            $this->log('Workspace restored', ['workspace_id' => $workspace->id]);

            return $workspace;
        });
    }
}
