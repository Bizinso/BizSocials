<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BelongsToWorkspace Trait
 *
 * Provides workspace scoping for models that belong to a workspace.
 * Optionally adds global scope to filter records by workspace_id.
 *
 * @property string $workspace_id Workspace UUID
 * @property-read Workspace $workspace
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait BelongsToWorkspace
{
    /**
     * Boot the trait.
     */
    public static function bootBelongsToWorkspace(): void
    {
        static::creating(function ($model) {
            // Automatically set workspace_id if not set and we have a current workspace
            if (empty($model->workspace_id) && app()->has('current_workspace')) {
                $model->workspace_id = app('current_workspace')->id;
            }
        });

        // Optionally add global scope to filter by workspace
        // Only if the model has $autoScopeWorkspace = true
        if (property_exists(static::class, 'autoScopeWorkspace') && static::$autoScopeWorkspace) {
            static::addGlobalScope('workspace', function (Builder $builder) {
                if (app()->has('current_workspace')) {
                    $builder->where(
                        $builder->getModel()->getTable() . '.workspace_id',
                        app('current_workspace')->id
                    );
                }
            });
        }
    }

    /**
     * Get the workspace that this model belongs to.
     *
     * @return BelongsTo<Workspace, static>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<static>  $query
     * @param  string  $workspaceId
     * @return Builder<static>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->withoutGlobalScope('workspace')->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to include all workspaces (bypass global scope).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithoutWorkspaceScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('workspace');
    }

    /**
     * Check if this model belongs to a specific workspace.
     */
    public function belongsToWorkspace(string $workspaceId): bool
    {
        return $this->workspace_id === $workspaceId;
    }
}
