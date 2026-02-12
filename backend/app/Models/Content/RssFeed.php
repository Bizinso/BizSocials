<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * RssFeed Model
 *
 * Represents an RSS feed for content curation.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string $url RSS feed URL
 * @property string $name Feed name
 * @property bool $is_active Active status
 * @property bool $auto_schedule Auto-schedule items
 * @property string|null $category_id Content category UUID
 * @property \Carbon\Carbon|null $last_fetched_at Last fetch timestamp
 * @property int $fetch_interval_hours Fetch interval in hours
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 * @property-read ContentCategory|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, RssFeedItem> $items
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> active()
 * @method static Builder<static> needsFetch()
 */
final class RssFeed extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'rss_feeds';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'url',
        'name',
        'is_active',
        'auto_schedule',
        'category_id',
        'last_fetched_at',
        'fetch_interval_hours',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'auto_schedule' => 'boolean',
            'last_fetched_at' => 'datetime',
            'fetch_interval_hours' => 'integer',
        ];
    }

    /**
     * Get the workspace that this feed belongs to.
     *
     * @return BelongsTo<Workspace, RssFeed>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the category for this feed.
     *
     * @return BelongsTo<ContentCategory, RssFeed>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ContentCategory::class, 'category_id');
    }

    /**
     * Get the items from this feed.
     *
     * @return HasMany<RssFeedItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(RssFeedItem::class);
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<RssFeed>  $query
     * @return Builder<RssFeed>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to filter active feeds.
     *
     * @param  Builder<RssFeed>  $query
     * @return Builder<RssFeed>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get feeds that need fetching.
     *
     * @param  Builder<RssFeed>  $query
     * @return Builder<RssFeed>
     */
    public function scopeNeedsFetch(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereNull('last_fetched_at')
                ->orWhereRaw('last_fetched_at < NOW() - INTERVAL fetch_interval_hours HOUR');
        });
    }
}
