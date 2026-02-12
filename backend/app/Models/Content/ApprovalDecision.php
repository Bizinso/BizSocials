<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Enums\Content\ApprovalDecisionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ApprovalDecision Model
 *
 * Represents an approval or rejection decision for a post.
 * Maintains an audit trail of all decisions with support for active/inactive states.
 *
 * @property string $id UUID primary key
 * @property string $post_id Post UUID
 * @property string $decided_by_user_id User who made the decision
 * @property ApprovalDecisionType $decision Decision type
 * @property string|null $comment Optional comment
 * @property bool $is_active Whether this is the current active decision
 * @property \Carbon\Carbon $decided_at When the decision was made
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Post $post
 * @property-read User $decidedBy
 *
 * @method static Builder<static> active()
 * @method static Builder<static> forPost(string $postId)
 */
final class ApprovalDecision extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'approval_decisions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'decided_by_user_id',
        'decision',
        'comment',
        'is_active',
        'decided_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decision' => ApprovalDecisionType::class,
            'is_active' => 'boolean',
            'decided_at' => 'datetime',
        ];
    }

    /**
     * Get the post this decision belongs to.
     *
     * @return BelongsTo<Post, ApprovalDecision>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the user who made this decision.
     *
     * @return BelongsTo<User, ApprovalDecision>
     */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }

    /**
     * Scope to get only active decisions.
     *
     * @param  Builder<ApprovalDecision>  $query
     * @return Builder<ApprovalDecision>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by post.
     *
     * @param  Builder<ApprovalDecision>  $query
     * @return Builder<ApprovalDecision>
     */
    public function scopeForPost(Builder $query, string $postId): Builder
    {
        return $query->where('post_id', $postId);
    }

    /**
     * Check if this is an approval decision.
     */
    public function isApproved(): bool
    {
        return $this->decision === ApprovalDecisionType::APPROVED;
    }

    /**
     * Check if this is a rejection decision.
     */
    public function isRejected(): bool
    {
        return $this->decision === ApprovalDecisionType::REJECTED;
    }

    /**
     * Deactivate this decision.
     */
    public function deactivate(): void
    {
        $this->is_active = false;
        $this->save();
    }
}
