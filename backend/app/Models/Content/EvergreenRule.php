<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * EvergreenRule Model
 *
 * Represents a rule for recycling evergreen content.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string $name Rule name
 * @property bool $is_active Active status
 * @property string|null $source_category_id Source category UUID
 * @property array $social_account_ids Social account UUIDs
 * @property int $repost_interval_days Days between reposts
 * @property int $max_reposts Maximum number of reposts
 * @property array|null $time_slots Time slots for posting
 * @property bool $content_variation Enable content variation
 * @property \Carbon\Carbon|null $last_reposted_at Last repost timestamp
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 * @property-read ContentCategory|null $sourceCategory
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EvergreenPostPool> $poolEntries
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> active()
 */
final class EvergreenRule extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'evergreen_rules';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'name',
        'is_active',
        'source_category_id',
        'social_account_ids',
        'repost_interval_days',
        'max_reposts',
        'time_slots',
        'content_variation',
        'last_reposted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'social_account_ids' => 'array',
            'repost_interval_days' => 'integer',
            'max_reposts' => 'integer',
            'time_slots' => 'array',
            'content_variation' => 'boolean',
            'last_reposted_at' => 'datetime',
        ];
    }

    /**
     * Get the workspace that this rule belongs to.
     *
     * @return BelongsTo<Workspace, EvergreenRule>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the source category for this rule.
     *
     * @return BelongsTo<ContentCategory, EvergreenRule>
     */
    public function sourceCategory(): BelongsTo
    {
        return $this->belongsTo(ContentCategory::class, 'source_category_id');
    }

    /**
     * Get the pool entries for this rule.
     *
     * @return HasMany<EvergreenPostPool>
     */
    public function poolEntries(): HasMany
    {
        return $this->hasMany(EvergreenPostPool::class);
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<EvergreenRule>  $query
     * @return Builder<EvergreenRule>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to filter active rules.
     *
     * @param  Builder<EvergreenRule>  $query
     * @return Builder<EvergreenRule>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
