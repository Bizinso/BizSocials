<?php

declare(strict_types=1);

namespace App\Events\Broadcast;

use App\Models\Inbox\InboxItem;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * InboxMessageAssigned Event
 *
 * Broadcast when an inbox message is assigned to a user.
 * This enables real-time notifications for assigned team members.
 */
final class InboxMessageAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly InboxItem $inboxItem,
        public readonly string $assignedToUserId,
        public readonly string $assignedByUserId,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            // Broadcast to the workspace inbox channel
            new PrivateChannel("workspace.{$this->inboxItem->workspace_id}.inbox"),
            // Broadcast to the assigned user's personal channel
            new PrivateChannel("user.{$this->assignedToUserId}"),
        ];
    }

    /**
     * Get the event name for broadcasting.
     */
    public function broadcastAs(): string
    {
        return 'inbox.message_assigned';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'inbox_item_id' => $this->inboxItem->id,
            'assigned_to_user_id' => $this->assignedToUserId,
            'assigned_by_user_id' => $this->assignedByUserId,
            'platform' => $this->inboxItem->platform->value,
            'author_name' => $this->inboxItem->author_name,
            'content_preview' => mb_substr($this->inboxItem->content ?? '', 0, 100),
            'assigned_at' => now()->toIso8601String(),
        ];
    }
}
