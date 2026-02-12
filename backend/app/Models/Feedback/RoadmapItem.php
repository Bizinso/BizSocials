<?php

declare(strict_types=1);

namespace App\Models\Feedback;

use App\Enums\Feedback\AdminPriority;
use App\Enums\Feedback\RoadmapCategory;
use App\Enums\Feedback\RoadmapStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * RoadmapItem Model
 *
 * Represents an item on the product roadmap.
 *
 * @property string $id UUID primary key
 * @property string $title Item title
 * @property string|null $description Short description
 * @property string|null $detailed_description Detailed description
 * @property RoadmapCategory $category Product category
 * @property RoadmapStatus $status Current status
 * @property string|null $quarter Target quarter (Q1 2026)
 * @property \Carbon\Carbon|null $target_date Target date
 * @property \Carbon\Carbon|null $shipped_date Date shipped
 * @property AdminPriority $priority Priority level
 * @property int $progress_percentage Progress (0-100)
 * @property bool $is_public Publicly visible
 * @property int $linked_feedback_count Number of linked feedback items
 * @property int $total_votes Total votes from linked feedback
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Collection<int, Feedback> $linkedFeedback
 * @property-read Collection<int, ReleaseNoteItem> $releaseNoteItems
 *
 * @method static Builder<static> public()
 * @method static Builder<static> byStatus(RoadmapStatus $status)
 * @method static Builder<static> byCategory(RoadmapCategory $category)
 * @method static Builder<static> byQuarter(string $quarter)
 * @method static Builder<static> active()
 * @method static Builder<static> shipped()
 */
final class RoadmapItem extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'roadmap_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'detailed_description',
        'category',
        'status',
        'quarter',
        'target_date',
        'shipped_date',
        'priority',
        'progress_percentage',
        'is_public',
        'linked_feedback_count',
        'total_votes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => RoadmapCategory::class,
            'status' => RoadmapStatus::class,
            'priority' => AdminPriority::class,
            'target_date' => 'date',
            'shipped_date' => 'date',
            'progress_percentage' => 'integer',
            'is_public' => 'boolean',
            'linked_feedback_count' => 'integer',
            'total_votes' => 'integer',
        ];
    }

    /**
     * Get feedback linked to this roadmap item.
     *
     * @return BelongsToMany<Feedback>
     */
    public function linkedFeedback(): BelongsToMany
    {
        return $this->belongsToMany(Feedback::class, 'roadmap_feedback_links', 'roadmap_item_id', 'feedback_id')
            ->withTimestamps();
    }

    /**
     * Get release note items for this roadmap item.
     *
     * @return HasMany<ReleaseNoteItem>
     */
    public function releaseNoteItems(): HasMany
    {
        return $this->hasMany(ReleaseNoteItem::class, 'roadmap_item_id');
    }

    /**
     * Scope to get public roadmap items.
     *
     * @param  Builder<RoadmapItem>  $query
     * @return Builder<RoadmapItem>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to filter by status.
     *
     * @param  Builder<RoadmapItem>  $query
     * @return Builder<RoadmapItem>
     */
    public function scopeByStatus(Builder $query, RoadmapStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by category.
     *
     * @param  Builder<RoadmapItem>  $query
     * @return Builder<RoadmapItem>
     */
    public function scopeByCategory(Builder $query, RoadmapCategory $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter by quarter.
     *
     * @param  Builder<RoadmapItem>  $query
     * @return Builder<RoadmapItem>
     */
    public function scopeByQuarter(Builder $query, string $quarter): Builder
    {
        return $query->where('quarter', $quarter);
    }

    /**
     * Scope to get active roadmap items.
     *
     * @param  Builder<RoadmapItem>  $query
     * @return Builder<RoadmapItem>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            RoadmapStatus::PLANNED,
            RoadmapStatus::IN_PROGRESS,
            RoadmapStatus::BETA,
        ]);
    }

    /**
     * Scope to get shipped roadmap items.
     *
     * @param  Builder<RoadmapItem>  $query
     * @return Builder<RoadmapItem>
     */
    public function scopeShipped(Builder $query): Builder
    {
        return $query->where('status', RoadmapStatus::SHIPPED);
    }

    /**
     * Check if the item is public.
     */
    public function isPublic(): bool
    {
        return $this->is_public;
    }

    /**
     * Check if the item is active.
     */
    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    /**
     * Check if the item is shipped.
     */
    public function isShipped(): bool
    {
        return $this->status === RoadmapStatus::SHIPPED;
    }

    /**
     * Update the progress percentage.
     */
    public function updateProgress(int $percentage): void
    {
        $this->progress_percentage = max(0, min(100, $percentage));
        $this->save();
    }

    /**
     * Mark the item as shipped.
     */
    public function markAsShipped(): void
    {
        if (!$this->status->canTransitionTo(RoadmapStatus::SHIPPED)) {
            return;
        }

        $this->status = RoadmapStatus::SHIPPED;
        $this->shipped_date = now();
        $this->progress_percentage = 100;
        $this->save();
    }

    /**
     * Link feedback to this roadmap item.
     */
    public function linkFeedback(Feedback $feedback): void
    {
        if (!$this->linkedFeedback()->where('feedback_id', $feedback->id)->exists()) {
            $this->linkedFeedback()->attach($feedback->id);
            $this->recalculateCounts();
        }
    }

    /**
     * Unlink feedback from this roadmap item.
     */
    public function unlinkFeedback(Feedback $feedback): void
    {
        if ($this->linkedFeedback()->where('feedback_id', $feedback->id)->exists()) {
            $this->linkedFeedback()->detach($feedback->id);
            $this->recalculateCounts();
        }
    }

    /**
     * Recalculate the linked feedback count and total votes.
     */
    public function recalculateCounts(): void
    {
        $this->linked_feedback_count = $this->linkedFeedback()->count();
        $this->total_votes = (int) $this->linkedFeedback()->sum('vote_count');
        $this->save();
    }
}
