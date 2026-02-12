<?php

declare(strict_types=1);

namespace App\Listeners\Content;

use App\Enums\Notification\NotificationType;
use App\Events\Content\PostFailed;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyPostFailed implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(PostFailed $event): void
    {
        $post = $event->post;
        $author = $post->author;

        if ($author === null) {
            return;
        }

        $this->notificationService->send(
            user: $author,
            type: NotificationType::POST_FAILED,
            title: 'Post Publishing Failed',
            message: "Your post failed to publish: {$event->reason}",
            data: [
                'post_id' => $post->id,
                'workspace_id' => $post->workspace_id,
                'reason' => $event->reason,
            ],
        );
    }
}
