<?php

declare(strict_types=1);

namespace App\Models\Feedback;

use App\Models\Platform\SuperAdminUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FeedbackComment Model
 *
 * Represents a comment on a feedback item.
 *
 * @property string $id UUID primary key
 * @property string $feedback_id Feedback UUID
 * @property string|null $user_id User UUID
 * @property string|null $admin_id Admin UUID
 * @property string|null $commenter_name Commenter name
 * @property string $content Comment content
 * @property bool $is_internal Internal comment flag
 * @property bool $is_official_response Official response flag
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Feedback $feedback
 * @property-read User|null $user
 * @property-read SuperAdminUser|null $admin
 *
 * @method static Builder<static> forFeedback(string $feedbackId)
 * @method static Builder<static> public()
 * @method static Builder<static> internal()
 * @method static Builder<static> official()
 */
final class FeedbackComment extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'feedback_comments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'feedback_id',
        'user_id',
        'admin_id',
        'commenter_name',
        'content',
        'is_internal',
        'is_official_response',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
            'is_official_response' => 'boolean',
        ];
    }

    /**
     * Get the feedback item.
     *
     * @return BelongsTo<Feedback, FeedbackComment>
     */
    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class, 'feedback_id');
    }

    /**
     * Get the user who commented.
     *
     * @return BelongsTo<User, FeedbackComment>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the admin who commented.
     *
     * @return BelongsTo<SuperAdminUser, FeedbackComment>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(SuperAdminUser::class, 'admin_id');
    }

    /**
     * Scope to filter by feedback.
     *
     * @param  Builder<FeedbackComment>  $query
     * @return Builder<FeedbackComment>
     */
    public function scopeForFeedback(Builder $query, string $feedbackId): Builder
    {
        return $query->where('feedback_id', $feedbackId);
    }

    /**
     * Scope to get public comments only.
     *
     * @param  Builder<FeedbackComment>  $query
     * @return Builder<FeedbackComment>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_internal', false);
    }

    /**
     * Scope to get internal comments only.
     *
     * @param  Builder<FeedbackComment>  $query
     * @return Builder<FeedbackComment>
     */
    public function scopeInternal(Builder $query): Builder
    {
        return $query->where('is_internal', true);
    }

    /**
     * Scope to get official responses only.
     *
     * @param  Builder<FeedbackComment>  $query
     * @return Builder<FeedbackComment>
     */
    public function scopeOfficial(Builder $query): Builder
    {
        return $query->where('is_official_response', true);
    }

    /**
     * Check if this is an internal comment.
     */
    public function isInternal(): bool
    {
        return $this->is_internal;
    }

    /**
     * Check if this is an official response.
     */
    public function isOfficial(): bool
    {
        return $this->is_official_response;
    }

    /**
     * Get the author name.
     */
    public function getAuthorName(): string
    {
        if ($this->admin) {
            return $this->admin->name;
        }

        if ($this->user) {
            return $this->user->name;
        }

        return $this->commenter_name ?? 'Anonymous';
    }
}
