<?php

declare(strict_types=1);

namespace App\Models\Feedback;

use App\Enums\Feedback\ChangeType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ReleaseNoteItem Model
 *
 * Represents an individual item within a release note.
 *
 * @property string $id UUID primary key
 * @property string $release_note_id ReleaseNote UUID
 * @property string $title Item title
 * @property string|null $description Item description
 * @property ChangeType $change_type Type of change
 * @property string|null $roadmap_item_id Linked RoadmapItem UUID
 * @property int $sort_order Display order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read ReleaseNote $releaseNote
 * @property-read RoadmapItem|null $roadmapItem
 *
 * @method static Builder<static> forRelease(string $releaseNoteId)
 * @method static Builder<static> byType(ChangeType $type)
 * @method static Builder<static> ordered()
 */
final class ReleaseNoteItem extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'release_note_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'release_note_id',
        'title',
        'description',
        'change_type',
        'roadmap_item_id',
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
            'change_type' => ChangeType::class,
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the release note.
     *
     * @return BelongsTo<ReleaseNote, ReleaseNoteItem>
     */
    public function releaseNote(): BelongsTo
    {
        return $this->belongsTo(ReleaseNote::class, 'release_note_id');
    }

    /**
     * Get the linked roadmap item.
     *
     * @return BelongsTo<RoadmapItem, ReleaseNoteItem>
     */
    public function roadmapItem(): BelongsTo
    {
        return $this->belongsTo(RoadmapItem::class, 'roadmap_item_id');
    }

    /**
     * Scope to filter by release note.
     *
     * @param  Builder<ReleaseNoteItem>  $query
     * @return Builder<ReleaseNoteItem>
     */
    public function scopeForRelease(Builder $query, string $releaseNoteId): Builder
    {
        return $query->where('release_note_id', $releaseNoteId);
    }

    /**
     * Scope to filter by change type.
     *
     * @param  Builder<ReleaseNoteItem>  $query
     * @return Builder<ReleaseNoteItem>
     */
    public function scopeByType(Builder $query, ChangeType $type): Builder
    {
        return $query->where('change_type', $type);
    }

    /**
     * Scope to order by sort_order.
     *
     * @param  Builder<ReleaseNoteItem>  $query
     * @return Builder<ReleaseNoteItem>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
