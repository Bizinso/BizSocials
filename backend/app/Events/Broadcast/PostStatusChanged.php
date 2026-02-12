<?php

declare(strict_types=1);

namespace App\Events\Broadcast;

use App\Models\Content\Post;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PostStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Post $post,
        public readonly string $previousStatus,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("workspace.{$this->post->workspace_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'post.status_changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'post_id' => $this->post->id,
            'title' => $this->post->title,
            'status' => $this->post->status->value,
            'previous_status' => $this->previousStatus,
            'updated_at' => $this->post->updated_at?->toIso8601String(),
        ];
    }
}
