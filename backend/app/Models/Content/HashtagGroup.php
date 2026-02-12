<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HashtagGroup Model
 *
 * Represents a reusable group of hashtags.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string $name Group name
 * @property array $hashtags Array of hashtags
 * @property string|null $description Group description
 * @property int $usage_count Number of times used
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 */
final class HashtagGroup extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hashtag_groups';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'name',
        'hashtags',
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
            'hashtags' => 'array',
            'usage_count' => 'integer',
        ];
    }

    /**
     * Get the workspace that this group belongs to.
     *
     * @return BelongsTo<Workspace, HashtagGroup>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<HashtagGroup>  $query
     * @return Builder<HashtagGroup>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Increment the usage counter.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}
