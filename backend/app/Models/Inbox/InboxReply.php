<?php

declare(strict_types=1);

namespace App\Models\Inbox;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InboxReply Model
 *
 * Represents a reply sent to an inbox item (comment).
 * Tracks the response sent to the social platform.
 *
 * @property string $id UUID primary key
 * @property string $inbox_item_id Inbox item UUID
 * @property string $replied_by_user_id User UUID who sent the reply
 * @property string $content_text Reply content (max 1000 chars)
 * @property string|null $platform_reply_id Platform's reply ID
 * @property \Carbon\Carbon $sent_at When the reply was sent
 * @property \Carbon\Carbon|null $failed_at When the reply failed
 * @property string|null $failure_reason Reason for failure
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read InboxItem $inboxItem
 * @property-read User $repliedBy
 *
 * @method static Builder<static> forItem(string $inboxItemId)
 * @method static Builder<static> successful()
 * @method static Builder<static> failed()
 */
final class InboxReply extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inbox_replies';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'inbox_item_id',
        'replied_by_user_id',
        'content_text',
        'platform_reply_id',
        'sent_at',
        'failed_at',
        'failure_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /**
     * Get the inbox item this reply belongs to.
     *
     * @return BelongsTo<InboxItem, InboxReply>
     */
    public function inboxItem(): BelongsTo
    {
        return $this->belongsTo(InboxItem::class);
    }

    /**
     * Get the user who sent this reply.
     *
     * @return BelongsTo<User, InboxReply>
     */
    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by_user_id');
    }

    /**
     * Scope to filter by inbox item.
     *
     * @param  Builder<InboxReply>  $query
     * @return Builder<InboxReply>
     */
    public function scopeForItem(Builder $query, string $inboxItemId): Builder
    {
        return $query->where('inbox_item_id', $inboxItemId);
    }

    /**
     * Scope to get successful replies.
     *
     * @param  Builder<InboxReply>  $query
     * @return Builder<InboxReply>
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->whereNotNull('platform_reply_id')
            ->whereNull('failed_at');
    }

    /**
     * Scope to get failed replies.
     *
     * @param  Builder<InboxReply>  $query
     * @return Builder<InboxReply>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->whereNotNull('failed_at');
    }

    /**
     * Check if the reply was sent successfully.
     */
    public function isSent(): bool
    {
        return $this->platform_reply_id !== null && $this->failed_at === null;
    }

    /**
     * Check if the reply has failed.
     */
    public function hasFailed(): bool
    {
        return $this->failed_at !== null;
    }

    /**
     * Mark the reply as sent successfully.
     */
    public function markAsSent(string $platformReplyId): void
    {
        $this->platform_reply_id = $platformReplyId;
        $this->failed_at = null;
        $this->failure_reason = null;
        $this->save();
    }

    /**
     * Mark the reply as failed.
     */
    public function markAsFailed(string $reason): void
    {
        $this->failed_at = now();
        $this->failure_reason = $reason;
        $this->save();
    }
}
