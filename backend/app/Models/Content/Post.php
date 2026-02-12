<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Enums\Content\PostStatus;
use App\Enums\Content\PostType;
use App\Models\Content\ApprovalStepDecision;
use App\Models\Content\PostNote;
use App\Models\Content\PostRevision;
use App\Models\Content\WorkspaceTask;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Post Model
 *
 * Represents a social media post within a workspace.
 * Supports multi-platform publishing, scheduling, and approval workflows.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string $created_by_user_id User who created the post
 * @property string|null $content_text Main post content
 * @property array|null $content_variations Platform-specific content
 * @property PostStatus $status Post status
 * @property PostType $post_type Type of post
 * @property \Carbon\Carbon|null $scheduled_at When to publish
 * @property string|null $scheduled_timezone Timezone for scheduling
 * @property \Carbon\Carbon|null $published_at When published
 * @property \Carbon\Carbon|null $submitted_at When submitted for approval
 * @property array|null $hashtags Hashtags array
 * @property array|null $mentions Mentions array
 * @property string|null $link_url Attached link URL
 * @property array|null $link_preview Link preview data
 * @property string|null $first_comment First comment to post
 * @property string|null $rejection_reason Reason for rejection
 * @property array|null $metadata Additional metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read Workspace $workspace
 * @property-read User $author
 * @property-read Collection<PostTarget> $targets
 * @property-read Collection<PostMedia> $media
 * @property-read Collection<ApprovalDecision> $approvalDecisions
 * @property-read ApprovalDecision|null $activeApprovalDecision
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> withStatus(PostStatus $status)
 * @method static Builder<static> scheduled()
 * @method static Builder<static> published()
 * @method static Builder<static> draft()
 * @method static Builder<static> requiresApproval()
 */
final class Post extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'posts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'created_by_user_id',
        'content_text',
        'content_variations',
        'status',
        'post_type',
        'scheduled_at',
        'scheduled_timezone',
        'published_at',
        'submitted_at',
        'hashtags',
        'mentions',
        'link_url',
        'link_preview',
        'first_comment',
        'rejection_reason',
        'metadata',
        'category_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'post_type' => PostType::class,
            'content_variations' => 'array',
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'submitted_at' => 'datetime',
            'hashtags' => 'array',
            'mentions' => 'array',
            'link_preview' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the workspace that this post belongs to.
     *
     * @return BelongsTo<Workspace, Post>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the user who created this post.
     *
     * @return BelongsTo<User, Post>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the content category of this post.
     *
     * @return BelongsTo<ContentCategory, Post>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ContentCategory::class, 'category_id');
    }

    /**
     * Get the post targets (social accounts to publish to).
     *
     * @return HasMany<PostTarget>
     */
    public function targets(): HasMany
    {
        return $this->hasMany(PostTarget::class);
    }

    /**
     * Get the media attachments for this post.
     *
     * @return HasMany<PostMedia>
     */
    public function media(): HasMany
    {
        return $this->hasMany(PostMedia::class)->orderBy('sort_order');
    }

    /**
     * Get all approval decisions for this post.
     *
     * @return HasMany<ApprovalDecision>
     */
    public function approvalDecisions(): HasMany
    {
        return $this->hasMany(ApprovalDecision::class)->orderByDesc('decided_at');
    }

    /**
     * Get the current active approval decision.
     *
     * @return HasOne<ApprovalDecision>
     */
    public function activeApprovalDecision(): HasOne
    {
        return $this->hasOne(ApprovalDecision::class)->where('is_active', true);
    }

    /**
     * Get the notes for this post.
     *
     * @return HasMany<PostNote>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(PostNote::class);
    }

    /**
     * Get the revisions for this post.
     *
     * @return HasMany<PostRevision>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(PostRevision::class);
    }

    /**
     * Get the tasks linked to this post.
     *
     * @return HasMany<WorkspaceTask>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(WorkspaceTask::class);
    }

    /**
     * Get the step decisions for this post.
     *
     * @return HasMany<ApprovalStepDecision>
     */
    public function stepDecisions(): HasMany
    {
        return $this->hasMany(ApprovalStepDecision::class);
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to filter by status.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopeWithStatus(Builder $query, PostStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get scheduled posts.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', PostStatus::SCHEDULED)
            ->whereNotNull('scheduled_at');
    }

    /**
     * Scope to get published posts.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostStatus::PUBLISHED);
    }

    /**
     * Scope to get draft posts.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', PostStatus::DRAFT);
    }

    /**
     * Scope to get posts requiring approval.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopeRequiresApproval(Builder $query): Builder
    {
        return $query->where('status', PostStatus::SUBMITTED);
    }

    /**
     * Check if the post can be edited.
     */
    public function canEdit(): bool
    {
        return $this->status->canEdit();
    }

    /**
     * Check if the post can be deleted.
     */
    public function canDelete(): bool
    {
        return $this->status->canDelete();
    }

    /**
     * Check if the post can be published.
     */
    public function canPublish(): bool
    {
        return $this->status->canPublish();
    }

    /**
     * Check if the post has any targets.
     */
    public function hasTargets(): bool
    {
        return $this->targets()->exists();
    }

    /**
     * Get the count of targets.
     */
    public function getTargetCount(): int
    {
        return $this->targets()->count();
    }

    /**
     * Submit the post for approval.
     */
    public function submit(): void
    {
        if (!$this->status->canTransitionTo(PostStatus::SUBMITTED)) {
            return;
        }

        $this->status = PostStatus::SUBMITTED;
        $this->submitted_at = now();
        $this->save();
    }

    /**
     * Approve the post.
     */
    public function approve(): void
    {
        if (!$this->status->canTransitionTo(PostStatus::APPROVED)) {
            return;
        }

        $this->status = PostStatus::APPROVED;
        $this->rejection_reason = null;
        $this->save();
    }

    /**
     * Reject the post.
     */
    public function reject(?string $reason = null): void
    {
        if (!$this->status->canTransitionTo(PostStatus::REJECTED)) {
            return;
        }

        $this->status = PostStatus::REJECTED;
        $this->rejection_reason = $reason;
        $this->save();
    }

    /**
     * Schedule the post for publishing.
     */
    public function schedule(\DateTimeInterface $scheduledAt, ?string $timezone = null): void
    {
        if (!$this->status->canTransitionTo(PostStatus::SCHEDULED)) {
            return;
        }

        $this->status = PostStatus::SCHEDULED;
        $this->scheduled_at = $scheduledAt;
        $this->scheduled_timezone = $timezone;
        $this->save();
    }

    /**
     * Mark the post as publishing.
     */
    public function markPublishing(): void
    {
        if (!$this->status->canTransitionTo(PostStatus::PUBLISHING)) {
            return;
        }

        $this->status = PostStatus::PUBLISHING;
        $this->save();
    }

    /**
     * Mark the post as published.
     */
    public function markPublished(): void
    {
        if (!$this->status->canTransitionTo(PostStatus::PUBLISHED)) {
            return;
        }

        $this->status = PostStatus::PUBLISHED;
        $this->published_at = now();
        $this->save();
    }

    /**
     * Mark the post as failed.
     */
    public function markFailed(): void
    {
        if (!$this->status->canTransitionTo(PostStatus::FAILED)) {
            return;
        }

        $this->status = PostStatus::FAILED;
        $this->save();
    }

    /**
     * Cancel the post.
     */
    public function cancel(): void
    {
        if (!$this->status->canTransitionTo(PostStatus::CANCELLED)) {
            return;
        }

        $this->status = PostStatus::CANCELLED;
        $this->save();
    }
}
