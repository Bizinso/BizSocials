<?php

declare(strict_types=1);

namespace App\Models\Workspace;

use App\Enums\Workspace\WorkspaceRole;
use App\Enums\Workspace\WorkspaceStatus;
use App\Models\Content\Post;
use App\Models\Content\MediaFolder;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Workspace Model
 *
 * Represents an isolated organizational container within a tenant.
 * Each workspace is an isolated container for social accounts, posts,
 * and team collaboration.
 *
 * @property string $id UUID primary key
 * @property string $tenant_id Tenant UUID
 * @property string $name Workspace display name
 * @property string $slug URL-safe unique identifier
 * @property string|null $description Workspace description
 * @property WorkspaceStatus $status Workspace status
 * @property array|null $settings Workspace preferences
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read Tenant $tenant
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WorkspaceMembership> $memberships
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $members
 *
 * @method static Builder<static> active()
 * @method static Builder<static> forTenant(string $tenantId)
 * @method static Builder<static> forUser(string $userId)
 */
final class Workspace extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'workspaces';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'status',
        'settings',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WorkspaceStatus::class,
            'settings' => 'array',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Workspace $workspace): void {
            // Auto-generate slug if not provided
            if (empty($workspace->slug)) {
                $workspace->slug = static::generateSlug($workspace->name, $workspace->tenant_id);
            }

            // Initialize default settings if not provided
            if ($workspace->settings === null) {
                $workspace->settings = [
                    'timezone' => 'Asia/Kolkata',
                    'date_format' => 'DD/MM/YYYY',
                    'approval_workflow' => [
                        'enabled' => true,
                        'required_for_roles' => ['editor'],
                    ],
                    'default_social_accounts' => [],
                    'content_categories' => ['Marketing', 'Product', 'Support'],
                    'hashtag_groups' => [
                        'brand' => ['#BizSocials', '#SocialMedia'],
                        'campaign' => [],
                    ],
                ];
            }
        });
    }

    /**
     * Get the tenant that this workspace belongs to.
     *
     * @return BelongsTo<Tenant, Workspace>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get all memberships for this workspace.
     *
     * @return HasMany<WorkspaceMembership>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(WorkspaceMembership::class);
    }

    /**
     * Get all members (users) of this workspace through memberships.
     *
     * @return BelongsToMany<User>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_memberships')
            ->withPivot(['id', 'role', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get all social accounts connected to this workspace.
     *
     * @return HasMany<SocialAccount>
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * Get all posts in this workspace.
     *
     * @return HasMany<Post>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get all media folders in this workspace.
     *
     * @return HasMany<MediaFolder>
     */
    public function mediaFolders(): HasMany
    {
        return $this->hasMany(MediaFolder::class);
    }

    /**
     * Scope to get only active workspaces.
     *
     * @param  Builder<Workspace>  $query
     * @return Builder<Workspace>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', WorkspaceStatus::ACTIVE);
    }

    /**
     * Scope to filter by tenant.
     *
     * @param  Builder<Workspace>  $query
     * @return Builder<Workspace>
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter workspaces where a user is a member.
     *
     * @param  Builder<Workspace>  $query
     * @return Builder<Workspace>
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->whereHas('memberships', function (Builder $q) use ($userId): void {
            $q->where('user_id', $userId);
        });
    }

    /**
     * Generate a URL-safe slug from a name.
     * Handles duplicates by appending a number.
     */
    public static function generateSlug(string $name, string $tenantId): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (static::where('tenant_id', $tenantId)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . ++$counter;
        }

        return $slug;
    }

    /**
     * Check if the workspace is active.
     */
    public function isActive(): bool
    {
        return $this->status === WorkspaceStatus::ACTIVE;
    }

    /**
     * Check if the workspace has access (can be used normally).
     */
    public function hasAccess(): bool
    {
        return $this->status->hasAccess();
    }

    /**
     * Get the owner of the workspace.
     */
    public function getOwner(): ?User
    {
        $ownerMembership = $this->memberships()
            ->where('role', WorkspaceRole::OWNER)
            ->first();

        return $ownerMembership?->user;
    }

    /**
     * Get the number of members in the workspace.
     */
    public function getMemberCount(): int
    {
        return $this->memberships()->count();
    }

    /**
     * Check if a user is a member of the workspace.
     */
    public function hasMember(string $userId): bool
    {
        return $this->memberships()->where('user_id', $userId)->exists();
    }

    /**
     * Get the role of a user in the workspace.
     */
    public function getMemberRole(string $userId): ?WorkspaceRole
    {
        $membership = $this->memberships()->where('user_id', $userId)->first();

        return $membership?->role;
    }

    /**
     * Add a user as a member of the workspace.
     */
    public function addMember(User $user, WorkspaceRole $role): WorkspaceMembership
    {
        return $this->memberships()->create([
            'user_id' => $user->id,
            'role' => $role,
            'joined_at' => now(),
        ]);
    }

    /**
     * Remove a user from the workspace.
     */
    public function removeMember(string $userId): bool
    {
        return $this->memberships()->where('user_id', $userId)->delete() > 0;
    }

    /**
     * Get a setting value using dot notation.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->settings ?? [], $key, $default);
    }

    /**
     * Set a setting value using dot notation.
     */
    public function setSetting(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        Arr::set($settings, $key, $value);
        $this->settings = $settings;
        $this->save();
    }

    /**
     * Suspend the workspace.
     */
    public function suspend(): void
    {
        $this->status = WorkspaceStatus::SUSPENDED;
        $this->save();
    }

    /**
     * Activate the workspace.
     */
    public function activate(): void
    {
        $this->status = WorkspaceStatus::ACTIVE;
        $this->save();
    }

    /**
     * Check if approval is required for a given role.
     */
    public function isApprovalRequired(WorkspaceRole $role): bool
    {
        $workflowEnabled = $this->getSetting('approval_workflow.enabled', false);

        if (! $workflowEnabled) {
            return false;
        }

        $requiredForRoles = $this->getSetting('approval_workflow.required_for_roles', []);

        return in_array($role->value, $requiredForRoles, true);
    }
}
