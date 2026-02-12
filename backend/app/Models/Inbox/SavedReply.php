<?php

declare(strict_types=1);

namespace App\Models\Inbox;

use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SavedReply Model
 *
 * Represents a pre-written reply template that can be quickly inserted
 * when responding to inbox items.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string $title Reply title
 * @property string $content Reply content text
 * @property string|null $shortcut Keyboard shortcut for quick access
 * @property string|null $category Category for organization
 * @property int $usage_count Number of times used
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> forCategory(string $category)
 * @method static Builder<static> search(string $term)
 */
final class SavedReply extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'saved_replies';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'title',
        'content',
        'shortcut',
        'category',
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
            'usage_count' => 'integer',
        ];
    }

    /**
     * Get the workspace that this saved reply belongs to.
     *
     * @return BelongsTo<Workspace, SavedReply>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<SavedReply>  $query
     * @return Builder<SavedReply>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to filter by category.
     *
     * @param  Builder<SavedReply>  $query
     * @return Builder<SavedReply>
     */
    public function scopeForCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to search by title or content.
     *
     * @param  Builder<SavedReply>  $query
     * @return Builder<SavedReply>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term): void {
            $q->where('title', 'like', '%' . $term . '%')
                ->orWhere('content', 'like', '%' . $term . '%')
                ->orWhere('shortcut', 'like', '%' . $term . '%');
        });
    }
}
