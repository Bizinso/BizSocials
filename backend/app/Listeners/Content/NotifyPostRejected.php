<?php

declare(strict_types=1);

namespace App\Listeners\Content;

use App\Enums\Notification\NotificationType;
use App\Events\Content\PostRejected;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyPostRejected implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(PostRejected $event): void
    {
        $post = $event->post;
        $author = $post->author;

        if ($author === null) {
            return;
        }

        $this->notificationService->send(
            user: $author,
            type: NotificationType::POST_REJECTED,
            title: 'Post Rejected',
            message: "Your post has been rejected by {$event->rejector->name}. Reason: {$event->reason}",
            data: [
                'post_id' => $post->id,
                'workspace_id' => $post->workspace_id,
                'rejector_id' => $event->rejector->id,
                'rejector_name' => $event->rejector->name,
                'reason' => $event->reason,
            ],
        );
    }
}
