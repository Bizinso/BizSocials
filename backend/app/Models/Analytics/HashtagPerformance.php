<?php

declare(strict_types=1);

namespace App\Models\Analytics;

use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HashtagPerformance Model
 *
 * Tracks hashtag usage and performance metrics per workspace and platform.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string $hashtag The hashtag string
 * @property string $platform Platform identifier
 * @property int $usage_count Number of times the hashtag has been used
 * @property float $avg_reach Average reach per usage
 * @property float $avg_engagement Average engagement per usage
 * @property float $avg_impressions Average impressions per usage
 * @property \Carbon\Carbon|null $last_used_at When the hashtag was last used
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> forPlatform(string $platform)
 * @method static Builder<static> topPerforming(int $limit)
 */
final class HashtagPerformance extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hashtag_performance';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'hashtag',
        'platform',
        'usage_count',
        'avg_reach',
        'avg_engagement',
        'avg_impressions',
        'last_used_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'avg_reach' => 'decimal:2',
            'avg_engagement' => 'decimal:2',
            'avg_impressions' => 'decimal:2',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Get the workspace that this hashtag performance belongs to.
     *
     * @return BelongsTo<Workspace, HashtagPerformance>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<HashtagPerformance>  $query
     * @return Builder<HashtagPerformance>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to filter by platform.
     *
     * @param  Builder<HashtagPerformance>  $query
     * @return Builder<HashtagPerformance>
     */
    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope to get top performing hashtags by engagement.
     *
     * @param  Builder<HashtagPerformance>  $query
     * @return Builder<HashtagPerformance>
     */
    public function scopeTopPerforming(Builder $query, int $limit = 20): Builder
    {
        return $query->orderByDesc('avg_engagement')->limit($limit);
    }
}
