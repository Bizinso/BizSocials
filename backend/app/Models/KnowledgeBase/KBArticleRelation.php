<?php

declare(strict_types=1);

namespace App\Models\KnowledgeBase;

use App\Enums\KnowledgeBase\KBRelationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * KBArticleRelation Model (Pivot)
 *
 * Represents the relationship between articles (related, prerequisite, next_step).
 *
 * @property string $article_id Source article UUID
 * @property string $related_article_id Related article UUID
 * @property KBRelationType $relation_type Type of relationship
 * @property int $sort_order Display order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read KBArticle $article
 * @property-read KBArticle $relatedArticle
 *
 * @method static Builder<static> ofType(KBRelationType $type)
 * @method static Builder<static> prerequisites()
 * @method static Builder<static> nextSteps()
 * @method static Builder<static> related()
 */
final class KBArticleRelation extends Pivot
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'kb_article_relations';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The primary key type.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'article_id',
        'related_article_id',
        'relation_type',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'relation_type' => KBRelationType::class,
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the source article.
     *
     * @return BelongsTo<KBArticle, KBArticleRelation>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(KBArticle::class, 'article_id');
    }

    /**
     * Get the related article.
     *
     * @return BelongsTo<KBArticle, KBArticleRelation>
     */
    public function relatedArticle(): BelongsTo
    {
        return $this->belongsTo(KBArticle::class, 'related_article_id');
    }

    /**
     * Scope to filter by relation type.
     *
     * @param  Builder<KBArticleRelation>  $query
     * @return Builder<KBArticleRelation>
     */
    public function scopeOfType(Builder $query, KBRelationType $type): Builder
    {
        return $query->where('relation_type', $type);
    }

    /**
     * Scope to get only prerequisite relations.
     *
     * @param  Builder<KBArticleRelation>  $query
     * @return Builder<KBArticleRelation>
     */
    public function scopePrerequisites(Builder $query): Builder
    {
        return $query->where('relation_type', KBRelationType::PREREQUISITE);
    }

    /**
     * Scope to get only next step relations.
     *
     * @param  Builder<KBArticleRelation>  $query
     * @return Builder<KBArticleRelation>
     */
    public function scopeNextSteps(Builder $query): Builder
    {
        return $query->where('relation_type', KBRelationType::NEXT_STEP);
    }

    /**
     * Scope to get only related relations.
     *
     * @param  Builder<KBArticleRelation>  $query
     * @return Builder<KBArticleRelation>
     */
    public function scopeRelated(Builder $query): Builder
    {
        return $query->where('relation_type', KBRelationType::RELATED);
    }

    /**
     * Check if this is a prerequisite relation.
     */
    public function isPrerequisite(): bool
    {
        return $this->relation_type === KBRelationType::PREREQUISITE;
    }

    /**
     * Check if this is a next step relation.
     */
    public function isNextStep(): bool
    {
        return $this->relation_type === KBRelationType::NEXT_STEP;
    }

    /**
     * Check if this is a related relation.
     */
    public function isRelated(): bool
    {
        return $this->relation_type === KBRelationType::RELATED;
    }
}
