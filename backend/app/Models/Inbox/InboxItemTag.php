<?php

declare(strict_types=1);

namespace App\Models\Inbox;

use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * InboxItemTag Model
 *
 * Represents a tag that can be applied to inbox items for categorization.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string $name Tag name
 * @property string $color Tag color hex code
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 * @property-read \Illuminate\Database\Eloquent\Collection<InboxItem> $items
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 */
final class InboxItemTag extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inbox_item_tags';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'name',
        'color',
    ];

    /**
     * Get the workspace that this tag belongs to.
     *
     * @return BelongsTo<Workspace, InboxItemTag>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the inbox items that have this tag.
     *
     * @return BelongsToMany<InboxItem>
     */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(InboxItem::class, 'inbox_item_tag_assignments', 'tag_id', 'inbox_item_id');
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<InboxItemTag>  $query
     * @return Builder<InboxItemTag>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }
}
