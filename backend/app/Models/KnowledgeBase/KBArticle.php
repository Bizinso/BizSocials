<?php

declare(strict_types=1);

namespace App\Models\KnowledgeBase;

use App\Enums\KnowledgeBase\KBArticleStatus;
use App\Enums\KnowledgeBase\KBArticleType;
use App\Enums\KnowledgeBase\KBContentFormat;
use App\Enums\KnowledgeBase\KBDifficultyLevel;
use App\Enums\KnowledgeBase\KBRelationType;
use App\Enums\KnowledgeBase\KBVisibility;
use App\Models\Platform\SuperAdminUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * KBArticle Model
 *
 * Represents a knowledge base article with versioning support.
 *
 * @property string $id UUID primary key
 * @property string $category_id Category UUID
 * @property string $title Article title
 * @property string $slug URL-friendly slug
 * @property string|null $excerpt Short description
 * @property string $content Article content
 * @property KBContentFormat $content_format Content format type
 * @property string|null $featured_image Featured image URL
 * @property string|null $video_url Video URL
 * @property int|null $video_duration Video duration in seconds
 * @property KBArticleType $article_type Type of article
 * @property KBDifficultyLevel $difficulty_level Difficulty level
 * @property KBArticleStatus $status Publication status
 * @property bool $is_featured Whether featured
 * @property bool $is_public Whether publicly visible
 * @property KBVisibility $visibility Visibility level
 * @property array|null $allowed_plans Plan IDs for visibility
 * @property string|null $meta_title SEO title
 * @property string|null $meta_description SEO description
 * @property array|null $meta_keywords SEO keywords
 * @property int $version Current version number
 * @property string $author_id Author UUID (SuperAdminUser)
 * @property string|null $last_edited_by Last editor UUID
 * @property int $view_count View count
 * @property int $helpful_count Helpful vote count
 * @property int $not_helpful_count Not helpful vote count
 * @property \Carbon\Carbon|null $published_at Publication date
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read KBCategory $category
 * @property-read SuperAdminUser $author
 * @property-read SuperAdminUser|null $lastEditedBy
 * @property-read Collection<int, KBTag> $tags
 * @property-read Collection<int, KBArticle> $relatedArticles
 * @property-read Collection<int, KBArticle> $prerequisiteArticles
 * @property-read Collection<int, KBArticle> $nextStepArticles
 * @property-read Collection<int, KBArticleFeedback> $feedback
 * @property-read Collection<int, KBArticleVersion> $versions
 *
 * @method static Builder<static> published()
 * @method static Builder<static> draft()
 * @method static Builder<static> archived()
 * @method static Builder<static> featured()
 * @method static Builder<static> forCategory(string $categoryId)
 * @method static Builder<static> ofType(KBArticleType $type)
 * @method static Builder<static> withDifficulty(KBDifficultyLevel $level)
 * @method static Builder<static> searchable()
 * @method static Builder<static> popular()
 */
final class KBArticle extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'kb_articles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'content_format',
        'featured_image',
        'video_url',
        'video_duration',
        'article_type',
        'difficulty_level',
        'status',
        'is_featured',
        'is_public',
        'visibility',
        'allowed_plans',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'version',
        'author_id',
        'last_edited_by',
        'view_count',
        'helpful_count',
        'not_helpful_count',
        'published_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content_format' => KBContentFormat::class,
            'article_type' => KBArticleType::class,
            'difficulty_level' => KBDifficultyLevel::class,
            'status' => KBArticleStatus::class,
            'visibility' => KBVisibility::class,
            'is_featured' => 'boolean',
            'is_public' => 'boolean',
            'allowed_plans' => 'array',
            'meta_keywords' => 'array',
            'version' => 'integer',
            'video_duration' => 'integer',
            'view_count' => 'integer',
            'helpful_count' => 'integer',
            'not_helpful_count' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Get the category this article belongs to.
     *
     * @return BelongsTo<KBCategory, KBArticle>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(KBCategory::class, 'category_id');
    }

    /**
     * Get the author of this article.
     *
     * @return BelongsTo<SuperAdminUser, KBArticle>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(SuperAdminUser::class, 'author_id');
    }

    /**
     * Get the user who last edited this article.
     *
     * @return BelongsTo<SuperAdminUser, KBArticle>
     */
    public function lastEditedBy(): BelongsTo
    {
        return $this->belongsTo(SuperAdminUser::class, 'last_edited_by');
    }

    /**
     * Get the tags associated with this article.
     *
     * @return BelongsToMany<KBTag>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(KBTag::class, 'kb_article_tags', 'article_id', 'tag_id')
            ->withTimestamps();
    }

    /**
     * Get all related articles.
     *
     * @return BelongsToMany<KBArticle>
     */
    public function relatedArticles(): BelongsToMany
    {
        return $this->belongsToMany(KBArticle::class, 'kb_article_relations', 'article_id', 'related_article_id')
            ->withPivot(['relation_type', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Get prerequisite articles.
     *
     * @return BelongsToMany<KBArticle>
     */
    public function prerequisiteArticles(): BelongsToMany
    {
        return $this->belongsToMany(KBArticle::class, 'kb_article_relations', 'article_id', 'related_article_id')
            ->withPivot(['relation_type', 'sort_order'])
            ->withTimestamps()
            ->wherePivot('relation_type', KBRelationType::PREREQUISITE->value)
            ->orderByPivot('sort_order');
    }

    /**
     * Get next step articles.
     *
     * @return BelongsToMany<KBArticle>
     */
    public function nextStepArticles(): BelongsToMany
    {
        return $this->belongsToMany(KBArticle::class, 'kb_article_relations', 'article_id', 'related_article_id')
            ->withPivot(['relation_type', 'sort_order'])
            ->withTimestamps()
            ->wherePivot('relation_type', KBRelationType::NEXT_STEP->value)
            ->orderByPivot('sort_order');
    }

    /**
     * Get feedback for this article.
     *
     * @return HasMany<KBArticleFeedback>
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(KBArticleFeedback::class, 'article_id');
    }

    /**
     * Get version history for this article.
     *
     * @return HasMany<KBArticleVersion>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(KBArticleVersion::class, 'article_id')->orderByDesc('version');
    }

    /**
     * Scope to get only published articles.
     *
     * @param  Builder<KBArticle>  $query
     * @return Builder<KBArticle>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', KBArticleStatus::PUBLISHED);
    }

    /**
     * Scope to get only draft articles.
     *
     * @param  Builder<KBArticle>  $query
     * @return Builder<KBArticle>
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', KBArticleStatus::DRAFT);
    }

    /**
     * Scope to get only archived articles.
     *
     * @param  Builder<KBArticle>  $query
     * @return Builder<KBArticle>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', KBArticleStatus::ARCHIVED);
    }

    /**
     * Scope to get only featured articles.
     *
     * @param  Builder<KBArticle>  $query
     * @return Builder<KBArticle>
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to filter by category.
     *
     * @param  Builder<KBArticle>  $query
     * @return Builder<KBArticle>
     */
    public function scopeForCategory(Builder $query, string $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope to filter by article type.
     *
     * @param  Builder<KBArticle>  $query
     * @return Builder<KBArticle>
     */
    public function scopeOfType(Builder $query, KBArticleType $type): Builder
    {
        return $query->where('article_type', $type);
    }

    /**
     * Scope to filter by difficulty level.
     *
     * @param  Builder<KBArticle>  $query
     * @return Builder<KBArticle>
     */
    public function scopeWithDifficulty(Builder $query, KBDifficultyLevel $level): Builder
    {
        return $query->where('difficulty_level', $level);
    }

    /**
     * Scope to get searchable (published and public) articles.
     *
     * @param  Builder<KBArticle>  $query
     * @return Builder<KBArticle>
     */
    public function scopeSearchable(Builder $query): Builder
    {
        return $query->where('status', KBArticleStatus::PUBLISHED)
            ->where('is_public', true);
    }

    /**
     * Scope to order by popularity (view count).
     *
     * @param  Builder<KBArticle>  $query
     * @return Builder<KBArticle>
     */
    public function scopePopular(Builder $query): Builder
    {
        return $query->orderByDesc('view_count');
    }

    /**
     * Check if the article is published.
     */
    public function isPublished(): bool
    {
        return $this->status === KBArticleStatus::PUBLISHED;
    }

    /**
     * Check if the article is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === KBArticleStatus::DRAFT;
    }

    /**
     * Check if the article is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === KBArticleStatus::ARCHIVED;
    }

    /**
     * Publish the article.
     */
    public function publish(): void
    {
        if (!$this->status->canTransitionTo(KBArticleStatus::PUBLISHED)) {
            return;
        }

        $this->status = KBArticleStatus::PUBLISHED;
        $this->published_at = now();
        $this->save();

        $this->category->incrementArticleCount();
    }

    /**
     * Unpublish the article (set to draft).
     */
    public function unpublish(): void
    {
        if (!$this->status->canTransitionTo(KBArticleStatus::DRAFT)) {
            return;
        }

        $wasPublished = $this->isPublished();
        $this->status = KBArticleStatus::DRAFT;
        $this->save();

        if ($wasPublished) {
            $this->category->decrementArticleCount();
        }
    }

    /**
     * Archive the article.
     */
    public function archive(): void
    {
        if (!$this->status->canTransitionTo(KBArticleStatus::ARCHIVED)) {
            return;
        }

        $wasPublished = $this->isPublished();
        $this->status = KBArticleStatus::ARCHIVED;
        $this->save();

        if ($wasPublished) {
            $this->category->decrementArticleCount();
        }
    }

    /**
     * Increment the view count.
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Record a helpful vote.
     */
    public function recordHelpfulVote(): void
    {
        $this->increment('helpful_count');
    }

    /**
     * Record a not helpful vote.
     */
    public function recordNotHelpfulVote(): void
    {
        $this->increment('not_helpful_count');
    }

    /**
     * Get the percentage of helpful votes.
     */
    public function getHelpfulPercentage(): float
    {
        $total = $this->helpful_count + $this->not_helpful_count;

        if ($total === 0) {
            return 0.0;
        }

        return round(($this->helpful_count / $total) * 100, 1);
    }

    /**
     * Create a new version snapshot.
     */
    public function createVersion(string $changeSummary, string $changedById): KBArticleVersion
    {
        return $this->versions()->create([
            'version' => $this->version,
            'title' => $this->title,
            'content' => $this->content,
            'change_summary' => $changeSummary,
            'changed_by' => $changedById,
        ]);
    }

    /**
     * Restore from a specific version.
     */
    public function restoreVersion(KBArticleVersion $version): void
    {
        $this->title = $version->title;
        $this->content = $version->content;
        $this->version = $this->version + 1;
        $this->save();
    }

    /**
     * Get the article URL.
     */
    public function getUrl(): string
    {
        return "/kb/{$this->category->slug}/{$this->slug}";
    }

    /**
     * Attach a tag to this article.
     */
    public function attachTag(KBTag $tag): void
    {
        if (!$this->tags()->where('tag_id', $tag->id)->exists()) {
            $this->tags()->attach($tag->id);
            $tag->incrementUsageCount();
        }
    }

    /**
     * Detach a tag from this article.
     */
    public function detachTag(KBTag $tag): void
    {
        if ($this->tags()->where('tag_id', $tag->id)->exists()) {
            $this->tags()->detach($tag->id);
            $tag->decrementUsageCount();
        }
    }

    /**
     * Sync tags for this article.
     *
     * @param  array<string>  $tagIds
     */
    public function syncTags(array $tagIds): void
    {
        $currentTagIds = $this->tags()->pluck('kb_tags.id')->toArray();

        // Tags to add
        $toAdd = array_diff($tagIds, $currentTagIds);
        foreach ($toAdd as $tagId) {
            $tag = KBTag::find($tagId);
            if ($tag) {
                $tag->incrementUsageCount();
            }
        }

        // Tags to remove
        $toRemove = array_diff($currentTagIds, $tagIds);
        foreach ($toRemove as $tagId) {
            $tag = KBTag::find($tagId);
            if ($tag) {
                $tag->decrementUsageCount();
            }
        }

        $this->tags()->sync($tagIds);
    }

    /**
     * Add a relation to another article.
     */
    public function addRelation(KBArticle $relatedArticle, KBRelationType $type, int $sortOrder = 0): void
    {
        $this->relatedArticles()->syncWithoutDetaching([
            $relatedArticle->id => [
                'relation_type' => $type->value,
                'sort_order' => $sortOrder,
            ],
        ]);
    }

    /**
     * Remove a relation to another article.
     */
    public function removeRelation(KBArticle $relatedArticle): void
    {
        $this->relatedArticles()->detach($relatedArticle->id);
    }
}
