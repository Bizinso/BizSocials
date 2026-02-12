<?php

declare(strict_types=1);

namespace App\Models\KnowledgeBase;

use App\Models\Platform\SuperAdminUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * KBArticleVersion Model
 *
 * Represents a version snapshot of a knowledge base article.
 *
 * @property string $id UUID primary key
 * @property string $article_id Article UUID
 * @property int $version Version number
 * @property string $title Article title at this version
 * @property string $content Article content at this version
 * @property string|null $change_summary Summary of changes
 * @property string $changed_by SuperAdminUser UUID who made the change
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read KBArticle $article
 * @property-read SuperAdminUser $changedBy
 *
 * @method static Builder<static> forArticle(string $articleId)
 * @method static Builder<static> latestFirst()
 */
final class KBArticleVersion extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'kb_article_versions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'article_id',
        'version',
        'title',
        'content',
        'change_summary',
        'changed_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
        ];
    }

    /**
     * Get the article this version belongs to.
     *
     * @return BelongsTo<KBArticle, KBArticleVersion>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(KBArticle::class, 'article_id');
    }

    /**
     * Get the user who created this version.
     *
     * @return BelongsTo<SuperAdminUser, KBArticleVersion>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(SuperAdminUser::class, 'changed_by');
    }

    /**
     * Scope to filter by article.
     *
     * @param  Builder<KBArticleVersion>  $query
     * @return Builder<KBArticleVersion>
     */
    public function scopeForArticle(Builder $query, string $articleId): Builder
    {
        return $query->where('article_id', $articleId);
    }

    /**
     * Scope to order by version descending.
     *
     * @param  Builder<KBArticleVersion>  $query
     * @return Builder<KBArticleVersion>
     */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('version');
    }

    /**
     * Check if this is the latest version.
     */
    public function isLatest(): bool
    {
        return $this->article->version === $this->version;
    }

    /**
     * Get a simple diff summary comparing to another version.
     *
     * @return array<string, array{old: string, new: string}>
     */
    public function getDiff(KBArticleVersion $otherVersion): array
    {
        $diff = [];

        if ($this->title !== $otherVersion->title) {
            $diff['title'] = [
                'old' => $otherVersion->title,
                'new' => $this->title,
            ];
        }

        if ($this->content !== $otherVersion->content) {
            $diff['content'] = [
                'old' => $otherVersion->content,
                'new' => $this->content,
            ];
        }

        return $diff;
    }
}
