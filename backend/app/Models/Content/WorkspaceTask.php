<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Enums\Content\TaskPriority;
use App\Enums\Content\TaskStatus;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WorkspaceTask Model
 *
 * Represents a task within a workspace, optionally linked to a post.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string|null $post_id Post UUID
 * @property string $title Task title
 * @property string|null $description Task description
 * @property string|null $assigned_to_user_id Assigned user UUID
 * @property string $created_by_user_id Creator user UUID
 * @property TaskStatus $status Task status
 * @property \Carbon\Carbon|null $due_date Task due date
 * @property TaskPriority $priority Task priority
 * @property \Carbon\Carbon|null $completed_at When the task was completed
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 * @property-read Post|null $post
 * @property-read User|null $assignedTo
 * @property-read User $createdBy
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> forStatus(TaskStatus $status)
 * @method static Builder<static> forAssignee(string $userId)
 * @method static Builder<static> overdue()
 */
final class WorkspaceTask extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'workspace_tasks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'post_id',
        'title',
        'description',
        'assigned_to_user_id',
        'created_by_user_id',
        'status',
        'due_date',
        'priority',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the workspace that this task belongs to.
     *
     * @return BelongsTo<Workspace, WorkspaceTask>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the post linked to this task.
     *
     * @return BelongsTo<Post, WorkspaceTask>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the user assigned to this task.
     *
     * @return BelongsTo<User, WorkspaceTask>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Get the user who created this task.
     *
     * @return BelongsTo<User, WorkspaceTask>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<WorkspaceTask>  $query
     * @return Builder<WorkspaceTask>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to filter by status.
     *
     * @param  Builder<WorkspaceTask>  $query
     * @return Builder<WorkspaceTask>
     */
    public function scopeForStatus(Builder $query, TaskStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by assignee.
     *
     * @param  Builder<WorkspaceTask>  $query
     * @return Builder<WorkspaceTask>
     */
    public function scopeForAssignee(Builder $query, string $userId): Builder
    {
        return $query->where('assigned_to_user_id', $userId);
    }

    /**
     * Scope to filter overdue tasks.
     *
     * @param  Builder<WorkspaceTask>  $query
     * @return Builder<WorkspaceTask>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
            ->where('status', '!=', TaskStatus::DONE);
    }
}
