<?php

declare(strict_types=1);

namespace App\Models\Workspace;

use App\Enums\Workspace\Permission;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WorkspaceMembership Model
 *
 * Represents the membership of a user in a workspace with their assigned role.
 * This is the join entity linking users to workspaces.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string $user_id User UUID
 * @property WorkspaceRole $role Role within workspace
 * @property \Carbon\Carbon $joined_at When the user joined
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 * @property-read User $user
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> forUser(string $userId)
 * @method static Builder<static> withRole(WorkspaceRole $role)
 * @method static Builder<static> owners()
 * @method static Builder<static> admins()
 */
final class WorkspaceMembership extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'workspace_memberships';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'user_id',
        'role',
        'joined_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => WorkspaceRole::class,
            'joined_at' => 'datetime',
        ];
    }

    /**
     * Get the workspace this membership belongs to.
     *
     * @return BelongsTo<Workspace, WorkspaceMembership>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the user this membership belongs to.
     *
     * @return BelongsTo<User, WorkspaceMembership>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<WorkspaceMembership>  $query
     * @return Builder<WorkspaceMembership>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to filter by user.
     *
     * @param  Builder<WorkspaceMembership>  $query
     * @return Builder<WorkspaceMembership>
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by role.
     *
     * @param  Builder<WorkspaceMembership>  $query
     * @return Builder<WorkspaceMembership>
     */
    public function scopeWithRole(Builder $query, WorkspaceRole $role): Builder
    {
        return $query->where('role', $role);
    }

    /**
     * Scope to get only owners.
     *
     * @param  Builder<WorkspaceMembership>  $query
     * @return Builder<WorkspaceMembership>
     */
    public function scopeOwners(Builder $query): Builder
    {
        return $query->where('role', WorkspaceRole::OWNER);
    }

    /**
     * Scope to get only admins.
     *
     * @param  Builder<WorkspaceMembership>  $query
     * @return Builder<WorkspaceMembership>
     */
    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('role', WorkspaceRole::ADMIN);
    }

    /**
     * Check if this membership has owner role.
     */
    public function isOwner(): bool
    {
        return $this->role === WorkspaceRole::OWNER;
    }

    /**
     * Check if this membership has admin role.
     */
    public function isAdmin(): bool
    {
        return $this->role === WorkspaceRole::ADMIN;
    }

    /**
     * Check if this membership has editor role.
     */
    public function isEditor(): bool
    {
        return $this->role === WorkspaceRole::EDITOR;
    }

    /**
     * Check if this membership has viewer role.
     */
    public function isViewer(): bool
    {
        return $this->role === WorkspaceRole::VIEWER;
    }

    /**
     * Check if this membership can manage workspace settings.
     * Delegates to the role.
     */
    public function canManageWorkspace(): bool
    {
        return $this->role->canManageWorkspace();
    }

    /**
     * Check if this membership can manage workspace members.
     * Delegates to the role.
     */
    public function canManageMembers(): bool
    {
        return $this->role->canManageMembers();
    }

    /**
     * Check if this membership can create content.
     * Delegates to the role.
     */
    public function canCreateContent(): bool
    {
        return $this->role->canCreateContent();
    }

    /**
     * Check if this membership can approve content.
     * Delegates to the role.
     */
    public function canApproveContent(): bool
    {
        return $this->role->canApproveContent();
    }

    /**
     * Check if this membership can publish content directly.
     * Delegates to the role.
     */
    public function canPublishDirectly(): bool
    {
        return $this->role->canPublishDirectly();
    }

    /**
     * Check if this membership grants the given permission.
     * Delegates to the role's hasPermission() method.
     */
    public function hasPermission(Permission|string $permission): bool
    {
        return $this->role->hasPermission($permission);
    }

    /**
     * Update the role of this membership.
     */
    public function updateRole(WorkspaceRole $newRole): void
    {
        $this->role = $newRole;
        $this->save();
    }
}
