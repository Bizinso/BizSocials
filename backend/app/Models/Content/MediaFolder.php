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
 * MediaFolder Model
 *
 * Represents a folder structure for organizing media library items.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string|null $parent_id Parent folder UUID
 * @property string $name Folder name
 * @property string $slug URL-safe identifier
 * @property string|null $color Folder color code
 * @property int $sort_order Sort order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 * @property-read MediaFolder|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MediaFolder> $children
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MediaLibraryItem> $items
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 */
final class MediaFolder extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'media_folders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'parent_id',
        'name',
        'slug',
        'color',
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
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the workspace that this folder belongs to.
     *
     * @return BelongsTo<Workspace, MediaFolder>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the parent folder.
     *
     * @return BelongsTo<MediaFolder, MediaFolder>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'parent_id');
    }

    /**
     * Get the child folders.
     *
     * @return HasMany<MediaFolder>
     */
    public function children(): HasMany
    {
        return $this->hasMany(MediaFolder::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Get the media items in this folder.
     *
     * @return HasMany<MediaLibraryItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(MediaLibraryItem::class, 'folder_id');
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<MediaFolder>  $query
     * @return Builder<MediaFolder>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }
}
