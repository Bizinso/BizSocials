<?php

declare(strict_types=1);

namespace App\Models\KnowledgeBase;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * KBArticleTag Model (Pivot)
 *
 * Represents the many-to-many relationship between articles and tags.
 *
 * @property string $article_id Article UUID
 * @property string $tag_id Tag UUID
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read KBArticle $article
 * @property-read KBTag $tag
 */
final class KBArticleTag extends Pivot
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'kb_article_tags';

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
     * Get the article.
     *
     * @return BelongsTo<KBArticle, KBArticleTag>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(KBArticle::class, 'article_id');
    }

    /**
     * Get the tag.
     *
     * @return BelongsTo<KBTag, KBArticleTag>
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(KBTag::class, 'tag_id');
    }
}
