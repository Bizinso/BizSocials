<?php

declare(strict_types=1);

namespace App\Models\Feedback;

use App\Enums\Feedback\AdminPriority;
use App\Enums\Feedback\EffortEstimate;
use App\Enums\Feedback\FeedbackCategory;
use App\Enums\Feedback\FeedbackSource;
use App\Enums\Feedback\FeedbackStatus;
use App\Enums\Feedback\FeedbackType;
use App\Enums\Feedback\UserPriority;
use App\Enums\Feedback\VoteType;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Feedback Model
 *
 * Represents a user feedback submission.
 *
 * @property string $id UUID primary key
 * @property string|null $tenant_id Tenant UUID
 * @property string|null $user_id User UUID
 * @property string|null $submitter_email Submitter email
 * @property string|null $submitter_name Submitter name
 * @property string $title Feedback title
 * @property string $description Feedback description
 * @property FeedbackType $feedback_type Type of feedback
 * @property FeedbackCategory|null $category Product category
 * @property UserPriority $user_priority User's priority assessment
 * @property string|null $business_impact Business impact description
 * @property AdminPriority|null $admin_priority Admin's priority assessment
 * @property EffortEstimate|null $effort_estimate Effort estimate
 * @property FeedbackStatus $status Processing status
 * @property string|null $status_reason Reason for status change
 * @property int $vote_count Net vote count
 * @property string|null $roadmap_item_id Linked roadmap item UUID
 * @property string|null $duplicate_of_id Duplicate of feedback UUID
 * @property FeedbackSource $source Submission source
 * @property array|null $browser_info Browser info JSON
 * @property string|null $page_url Page URL where feedback was submitted
 * @property \Carbon\Carbon|null $reviewed_at When reviewed
 * @property string|null $reviewed_by Reviewer UUID
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Tenant|null $tenant
 * @property-read User|null $user
 * @property-read Feedback|null $duplicateOf
 * @property-read Collection<int, Feedback> $duplicates
 * @property-read SuperAdminUser|null $reviewedBy
 * @property-read Collection<int, FeedbackVote> $votes
 * @property-read Collection<int, FeedbackComment> $comments
 * @property-read Collection<int, FeedbackTag> $tags
 * @property-read RoadmapItem|null $roadmapItem
 *
 * @method static Builder<static> new()
 * @method static Builder<static> underReview()
 * @method static Builder<static> planned()
 * @method static Builder<static> shipped()
 * @method static Builder<static> open()
 * @method static Builder<static> closed()
 * @method static Builder<static> byType(FeedbackType $type)
 * @method static Builder<static> byCategory(FeedbackCategory $category)
 * @method static Builder<static> topVoted()
 * @method static Builder<static> forTenant(string $tenantId)
 * @method static Builder<static> search(string $query)
 */
final class Feedback extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'feedback';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'submitter_email',
        'submitter_name',
        'title',
        'description',
        'feedback_type',
        'category',
        'user_priority',
        'business_impact',
        'admin_priority',
        'effort_estimate',
        'status',
        'status_reason',
        'vote_count',
        'roadmap_item_id',
        'duplicate_of_id',
        'source',
        'browser_info',
        'page_url',
        'reviewed_at',
        'reviewed_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'feedback_type' => FeedbackType::class,
            'category' => FeedbackCategory::class,
            'user_priority' => UserPriority::class,
            'admin_priority' => AdminPriority::class,
            'effort_estimate' => EffortEstimate::class,
            'status' => FeedbackStatus::class,
            'source' => FeedbackSource::class,
            'browser_info' => 'array',
            'vote_count' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant.
     *
     * @return BelongsTo<Tenant, Feedback>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the user who submitted the feedback.
     *
     * @return BelongsTo<User, Feedback>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the feedback this is a duplicate of.
     *
     * @return BelongsTo<Feedback, Feedback>
     */
    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(Feedback::class, 'duplicate_of_id');
    }

    /**
     * Get feedback items that are duplicates of this one.
     *
     * @return HasMany<Feedback>
     */
    public function duplicates(): HasMany
    {
        return $this->hasMany(Feedback::class, 'duplicate_of_id');
    }

    /**
     * Get the admin who reviewed this feedback.
     *
     * @return BelongsTo<SuperAdminUser, Feedback>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(SuperAdminUser::class, 'reviewed_by');
    }

    /**
     * Get votes for this feedback.
     *
     * @return HasMany<FeedbackVote>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(FeedbackVote::class, 'feedback_id');
    }

    /**
     * Get comments for this feedback.
     *
     * @return HasMany<FeedbackComment>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(FeedbackComment::class, 'feedback_id');
    }

    /**
     * Get tags for this feedback.
     *
     * @return BelongsToMany<FeedbackTag>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(FeedbackTag::class, 'feedback_tag_assignments', 'feedback_id', 'tag_id')
            ->withTimestamps();
    }

    /**
     * Get the linked roadmap item.
     *
     * @return BelongsTo<RoadmapItem, Feedback>
     */
    public function roadmapItem(): BelongsTo
    {
        return $this->belongsTo(RoadmapItem::class, 'roadmap_item_id');
    }

    /**
     * Scope to get new feedback.
     *
     * @param  Builder<Feedback>  $query
     * @return Builder<Feedback>
     */
    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', FeedbackStatus::NEW);
    }

    /**
     * Scope to get feedback under review.
     *
     * @param  Builder<Feedback>  $query
     * @return Builder<Feedback>
     */
    public function scopeUnderReview(Builder $query): Builder
    {
        return $query->where('status', FeedbackStatus::UNDER_REVIEW);
    }

    /**
     * Scope to get planned feedback.
     *
     * @param  Builder<Feedback>  $query
     * @return Builder<Feedback>
     */
    public function scopePlanned(Builder $query): Builder
    {
        return $query->where('status', FeedbackStatus::PLANNED);
    }

    /**
     * Scope to get shipped feedback.
     *
     * @param  Builder<Feedback>  $query
     * @return Builder<Feedback>
     */
    public function scopeShipped(Builder $query): Builder
    {
        return $query->where('status', FeedbackStatus::SHIPPED);
    }

    /**
     * Scope to get open feedback.
     *
     * @param  Builder<Feedback>  $query
     * @return Builder<Feedback>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [FeedbackStatus::NEW, FeedbackStatus::UNDER_REVIEW]);
    }

    /**
     * Scope to get closed feedback.
     *
     * @param  Builder<Feedback>  $query
     * @return Builder<Feedback>
     */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->whereIn('status', [
            FeedbackStatus::SHIPPED,
            FeedbackStatus::DECLINED,
            FeedbackStatus::DUPLICATE,
            FeedbackStatus::ARCHIVED,
        ]);
    }

    /**
     * Scope to filter by feedback type.
     *
     * @param  Builder<Feedback>  $query
     * @return Builder<Feedback>
     */
    public function scopeByType(Builder $query, FeedbackType $type): Builder
    {
        return $query->where('feedback_type', $type);
    }

    /**
     * Scope to filter by category.
     *
     * @param  Builder<Feedback>  $query
     * @return Builder<Feedback>
     */
    public function scopeByCategory(Builder $query, FeedbackCategory $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to order by vote count descending.
     *
     * @param  Builder<Feedback>  $query
     * @return Builder<Feedback>
     */
    public function scopeTopVoted(Builder $query): Builder
    {
        return $query->orderByDesc('vote_count');
    }

    /**
     * Scope to filter by tenant.
     *
     * @param  Builder<Feedback>  $query
     * @return Builder<Feedback>
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to search feedback.
     *
     * @param  Builder<Feedback>  $query
     * @return Builder<Feedback>
     */
    public function scopeSearch(Builder $query, string $searchQuery): Builder
    {
        return $query->where(function (Builder $q) use ($searchQuery) {
            $q->where('title', 'like', "%{$searchQuery}%")
                ->orWhere('description', 'like', "%{$searchQuery}%");
        });
    }

    /**
     * Check if feedback is new.
     */
    public function isNew(): bool
    {
        return $this->status === FeedbackStatus::NEW;
    }

    /**
     * Check if feedback is open.
     */
    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    /**
     * Check if feedback is closed.
     */
    public function isClosed(): bool
    {
        return $this->status->isClosed();
    }

    /**
     * Register an upvote for this feedback.
     */
    public function upvote(?string $userId = null, ?string $sessionId = null): ?FeedbackVote
    {
        if ($userId && $this->hasVotedBy($userId)) {
            return null;
        }

        $vote = $this->votes()->create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'vote_type' => VoteType::UPVOTE,
        ]);

        $this->incrementVoteCount();

        return $vote;
    }

    /**
     * Check if user has voted on this feedback.
     */
    public function hasVotedBy(string $userId): bool
    {
        return $this->votes()->where('user_id', $userId)->exists();
    }

    /**
     * Mark as reviewed by an admin.
     */
    public function markAsReviewed(string $adminId): void
    {
        $this->reviewed_at = now();
        $this->reviewed_by = $adminId;

        if ($this->status === FeedbackStatus::NEW) {
            $this->status = FeedbackStatus::UNDER_REVIEW;
        }

        $this->save();
    }

    /**
     * Link to a roadmap item.
     */
    public function linkToRoadmap(RoadmapItem $roadmapItem): void
    {
        $this->roadmap_item_id = $roadmapItem->id;
        $this->status = FeedbackStatus::PLANNED;
        $this->save();

        $roadmapItem->recalculateCounts();
    }

    /**
     * Mark as duplicate of another feedback.
     */
    public function markAsDuplicate(Feedback $original): void
    {
        $this->duplicate_of_id = $original->id;
        $this->status = FeedbackStatus::DUPLICATE;
        $this->status_reason = "Duplicate of: {$original->title}";
        $this->save();
    }

    /**
     * Increment the vote count.
     */
    public function incrementVoteCount(): void
    {
        $this->increment('vote_count');
    }

    /**
     * Decrement the vote count.
     */
    public function decrementVoteCount(): void
    {
        $this->decrement('vote_count');
    }
}
