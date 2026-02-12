<?php

declare(strict_types=1);

namespace App\Models\Feedback;

use App\Enums\Feedback\VoteType;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FeedbackVote Model
 *
 * Represents a vote on a feedback item.
 *
 * @property string $id UUID primary key
 * @property string $feedback_id Feedback UUID
 * @property string|null $user_id User UUID
 * @property string|null $tenant_id Tenant UUID
 * @property string|null $voter_email Voter email
 * @property string|null $session_id Session ID for anonymous votes
 * @property VoteType $vote_type Type of vote
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Feedback $feedback
 * @property-read User|null $user
 * @property-read Tenant|null $tenant
 *
 * @method static Builder<static> forFeedback(string $feedbackId)
 * @method static Builder<static> byUser(string $userId)
 * @method static Builder<static> upvotes()
 * @method static Builder<static> downvotes()
 */
final class FeedbackVote extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'feedback_votes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'feedback_id',
        'user_id',
        'tenant_id',
        'voter_email',
        'session_id',
        'vote_type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vote_type' => VoteType::class,
        ];
    }

    /**
     * Get the feedback item.
     *
     * @return BelongsTo<Feedback, FeedbackVote>
     */
    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class, 'feedback_id');
    }

    /**
     * Get the user who voted.
     *
     * @return BelongsTo<User, FeedbackVote>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the tenant.
     *
     * @return BelongsTo<Tenant, FeedbackVote>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Scope to filter by feedback.
     *
     * @param  Builder<FeedbackVote>  $query
     * @return Builder<FeedbackVote>
     */
    public function scopeForFeedback(Builder $query, string $feedbackId): Builder
    {
        return $query->where('feedback_id', $feedbackId);
    }

    /**
     * Scope to filter by user.
     *
     * @param  Builder<FeedbackVote>  $query
     * @return Builder<FeedbackVote>
     */
    public function scopeByUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get upvotes.
     *
     * @param  Builder<FeedbackVote>  $query
     * @return Builder<FeedbackVote>
     */
    public function scopeUpvotes(Builder $query): Builder
    {
        return $query->where('vote_type', VoteType::UPVOTE);
    }

    /**
     * Scope to get downvotes.
     *
     * @param  Builder<FeedbackVote>  $query
     * @return Builder<FeedbackVote>
     */
    public function scopeDownvotes(Builder $query): Builder
    {
        return $query->where('vote_type', VoteType::DOWNVOTE);
    }

    /**
     * Check if this is an upvote.
     */
    public function isUpvote(): bool
    {
        return $this->vote_type === VoteType::UPVOTE;
    }

    /**
     * Check if this is a downvote.
     */
    public function isDownvote(): bool
    {
        return $this->vote_type === VoteType::DOWNVOTE;
    }
}
