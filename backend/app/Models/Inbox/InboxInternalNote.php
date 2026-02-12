<?php

declare(strict_types=1);

namespace App\Models\Inbox;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InboxInternalNote Model
 *
 * Represents an internal note left by a team member on an inbox item.
 * These notes are only visible to workspace members, not to the external author.
 *
 * @property string $id UUID primary key
 * @property string $inbox_item_id Inbox item UUID
 * @property string $user_id User UUID who created the note
 * @property string $content Note content
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read InboxItem $inboxItem
 * @property-read User $user
 */
final class InboxInternalNote extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inbox_internal_notes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'inbox_item_id',
        'user_id',
        'content',
    ];

    /**
     * Get the inbox item that this note belongs to.
     *
     * @return BelongsTo<InboxItem, InboxInternalNote>
     */
    public function inboxItem(): BelongsTo
    {
        return $this->belongsTo(InboxItem::class);
    }

    /**
     * Get the user who created this note.
     *
     * @return BelongsTo<User, InboxInternalNote>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
