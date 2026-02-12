<?php

declare(strict_types=1);

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RssFeedItem Model
 *
 * Represents an item from an RSS feed.
 *
 * @property string $id UUID primary key
 * @property string $rss_feed_id RssFeed UUID
 * @property string $guid Item GUID
 * @property string $title Item title
 * @property string $link Item link
 * @property string|null $description Item description
 * @property string|null $image_url Item image URL
 * @property \Carbon\Carbon|null $published_at Item publish date
 * @property bool $is_used Whether item has been used
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read RssFeed $feed
 *
 * @method static Builder<static> unused()
 */
final class RssFeedItem extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'rss_feed_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'rss_feed_id',
        'guid',
        'title',
        'link',
        'description',
        'image_url',
        'published_at',
        'is_used',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_used' => 'boolean',
        ];
    }

    /**
     * Get the feed that this item belongs to.
     *
     * @return BelongsTo<RssFeed, RssFeedItem>
     */
    public function feed(): BelongsTo
    {
        return $this->belongsTo(RssFeed::class, 'rss_feed_id');
    }

    /**
     * Scope to get unused items.
     *
     * @param  Builder<RssFeedItem>  $query
     * @return Builder<RssFeedItem>
     */
    public function scopeUnused(Builder $query): Builder
    {
        return $query->where('is_used', false);
    }
}
