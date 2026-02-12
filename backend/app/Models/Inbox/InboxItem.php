<?php

declare(strict_types=1);

namespace App\Models\Inbox;

use App\Enums\Inbox\InboxItemStatus;
use App\Enums\Inbox\InboxItemType;
use App\Models\Content\PostTarget;
use App\Models\Social\SocialAccount;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * InboxItem Model
 *
 * Represents a comment or mention from a social platform.
 * Tracks the engagement received on connected social accounts.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string $social_account_id Social account UUID
 * @property string|null $post_target_id Post target UUID (if comment on our post)
 * @property InboxItemType $item_type Type of inbox item
 * @property InboxItemStatus $status Item status
 * @property string $platform_item_id Platform's comment/mention ID
 * @property string|null $platform_post_id Platform's post ID
 * @property string $author_name Author's display name
 * @property string|null $author_username Author's username
 * @property string|null $author_profile_url Author's profile URL
 * @property string|null $author_avatar_url Author's avatar URL
 * @property string $content_text Content text
 * @property \Carbon\Carbon $platform_created_at When created on platform
 * @property string|null $assigned_to_user_id Assigned user UUID
 * @property \Carbon\Carbon|null $assigned_at When assigned
 * @property \Carbon\Carbon|null $resolved_at When resolved
 * @property string|null $resolved_by_user_id User who resolved UUID
 * @property array|null $metadata Additional metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 * @property-read SocialAccount $socialAccount
 * @property-read PostTarget|null $postTarget
 * @property-read User|null $assignedTo
 * @property-read User|null $resolvedBy
 * @property-read Collection<InboxReply> $replies
 * @property-read Collection<InboxItemTag> $tags
 * @property-read Collection<InboxInternalNote> $notes
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> unread()
 * @method static Builder<static> active()
 * @method static Builder<static> withStatus(InboxItemStatus $status)
 * @method static Builder<static> ofType(InboxItemType $type)
 * @method static Builder<static> assignedToUser(string $userId)
 * @method static Builder<static> needsArchiving(int $days)
 */
final class InboxItem extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inbox_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'social_account_id',
        'post_target_id',
        'item_type',
        'status',
        'platform_item_id',
        'platform_post_id',
        'author_name',
        'author_username',
        'author_profile_url',
        'author_avatar_url',
        'content_text',
        'platform_created_at',
        'assigned_to_user_id',
        'assigned_at',
        'resolved_at',
        'resolved_by_user_id',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'item_type' => InboxItemType::class,
            'status' => InboxItemStatus::class,
            'platform_created_at' => 'datetime',
            'assigned_at' => 'datetime',
            'resolved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the workspace that this inbox item belongs to.
     *
     * @return BelongsTo<Workspace, InboxItem>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the social account that this inbox item belongs to.
     *
     * @return BelongsTo<SocialAccount, InboxItem>
     */
    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    /**
     * Get the post target (if this is a comment on our post).
     *
     * @return BelongsTo<PostTarget, InboxItem>
     */
    public function postTarget(): BelongsTo
    {
        return $this->belongsTo(PostTarget::class);
    }

    /**
     * Get the user this item is assigned to.
     *
     * @return BelongsTo<User, InboxItem>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Get the user who resolved this item.
     *
     * @return BelongsTo<User, InboxItem>
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    /**
     * Get the replies to this inbox item.
     *
     * @return HasMany<InboxReply>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(InboxReply::class)->orderByDesc('sent_at');
    }

    /**
     * Get the tags assigned to this inbox item.
     *
     * @return BelongsToMany<InboxItemTag>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(InboxItemTag::class, 'inbox_item_tag_assignments', 'inbox_item_id', 'tag_id');
    }

    /**
     * Get the internal notes for this inbox item.
     *
     * @return HasMany<InboxInternalNote>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(InboxInternalNote::class);
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<InboxItem>  $query
     * @return Builder<InboxItem>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to get unread items.
     *
     * @param  Builder<InboxItem>  $query
     * @return Builder<InboxItem>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('status', InboxItemStatus::UNREAD);
    }

    /**
     * Scope to get active items (not archived).
     *
     * @param  Builder<InboxItem>  $query
     * @return Builder<InboxItem>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNot('status', InboxItemStatus::ARCHIVED);
    }

    /**
     * Scope to filter by status.
     *
     * @param  Builder<InboxItem>  $query
     * @return Builder<InboxItem>
     */
    public function scopeWithStatus(Builder $query, InboxItemStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by type.
     *
     * @param  Builder<InboxItem>  $query
     * @return Builder<InboxItem>
     */
    public function scopeOfType(Builder $query, InboxItemType $type): Builder
    {
        return $query->where('item_type', $type);
    }

    /**
     * Scope to filter by assigned user.
     *
     * @param  Builder<InboxItem>  $query
     * @return Builder<InboxItem>
     */
    public function scopeAssignedToUser(Builder $query, string $userId): Builder
    {
        return $query->where('assigned_to_user_id', $userId);
    }

    /**
     * Scope to get items that need archiving (resolved for X days).
     *
     * @param  Builder<InboxItem>  $query
     * @return Builder<InboxItem>
     */
    public function scopeNeedsArchiving(Builder $query, int $days = 30): Builder
    {
        return $query->where('status', InboxItemStatus::RESOLVED)
            ->where('resolved_at', '<=', now()->subDays($days));
    }

    /**
     * Check if the item is unread.
     */
    public function isUnread(): bool
    {
        return $this->status === InboxItemStatus::UNREAD;
    }

    /**
     * Check if the item is resolved.
     */
    public function isResolved(): bool
    {
        return $this->status === InboxItemStatus::RESOLVED;
    }

    /**
     * Check if the item is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === InboxItemStatus::ARCHIVED;
    }

    /**
     * Check if this item can be replied to.
     */
    public function canReply(): bool
    {
        return $this->item_type->canReply();
    }

    /**
     * Mark the item as read.
     */
    public function markAsRead(): void
    {
        if (!$this->status->canTransitionTo(InboxItemStatus::READ)) {
            return;
        }

        $this->status = InboxItemStatus::READ;
        $this->save();
    }

    /**
     * Mark the item as resolved.
     */
    public function markAsResolved(User $user): void
    {
        if (!$this->status->canTransitionTo(InboxItemStatus::RESOLVED)) {
            return;
        }

        $this->status = InboxItemStatus::RESOLVED;
        $this->resolved_at = now();
        $this->resolved_by_user_id = $user->id;
        $this->save();
    }

    /**
     * Archive the item.
     */
    public function archive(): void
    {
        if (!$this->status->canTransitionTo(InboxItemStatus::ARCHIVED)) {
            return;
        }

        $this->status = InboxItemStatus::ARCHIVED;
        $this->save();
    }

    /**
     * Reopen the item (move back to READ status).
     */
    public function reopen(): void
    {
        if (!$this->status->canTransitionTo(InboxItemStatus::READ)) {
            return;
        }

        $this->status = InboxItemStatus::READ;
        $this->resolved_at = null;
        $this->resolved_by_user_id = null;
        $this->save();
    }

    /**
     * Assign the item to a user.
     */
    public function assignTo(User $user): void
    {
        $this->assigned_to_user_id = $user->id;
        $this->assigned_at = now();
        $this->save();
    }

    /**
     * Unassign the item.
     */
    public function unassign(): void
    {
        $this->assigned_to_user_id = null;
        $this->assigned_at = null;
        $this->save();
    }

    /**
     * Get the count of replies.
     */
    public function getReplyCount(): int
    {
        return $this->replies()->count();
    }
}
