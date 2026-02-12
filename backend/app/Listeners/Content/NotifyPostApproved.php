<?php

declare(strict_types=1);

namespace App\Listeners\Content;

use App\Enums\Notification\NotificationType;
use App\Events\Content\PostApproved;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyPostApproved implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(PostApproved $event): void
    {
        $post = $event->post;
        $author = $post->author;

        if ($author === null) {
            return;
        }

        $this->notificationService->send(
            user: $author,
            type: NotificationType::POST_APPROVED,
            title: 'Post Approved',
            message: "Your post has been approved by {$event->approver->name}.",
            data: [
                'post_id' => $post->id,
                'workspace_id' => $post->workspace_id,
                'approver_id' => $event->approver->id,
                'approver_name' => $event->approver->name,
            ],
        );
    }
}
