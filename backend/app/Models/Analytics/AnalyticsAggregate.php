<?php

declare(strict_types=1);

namespace App\Models\Analytics;

use App\Enums\Analytics\PeriodType;
use App\Models\Social\SocialAccount;
use App\Models\Workspace\Workspace;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AnalyticsAggregate Model
 *
 * Represents aggregated analytics data for a workspace or social account.
 * Stores daily, weekly, or monthly aggregated metrics for performance tracking.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string|null $social_account_id Social account UUID (null for workspace totals)
 * @property \Carbon\Carbon $date Date of the aggregate
 * @property PeriodType $period_type Aggregation period (daily, weekly, monthly)
 * @property int $impressions Total impressions
 * @property int $reach Total unique reach
 * @property int $engagements Total engagements
 * @property int $likes Total likes
 * @property int $comments Total comments
 * @property int $shares Total shares
 * @property int $saves Total saves
 * @property int $clicks Total link clicks
 * @property int $video_views Total video views
 * @property int $posts_count Number of posts
 * @property float $engagement_rate Engagement rate percentage
 * @property int $followers_start Followers at period start
 * @property int $followers_end Followers at period end
 * @property int $followers_change Net follower change
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 * @property-read SocialAccount|null $socialAccount
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> forSocialAccount(string $socialAccountId)
 * @method static Builder<static> forPeriod(PeriodType $periodType)
 * @method static Builder<static> inDateRange(\Carbon\Carbon $start, \Carbon\Carbon $end)
 * @method static Builder<static> daily()
 * @method static Builder<static> weekly()
 * @method static Builder<static> monthly()
 * @method static Builder<static> workspaceTotals()
 */
final class AnalyticsAggregate extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'analytics_aggregates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'social_account_id',
        'date',
        'period_type',
        'impressions',
        'reach',
        'engagements',
        'likes',
        'comments',
        'shares',
        'saves',
        'clicks',
        'video_views',
        'posts_count',
        'engagement_rate',
        'followers_start',
        'followers_end',
        'followers_change',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'period_type' => PeriodType::class,
            'impressions' => 'integer',
            'reach' => 'integer',
            'engagements' => 'integer',
            'likes' => 'integer',
            'comments' => 'integer',
            'shares' => 'integer',
            'saves' => 'integer',
            'clicks' => 'integer',
            'video_views' => 'integer',
            'posts_count' => 'integer',
            'engagement_rate' => 'float',
            'followers_start' => 'integer',
            'followers_end' => 'integer',
            'followers_change' => 'integer',
        ];
    }

    /**
     * Get the workspace that this aggregate belongs to.
     *
     * @return BelongsTo<Workspace, AnalyticsAggregate>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the social account that this aggregate belongs to.
     *
     * @return BelongsTo<SocialAccount, AnalyticsAggregate>
     */
    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<AnalyticsAggregate>  $query
     * @return Builder<AnalyticsAggregate>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to filter by social account.
     *
     * @param  Builder<AnalyticsAggregate>  $query
     * @return Builder<AnalyticsAggregate>
     */
    public function scopeForSocialAccount(Builder $query, string $socialAccountId): Builder
    {
        return $query->where('social_account_id', $socialAccountId);
    }

    /**
     * Scope to filter by period type.
     *
     * @param  Builder<AnalyticsAggregate>  $query
     * @return Builder<AnalyticsAggregate>
     */
    public function scopeForPeriod(Builder $query, PeriodType $periodType): Builder
    {
        return $query->where('period_type', $periodType);
    }

    /**
     * Scope to filter by date range.
     *
     * @param  Builder<AnalyticsAggregate>  $query
     * @return Builder<AnalyticsAggregate>
     */
    public function scopeInDateRange(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
    }

    /**
     * Scope to get daily aggregates.
     *
     * @param  Builder<AnalyticsAggregate>  $query
     * @return Builder<AnalyticsAggregate>
     */
    public function scopeDaily(Builder $query): Builder
    {
        return $query->where('period_type', PeriodType::DAILY);
    }

    /**
     * Scope to get weekly aggregates.
     *
     * @param  Builder<AnalyticsAggregate>  $query
     * @return Builder<AnalyticsAggregate>
     */
    public function scopeWeekly(Builder $query): Builder
    {
        return $query->where('period_type', PeriodType::WEEKLY);
    }

    /**
     * Scope to get monthly aggregates.
     *
     * @param  Builder<AnalyticsAggregate>  $query
     * @return Builder<AnalyticsAggregate>
     */
    public function scopeMonthly(Builder $query): Builder
    {
        return $query->where('period_type', PeriodType::MONTHLY);
    }

    /**
     * Scope to get workspace-level totals (no specific social account).
     *
     * @param  Builder<AnalyticsAggregate>  $query
     * @return Builder<AnalyticsAggregate>
     */
    public function scopeWorkspaceTotals(Builder $query): Builder
    {
        return $query->whereNull('social_account_id');
    }

    /**
     * Check if this is a workspace-level aggregate.
     */
    public function isWorkspaceTotal(): bool
    {
        return $this->social_account_id === null;
    }

    /**
     * Check if this is a daily aggregate.
     */
    public function isDaily(): bool
    {
        return $this->period_type === PeriodType::DAILY;
    }

    /**
     * Check if this is a weekly aggregate.
     */
    public function isWeekly(): bool
    {
        return $this->period_type === PeriodType::WEEKLY;
    }

    /**
     * Check if this is a monthly aggregate.
     */
    public function isMonthly(): bool
    {
        return $this->period_type === PeriodType::MONTHLY;
    }

    /**
     * Get total engagements (likes + comments + shares + saves).
     */
    public function getTotalEngagements(): int
    {
        return $this->likes + $this->comments + $this->shares + $this->saves;
    }

    /**
     * Get the follower growth rate as a percentage.
     */
    public function getFollowerGrowthRate(): float
    {
        if ($this->followers_start === 0) {
            return 0.0;
        }

        return ($this->followers_change / $this->followers_start) * 100;
    }

    /**
     * Calculate engagement rate from metrics.
     * Returns engagement rate as a percentage.
     */
    public function calculateEngagementRate(): float
    {
        if ($this->reach === 0) {
            return 0.0;
        }

        return ($this->engagements / $this->reach) * 100;
    }

    /**
     * Get the click-through rate (CTR) as a percentage.
     */
    public function getClickThroughRate(): float
    {
        if ($this->impressions === 0) {
            return 0.0;
        }

        return ($this->clicks / $this->impressions) * 100;
    }

    /**
     * Get average engagements per post.
     */
    public function getAverageEngagementsPerPost(): float
    {
        if ($this->posts_count === 0) {
            return 0.0;
        }

        return $this->engagements / $this->posts_count;
    }

    /**
     * Check if follower count increased.
     */
    public function hasFollowerGrowth(): bool
    {
        return $this->followers_change > 0;
    }

    /**
     * Check if follower count decreased.
     */
    public function hasFollowerDecline(): bool
    {
        return $this->followers_change < 0;
    }

    /**
     * Get metrics as an array for API responses.
     *
     * @return array<string, mixed>
     */
    public function toMetricsArray(): array
    {
        return [
            'impressions' => $this->impressions,
            'reach' => $this->reach,
            'engagements' => $this->engagements,
            'likes' => $this->likes,
            'comments' => $this->comments,
            'shares' => $this->shares,
            'saves' => $this->saves,
            'clicks' => $this->clicks,
            'video_views' => $this->video_views,
            'posts_count' => $this->posts_count,
            'engagement_rate' => $this->engagement_rate,
            'followers_start' => $this->followers_start,
            'followers_end' => $this->followers_end,
            'followers_change' => $this->followers_change,
            'follower_growth_rate' => $this->getFollowerGrowthRate(),
            'click_through_rate' => $this->getClickThroughRate(),
            'avg_engagements_per_post' => $this->getAverageEngagementsPerPost(),
        ];
    }

    /**
     * Create or update an aggregate record.
     *
     * @param  array<string, mixed>  $metrics
     */
    public static function upsertAggregate(
        string $workspaceId,
        ?string $socialAccountId,
        Carbon $date,
        PeriodType $periodType,
        array $metrics
    ): static {
        // Build query manually to handle NULL comparison properly
        // SQL NULL = NULL returns NULL (not TRUE), so we use whereNull for null values
        $dateString = $date->toDateString();

        $query = static::query()
            ->where('workspace_id', $workspaceId)
            ->whereDate('date', $dateString)
            ->where('period_type', $periodType->value);

        if ($socialAccountId === null) {
            $query->whereNull('social_account_id');
        } else {
            $query->where('social_account_id', $socialAccountId);
        }

        $existing = $query->first();

        if ($existing !== null) {
            $existing->update($metrics);

            return $existing;
        }

        return static::create([
            'workspace_id' => $workspaceId,
            'social_account_id' => $socialAccountId,
            'date' => $dateString,
            'period_type' => $periodType,
            ...$metrics,
        ]);
    }
}
