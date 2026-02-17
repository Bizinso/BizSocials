<?php

declare(strict_types=1);

namespace App\Models\Inbox;

use App\Models\Social\SocialAccount;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * InboxConversation Model
 *
 * Represents a conversation thread grouping related inbox items.
 * Conversations are identified by a unique key that groups messages
 * from the same participant or thread.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string $social_account_id Social account UUID
 * @property string $conversation_key Unique conversation identifier
 * @property string|null $subject Conversation subject/title
 * @property string $participant_name Participant's display name
 * @property string|null $participant_username Participant's username
 * @property string|null $participant_profile_url Participant's profile URL
 * @property string|null $participant_avatar_url Participant's avatar URL
 * @property int $message_count Number of messages in conversation
 * @property \Carbon\Carbon|null $first_message_at First message timestamp
 * @property \Carbon\Carbon|null $last_message_at Last message timestamp
 * @property string $status Conversation status (active, resolved, archived)
 * @property array|null $metadata Additional metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 * @property-read SocialAccount $socialAccount
 * @property-read Collection<InboxItem> $items
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> active()
 * @method static Builder<static> withStatus(string $status)
 * @method static Builder<static> recentActivity()
 */
final class InboxConversation extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\InboxConversationFactory
    {
        return \Database\Factories\InboxConversationFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inbox_conversations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'social_account_id',
        'conversation_key',
        'subject',
        'participant_name',
        'participant_username',
        'participant_profile_url',
        'participant_avatar_url',
        'message_count',
        'first_message_at',
        'last_message_at',
        'status',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'message_count' => 'integer',
            'first_message_at' => 'datetime',
            'last_message_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the workspace that this conversation belongs to.
     *
     * @return BelongsTo<Workspace, InboxConversation>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the social account that this conversation belongs to.
     *
     * @return BelongsTo<SocialAccount, InboxConversation>
     */
    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    /**
     * Get the inbox items in this conversation.
     *
     * @return HasMany<InboxItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InboxItem::class, 'conversation_id')->orderBy('platform_created_at');
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<InboxConversation>  $query
     * @return Builder<InboxConversation>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to get active conversations.
     *
     * @param  Builder<InboxConversation>  $query
     * @return Builder<InboxConversation>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter by status.
     *
     * @param  Builder<InboxConversation>  $query
     * @return Builder<InboxConversation>
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get conversations with recent activity.
     *
     * @param  Builder<InboxConversation>  $query
     * @return Builder<InboxConversation>
     */
    public function scopeRecentActivity(Builder $query): Builder
    {
        return $query->orderByDesc('last_message_at');
    }

    /**
     * Update conversation metadata when a new message is added.
     */
    public function addMessage(InboxItem $item): void
    {
        $this->message_count++;
        
        if ($this->first_message_at === null || $item->platform_created_at < $this->first_message_at) {
            $this->first_message_at = $item->platform_created_at;
        }
        
        if ($this->last_message_at === null || $item->platform_created_at > $this->last_message_at) {
            $this->last_message_at = $item->platform_created_at;
        }
        
        $this->save();
    }

    /**
     * Mark conversation as resolved.
     */
    public function markAsResolved(): void
    {
        $this->status = 'resolved';
        $this->save();
    }

    /**
     * Mark conversation as archived.
     */
    public function archive(): void
    {
        $this->status = 'archived';
        $this->save();
    }

    /**
     * Reopen conversation (set back to active).
     */
    public function reopen(): void
    {
        $this->status = 'active';
        $this->save();
    }
}
