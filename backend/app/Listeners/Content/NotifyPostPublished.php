<?php

declare(strict_types=1);

namespace App\Listeners\Content;

use App\Enums\Notification\NotificationType;
use App\Events\Content\PostPublished;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyPostPublished implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(PostPublished $event): void
    {
        $post = $event->post;
        $author = $post->author;

        if ($author === null) {
            return;
        }

        $this->notificationService->send(
            user: $author,
            type: NotificationType::POST_PUBLISHED,
            title: 'Post Published',
            message: 'Your post has been published successfully.',
            data: [
                'post_id' => $post->id,
                'workspace_id' => $post->workspace_id,
            ],
        );
    }
}
