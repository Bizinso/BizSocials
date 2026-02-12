<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ApprovalWorkflow Model
 *
 * Represents a multi-step approval workflow configuration for a workspace.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string $name Workflow name
 * @property bool $is_active Whether workflow is active
 * @property bool $is_default Whether this is the default workflow
 * @property array $steps Workflow steps configuration
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> active()
 * @method static Builder<static> default()
 */
final class ApprovalWorkflow extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'approval_workflows';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'name',
        'is_active',
        'is_default',
        'steps',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'steps' => 'array',
        ];
    }

    /**
     * Get the workspace that this workflow belongs to.
     *
     * @return BelongsTo<Workspace, ApprovalWorkflow>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<ApprovalWorkflow>  $query
     * @return Builder<ApprovalWorkflow>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to filter active workflows.
     *
     * @param  Builder<ApprovalWorkflow>  $query
     * @return Builder<ApprovalWorkflow>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter default workflows.
     *
     * @param  Builder<ApprovalWorkflow>  $query
     * @return Builder<ApprovalWorkflow>
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }
}
