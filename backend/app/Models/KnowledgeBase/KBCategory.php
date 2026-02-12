<?php

declare(strict_types=1);

namespace App\Models\KnowledgeBase;

use App\Enums\KnowledgeBase\KBVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * KBCategory Model
 *
 * Represents a knowledge base category for organizing articles.
 * Supports hierarchical structure with parent/child relationships.
 *
 * @property string $id UUID primary key
 * @property string|null $parent_id Parent category UUID
 * @property string $name Category name
 * @property string $slug URL-friendly slug
 * @property string|null $description Category description
 * @property string|null $icon Icon name
 * @property string|null $color Hex color code
 * @property bool $is_public Whether publicly visible
 * @property KBVisibility $visibility Visibility level
 * @property array|null $allowed_plans Plan IDs for SPECIFIC_PLANS visibility
 * @property int $sort_order Sort order for display
 * @property int $article_count Cached count of articles
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read KBCategory|null $parent
 * @property-read Collection<int, KBCategory> $children
 * @property-read Collection<int, KBArticle> $articles
 *
 * @method static Builder<static> published()
 * @method static Builder<static> topLevel()
 * @method static Builder<static> withPublishedArticles()
 * @method static Builder<static> ordered()
 */
final class KBCategory extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'kb_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'is_public',
        'visibility',
        'allowed_plans',
        'sort_order',
        'article_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'visibility' => KBVisibility::class,
            'allowed_plans' => 'array',
            'sort_order' => 'integer',
            'article_count' => 'integer',
        ];
    }

    /**
     * Get the parent category.
     *
     * @return BelongsTo<KBCategory, KBCategory>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(KBCategory::class, 'parent_id');
    }

    /**
     * Get the child categories.
     *
     * @return HasMany<KBCategory>
     */
    public function children(): HasMany
    {
        return $this->hasMany(KBCategory::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Get the articles in this category.
     *
     * @return HasMany<KBArticle>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(KBArticle::class, 'category_id');
    }

    /**
     * Scope to get only public categories.
     *
     * @param  Builder<KBCategory>  $query
     * @return Builder<KBCategory>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get only top-level categories (no parent).
     *
     * @param  Builder<KBCategory>  $query
     * @return Builder<KBCategory>
     */
    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to include only categories with published articles.
     *
     * @param  Builder<KBCategory>  $query
     * @return Builder<KBCategory>
     */
    public function scopeWithPublishedArticles(Builder $query): Builder
    {
        return $query->whereHas('articles', function (Builder $q) {
            $q->where('status', 'published');
        });
    }

    /**
     * Scope to order categories by sort order.
     *
     * @param  Builder<KBCategory>  $query
     * @return Builder<KBCategory>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Check if this is a top-level category.
     */
    public function isTopLevel(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Check if this category has children.
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Check if this category has articles.
     */
    public function hasArticles(): bool
    {
        return $this->article_count > 0 || $this->articles()->exists();
    }

    /**
     * Get the full path of categories from root to this one.
     *
     * @return Collection<int, KBCategory>
     */
    public function getPath(): Collection
    {
        $path = new Collection();
        $current = $this;

        while ($current !== null) {
            $path->prepend($current);
            $current = $current->parent;
        }

        return $path;
    }

    /**
     * Get the depth of this category in the hierarchy.
     */
    public function getDepth(): int
    {
        $depth = 0;
        $current = $this->parent;

        while ($current !== null) {
            $depth++;
            $current = $current->parent;
        }

        return $depth;
    }

    /**
     * Increment the article count.
     */
    public function incrementArticleCount(): void
    {
        $this->increment('article_count');
    }

    /**
     * Decrement the article count.
     */
    public function decrementArticleCount(): void
    {
        if ($this->article_count > 0) {
            $this->decrement('article_count');
        }
    }

    /**
     * Get the full slug path from root to this category.
     */
    public function getFullSlugPath(): string
    {
        $path = $this->getPath();

        return $path->pluck('slug')->implode('/');
    }
}
