<?php

declare(strict_types=1);

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * EvergreenPostPool Model
 *
 * Represents a post in the evergreen content pool.
 *
 * @property string $id UUID primary key
 * @property string $evergreen_rule_id EvergreenRule UUID
 * @property string $post_id Post UUID
 * @property int $repost_count Number of times reposted
 * @property \Carbon\Carbon|null $next_repost_at Next scheduled repost
 * @property bool $is_active Active status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read EvergreenRule $rule
 * @property-read Post $post
 *
 * @method static Builder<static> active()
 * @method static Builder<static> dueForRepost()
 */
final class EvergreenPostPool extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'evergreen_post_pool';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'evergreen_rule_id',
        'post_id',
        'repost_count',
        'next_repost_at',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'repost_count' => 'integer',
            'next_repost_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the rule that this pool entry belongs to.
     *
     * @return BelongsTo<EvergreenRule, EvergreenPostPool>
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(EvergreenRule::class, 'evergreen_rule_id');
    }

    /**
     * Get the post for this pool entry.
     *
     * @return BelongsTo<Post, EvergreenPostPool>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Scope to filter active pool entries.
     *
     * @param  Builder<EvergreenPostPool>  $query
     * @return Builder<EvergreenPostPool>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get entries due for repost.
     *
     * @param  Builder<EvergreenPostPool>  $query
     * @return Builder<EvergreenPostPool>
     */
    public function scopeDueForRepost(Builder $query): Builder
    {
        return $query->where('next_repost_at', '<=', now());
    }
}
