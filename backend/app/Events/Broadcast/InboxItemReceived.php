<?php

declare(strict_types=1);

namespace App\Events\Broadcast;

use App\Models\Inbox\InboxItem;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class InboxItemReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly InboxItem $inboxItem,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("workspace.{$this->inboxItem->workspace_id}.inbox"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'inbox.item_received';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->inboxItem->id,
            'platform' => $this->inboxItem->platform->value,
            'type' => $this->inboxItem->type->value,
            'author_name' => $this->inboxItem->author_name,
            'content_preview' => mb_substr($this->inboxItem->content ?? '', 0, 100),
            'created_at' => $this->inboxItem->created_at?->toIso8601String(),
        ];
    }
}
