<?php

declare(strict_types=1);

namespace App\Models\Listening;

use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MonitoredKeyword Model
 *
 * Represents a keyword being monitored for social listening.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string $keyword The keyword to monitor
 * @property array|null $platforms Platforms to monitor on
 * @property bool $is_active Whether monitoring is active
 * @property bool $notify_on_match Whether to notify on match
 * @property int $match_count Total number of matches found
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 * @property-read \Illuminate\Database\Eloquent\Collection<int, KeywordMention> $mentions
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> active()
 */
final class MonitoredKeyword extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'monitored_keywords';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'keyword',
        'platforms',
        'is_active',
        'notify_on_match',
        'match_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platforms' => 'array',
            'is_active' => 'boolean',
            'notify_on_match' => 'boolean',
        ];
    }

    /**
     * Get the workspace that this keyword belongs to.
     *
     * @return BelongsTo<Workspace, MonitoredKeyword>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the mentions for this keyword.
     *
     * @return HasMany<KeywordMention>
     */
    public function mentions(): HasMany
    {
        return $this->hasMany(KeywordMention::class, 'keyword_id');
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<MonitoredKeyword>  $query
     * @return Builder<MonitoredKeyword>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to filter active keywords.
     *
     * @param  Builder<MonitoredKeyword>  $query
     * @return Builder<MonitoredKeyword>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
