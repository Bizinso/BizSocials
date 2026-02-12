<?php

declare(strict_types=1);

namespace App\Models\KnowledgeBase;

use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * KBSearchAnalytic Model
 *
 * Represents search analytics for the knowledge base.
 *
 * @property string $id UUID primary key
 * @property string $search_query Original search query
 * @property string $search_query_normalized Normalized search query
 * @property int $results_count Number of results found
 * @property string|null $clicked_article_id Clicked article UUID
 * @property bool|null $search_successful Whether search was successful
 * @property string|null $user_id User UUID who searched
 * @property string|null $tenant_id Tenant UUID context
 * @property string|null $session_id Session ID
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read KBArticle|null $clickedArticle
 * @property-read User|null $user
 * @property-read Tenant|null $tenant
 *
 * @method static Builder<static> successful()
 * @method static Builder<static> noResults()
 * @method static Builder<static> inDateRange(\DateTimeInterface $start, \DateTimeInterface $end)
 * @method static Builder<static> forUser(string $userId)
 */
final class KBSearchAnalytic extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'kb_search_analytics';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'search_query',
        'search_query_normalized',
        'results_count',
        'clicked_article_id',
        'search_successful',
        'user_id',
        'tenant_id',
        'session_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'results_count' => 'integer',
            'search_successful' => 'boolean',
        ];
    }

    /**
     * Get the clicked article.
     *
     * @return BelongsTo<KBArticle, KBSearchAnalytic>
     */
    public function clickedArticle(): BelongsTo
    {
        return $this->belongsTo(KBArticle::class, 'clicked_article_id');
    }

    /**
     * Get the user who performed the search.
     *
     * @return BelongsTo<User, KBSearchAnalytic>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the tenant context.
     *
     * @return BelongsTo<Tenant, KBSearchAnalytic>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Scope to get successful searches.
     *
     * @param  Builder<KBSearchAnalytic>  $query
     * @return Builder<KBSearchAnalytic>
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('search_successful', true);
    }

    /**
     * Scope to get searches with no results.
     *
     * @param  Builder<KBSearchAnalytic>  $query
     * @return Builder<KBSearchAnalytic>
     */
    public function scopeNoResults(Builder $query): Builder
    {
        return $query->where('results_count', 0);
    }

    /**
     * Scope to filter by date range.
     *
     * @param  Builder<KBSearchAnalytic>  $query
     * @return Builder<KBSearchAnalytic>
     */
    public function scopeInDateRange(Builder $query, \DateTimeInterface $start, \DateTimeInterface $end): Builder
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Scope to filter by user.
     *
     * @param  Builder<KBSearchAnalytic>  $query
     * @return Builder<KBSearchAnalytic>
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Mark the search as successful.
     */
    public function markAsSuccessful(): void
    {
        $this->search_successful = true;
        $this->save();
    }

    /**
     * Record a click on an article.
     */
    public function recordClick(string $articleId): void
    {
        $this->clicked_article_id = $articleId;
        $this->search_successful = true;
        $this->save();
    }
}
