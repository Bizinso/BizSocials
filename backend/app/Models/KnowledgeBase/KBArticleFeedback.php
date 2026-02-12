<?php

declare(strict_types=1);

namespace App\Models\KnowledgeBase;

use App\Enums\KnowledgeBase\KBFeedbackCategory;
use App\Enums\KnowledgeBase\KBFeedbackStatus;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * KBArticleFeedback Model
 *
 * Represents user feedback on knowledge base articles.
 *
 * @property string $id UUID primary key
 * @property string $article_id Article UUID
 * @property bool $is_helpful Whether the article was helpful
 * @property string|null $feedback_text Additional feedback text
 * @property KBFeedbackCategory|null $feedback_category Category of feedback
 * @property string|null $user_id User UUID who gave feedback
 * @property string|null $tenant_id Tenant UUID context
 * @property string|null $session_id Session ID for anonymous tracking
 * @property string|null $ip_address IP address
 * @property KBFeedbackStatus $status Feedback processing status
 * @property string|null $reviewed_by SuperAdminUser UUID who reviewed
 * @property \Carbon\Carbon|null $reviewed_at When reviewed
 * @property string|null $admin_notes Admin notes on feedback
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read KBArticle $article
 * @property-read User|null $user
 * @property-read Tenant|null $tenant
 * @property-read SuperAdminUser|null $reviewedBy
 *
 * @method static Builder<static> pending()
 * @method static Builder<static> reviewed()
 * @method static Builder<static> helpful()
 * @method static Builder<static> notHelpful()
 * @method static Builder<static> forArticle(string $articleId)
 * @method static Builder<static> withCategory(KBFeedbackCategory $category)
 */
final class KBArticleFeedback extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'kb_article_feedback';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'article_id',
        'is_helpful',
        'feedback_text',
        'feedback_category',
        'user_id',
        'tenant_id',
        'session_id',
        'ip_address',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_helpful' => 'boolean',
            'feedback_category' => KBFeedbackCategory::class,
            'status' => KBFeedbackStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Get the article this feedback is for.
     *
     * @return BelongsTo<KBArticle, KBArticleFeedback>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(KBArticle::class, 'article_id');
    }

    /**
     * Get the user who gave this feedback.
     *
     * @return BelongsTo<User, KBArticleFeedback>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the tenant context.
     *
     * @return BelongsTo<Tenant, KBArticleFeedback>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the admin who reviewed this feedback.
     *
     * @return BelongsTo<SuperAdminUser, KBArticleFeedback>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(SuperAdminUser::class, 'reviewed_by');
    }

    /**
     * Scope to get only pending feedback.
     *
     * @param  Builder<KBArticleFeedback>  $query
     * @return Builder<KBArticleFeedback>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', KBFeedbackStatus::PENDING);
    }

    /**
     * Scope to get only reviewed feedback.
     *
     * @param  Builder<KBArticleFeedback>  $query
     * @return Builder<KBArticleFeedback>
     */
    public function scopeReviewed(Builder $query): Builder
    {
        return $query->whereIn('status', [
            KBFeedbackStatus::REVIEWED,
            KBFeedbackStatus::ACTIONED,
            KBFeedbackStatus::DISMISSED,
        ]);
    }

    /**
     * Scope to get only helpful feedback.
     *
     * @param  Builder<KBArticleFeedback>  $query
     * @return Builder<KBArticleFeedback>
     */
    public function scopeHelpful(Builder $query): Builder
    {
        return $query->where('is_helpful', true);
    }

    /**
     * Scope to get only not helpful feedback.
     *
     * @param  Builder<KBArticleFeedback>  $query
     * @return Builder<KBArticleFeedback>
     */
    public function scopeNotHelpful(Builder $query): Builder
    {
        return $query->where('is_helpful', false);
    }

    /**
     * Scope to filter by article.
     *
     * @param  Builder<KBArticleFeedback>  $query
     * @return Builder<KBArticleFeedback>
     */
    public function scopeForArticle(Builder $query, string $articleId): Builder
    {
        return $query->where('article_id', $articleId);
    }

    /**
     * Scope to filter by feedback category.
     *
     * @param  Builder<KBArticleFeedback>  $query
     * @return Builder<KBArticleFeedback>
     */
    public function scopeWithCategory(Builder $query, KBFeedbackCategory $category): Builder
    {
        return $query->where('feedback_category', $category);
    }

    /**
     * Mark the feedback as reviewed.
     */
    public function markAsReviewed(string $reviewedById, ?string $adminNotes = null): void
    {
        $this->status = KBFeedbackStatus::REVIEWED;
        $this->reviewed_by = $reviewedById;
        $this->reviewed_at = now();
        $this->admin_notes = $adminNotes;
        $this->save();
    }

    /**
     * Mark the feedback as actioned.
     */
    public function markAsActioned(string $reviewedById, ?string $adminNotes = null): void
    {
        $this->status = KBFeedbackStatus::ACTIONED;
        $this->reviewed_by = $reviewedById;
        $this->reviewed_at = now();
        $this->admin_notes = $adminNotes;
        $this->save();
    }

    /**
     * Dismiss the feedback.
     */
    public function dismiss(string $reviewedById, ?string $adminNotes = null): void
    {
        $this->status = KBFeedbackStatus::DISMISSED;
        $this->reviewed_by = $reviewedById;
        $this->reviewed_at = now();
        $this->admin_notes = $adminNotes;
        $this->save();
    }

    /**
     * Check if the feedback is pending.
     */
    public function isPending(): bool
    {
        return $this->status === KBFeedbackStatus::PENDING;
    }

    /**
     * Check if this is positive feedback.
     */
    public function isPositive(): bool
    {
        return $this->is_helpful ||
            ($this->feedback_category !== null && $this->feedback_category->isPositive());
    }
}
