<?php

declare(strict_types=1);

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * SupportTicketTagAssignment Model (Pivot)
 *
 * Represents the many-to-many relationship between tickets and tags.
 *
 * @property string $ticket_id Ticket UUID
 * @property string $tag_id Tag UUID
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read SupportTicket $ticket
 * @property-read SupportTicketTag $tag
 */
final class SupportTicketTagAssignment extends Pivot
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'support_ticket_tag_assignments';

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
     * Get the ticket.
     *
     * @return BelongsTo<SupportTicket, SupportTicketTagAssignment>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    /**
     * Get the tag.
     *
     * @return BelongsTo<SupportTicketTag, SupportTicketTagAssignment>
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(SupportTicketTag::class, 'tag_id');
    }
}
