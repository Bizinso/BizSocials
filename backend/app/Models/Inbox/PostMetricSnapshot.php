<?php

declare(strict_types=1);

namespace App\Models\Inbox;

use App\Models\Content\PostTarget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PostMetricSnapshot Model
 *
 * Represents a periodic snapshot of engagement metrics for a published post.
 * Each snapshot captures the metrics at a specific point in time.
 *
 * @property string $id UUID primary key
 * @property string $post_target_id Post target UUID
 * @property \Carbon\Carbon $captured_at When the metrics were captured
 * @property int|null $likes_count Number of likes
 * @property int|null $comments_count Number of comments
 * @property int|null $shares_count Number of shares
 * @property int|null $impressions_count Number of impressions
 * @property int|null $reach_count Number of unique users reached
 * @property int|null $clicks_count Number of clicks
 * @property float|null $engagement_rate Calculated engagement rate
 * @property array|null $raw_response Raw API response
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read PostTarget $postTarget
 *
 * @method static Builder<static> forPostTarget(string $postTargetId)
 * @method static Builder<static> inDateRange(\DateTimeInterface $start, \DateTimeInterface $end)
 * @method static Builder<static> latestCaptured()
 */
final class PostMetricSnapshot extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'post_metric_snapshots';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_target_id',
        'captured_at',
        'likes_count',
        'comments_count',
        'shares_count',
        'impressions_count',
        'reach_count',
        'clicks_count',
        'engagement_rate',
        'raw_response',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'likes_count' => 'integer',
            'comments_count' => 'integer',
            'shares_count' => 'integer',
            'impressions_count' => 'integer',
            'reach_count' => 'integer',
            'clicks_count' => 'integer',
            'engagement_rate' => 'decimal:4',
            'raw_response' => 'array',
        ];
    }

    /**
     * Get the post target this snapshot belongs to.
     *
     * @return BelongsTo<PostTarget, PostMetricSnapshot>
     */
    public function postTarget(): BelongsTo
    {
        return $this->belongsTo(PostTarget::class);
    }

    /**
     * Scope to filter by post target.
     *
     * @param  Builder<PostMetricSnapshot>  $query
     * @return Builder<PostMetricSnapshot>
     */
    public function scopeForPostTarget(Builder $query, string $postTargetId): Builder
    {
        return $query->where('post_target_id', $postTargetId);
    }

    /**
     * Scope to filter by date range.
     *
     * @param  Builder<PostMetricSnapshot>  $query
     * @return Builder<PostMetricSnapshot>
     */
    public function scopeInDateRange(Builder $query, \DateTimeInterface $start, \DateTimeInterface $end): Builder
    {
        return $query->whereBetween('captured_at', [$start, $end]);
    }

    /**
     * Scope to order by most recently captured first.
     *
     * @param  Builder<PostMetricSnapshot>  $query
     * @return Builder<PostMetricSnapshot>
     */
    public function scopeLatestCaptured(Builder $query): Builder
    {
        return $query->orderByDesc('captured_at');
    }

    /**
     * Get total engagement (likes + comments + shares).
     */
    public function getTotalEngagement(): int
    {
        return ($this->likes_count ?? 0) + ($this->comments_count ?? 0) + ($this->shares_count ?? 0);
    }

    /**
     * Calculate engagement rate based on impressions.
     * Returns engagement rate as a percentage.
     */
    public function calculateEngagementRate(): ?float
    {
        if ($this->impressions_count === null || $this->impressions_count === 0) {
            return null;
        }

        $totalEngagement = $this->getTotalEngagement();

        return round(($totalEngagement / $this->impressions_count) * 100, 4);
    }
}
