<?php

declare(strict_types=1);

namespace App\Models\KnowledgeBase;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * KBTag Model
 *
 * Represents a tag for classifying knowledge base articles.
 *
 * @property string $id UUID primary key
 * @property string $name Tag name
 * @property string $slug URL-friendly slug
 * @property int $usage_count Number of articles using this tag
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Collection<int, KBArticle> $articles
 *
 * @method static Builder<static> popular()
 * @method static Builder<static> ordered()
 * @method static Builder<static> search(string $query)
 */
final class KBTag extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'kb_tags';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
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
     * Get the articles that have this tag.
     *
     * @return BelongsToMany<KBArticle>
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(KBArticle::class, 'kb_article_tags', 'tag_id', 'article_id')
            ->withTimestamps();
    }

    /**
     * Scope to order by popularity (usage count).
     *
     * @param  Builder<KBTag>  $query
     * @return Builder<KBTag>
     */
    public function scopePopular(Builder $query): Builder
    {
        return $query->orderByDesc('usage_count');
    }

    /**
     * Scope to order alphabetically by name.
     *
     * @param  Builder<KBTag>  $query
     * @return Builder<KBTag>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    /**
     * Scope to search tags by name.
     *
     * @param  Builder<KBTag>  $query
     * @return Builder<KBTag>
     */
    public function scopeSearch(Builder $query, string $searchQuery): Builder
    {
        return $query->where('name', 'like', "%{$searchQuery}%")
            ->orWhere('slug', 'like', "%{$searchQuery}%");
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

    /**
     * Recalculate the usage count from actual article relationships.
     */
    public function recalculateUsageCount(): void
    {
        $this->usage_count = $this->articles()->count();
        $this->save();
    }
}
