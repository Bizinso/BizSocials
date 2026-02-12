<?php

declare(strict_types=1);

namespace App\Models\Feedback;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * FeedbackTagAssignment Model (Pivot)
 *
 * Represents the many-to-many relationship between feedback and tags.
 *
 * @property string $feedback_id Feedback UUID
 * @property string $tag_id Tag UUID
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Feedback $feedback
 * @property-read FeedbackTag $tag
 */
final class FeedbackTagAssignment extends Pivot
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'feedback_tag_assignments';

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
     * Get the feedback.
     *
     * @return BelongsTo<Feedback, FeedbackTagAssignment>
     */
    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class, 'feedback_id');
    }

    /**
     * Get the tag.
     *
     * @return BelongsTo<FeedbackTag, FeedbackTagAssignment>
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(FeedbackTag::class, 'tag_id');
    }
}
