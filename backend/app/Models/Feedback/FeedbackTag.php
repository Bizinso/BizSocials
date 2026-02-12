<?php

declare(strict_types=1);

namespace App\Models\Feedback;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * FeedbackTag Model
 *
 * Represents a tag for categorizing feedback.
 *
 * @property string $id UUID primary key
 * @property string $name Tag name
 * @property string $slug URL-friendly slug
 * @property string $color Hex color code
 * @property string|null $description Tag description
 * @property int $usage_count Number of feedback items with this tag
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Collection<int, Feedback> $feedback
 *
 * @method static Builder<static> popular()
 * @method static Builder<static> ordered()
 * @method static Builder<static> search(string $query)
 */
final class FeedbackTag extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'feedback_tags';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
        'usage_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'usage_count' => 'integer',
        ];
    }

    /**
     * Get feedback items with this tag.
     *
     * @return BelongsToMany<Feedback>
     */
    public function feedback(): BelongsToMany
    {
        return $this->belongsToMany(Feedback::class, 'feedback_tag_assignments', 'tag_id', 'feedback_id')
            ->withTimestamps();
    }

    /**
     * Scope to order by usage count descending.
     *
     * @param  Builder<FeedbackTag>  $query
     * @return Builder<FeedbackTag>
     */
    public function scopePopular(Builder $query): Builder
    {
        return $query->orderByDesc('usage_count');
    }

    /**
     * Scope to order by name.
     *
     * @param  Builder<FeedbackTag>  $query
     * @return Builder<FeedbackTag>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    /**
     * Scope to search tags.
     *
     * @param  Builder<FeedbackTag>  $query
     * @return Builder<FeedbackTag>
     */
    public function scopeSearch(Builder $query, string $searchQuery): Builder
    {
        return $query->where('name', 'like', "%{$searchQuery}%");
    }

    /**
     * Increment the usage count.
     */
    public function incrementUsageCount(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Decrement the usage count.
     */
    public function decrementUsageCount(): void
    {
        if ($this->usage_count > 0) {
            $this->decrement('usage_count');
        }
    }
}
