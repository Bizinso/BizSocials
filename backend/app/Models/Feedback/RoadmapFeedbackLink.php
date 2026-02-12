<?php

declare(strict_types=1);

namespace App\Models\Feedback;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * RoadmapFeedbackLink Model (Pivot)
 *
 * Represents the many-to-many relationship between roadmap items and feedback.
 *
 * @property string $roadmap_item_id RoadmapItem UUID
 * @property string $feedback_id Feedback UUID
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read RoadmapItem $roadmapItem
 * @property-read Feedback $feedback
 */
final class RoadmapFeedbackLink extends Pivot
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'roadmap_feedback_links';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The primary key type.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Get the roadmap item.
     *
     * @return BelongsTo<RoadmapItem, RoadmapFeedbackLink>
     */
    public function roadmapItem(): BelongsTo
    {
        return $this->belongsTo(RoadmapItem::class, 'roadmap_item_id');
    }

    /**
     * Get the feedback.
     *
     * @return BelongsTo<Feedback, RoadmapFeedbackLink>
     */
    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class, 'feedback_id');
    }
}
