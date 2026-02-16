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
 * InboxMessageReplied Event
 *
 * Broadcast when a reply is sent to an inbox message.
 * This enables real-time updates in the inbox UI.
 */
final class InboxMessageReplied implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly InboxItem $inboxItem,
        public readonly string $replyContent,
        public readonly string $repliedBy,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("workspace.{$this->inboxItem->workspace_id}.inbox"),
        ];
    }

    /**
     * Get the event name for broadcasting.
     */
    public function broadcastAs(): string
    {
        return 'inbox.message_replied';
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
            'reply_content' => mb_substr($this->replyContent, 0, 200),
            'replied_by' => $this->repliedBy,
            'replied_at' => now()->toIso8601String(),
        ];
    }
}
