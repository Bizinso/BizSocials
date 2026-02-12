<?php

declare(strict_types=1);

namespace App\Listeners\Inbox;

use App\Enums\Notification\NotificationType;
use App\Events\Inbox\NewInboxItemReceived;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyNewInboxItem implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(NewInboxItemReceived $event): void
    {
        $item = $event->inboxItem;
        $workspace = $item->workspace;

        if ($workspace === null) {
            return;
        }

        // Notify assigned user if any
        if ($item->assigned_to_user_id !== null) {
            $assignee = $item->assignedTo;

            if ($assignee !== null) {
                $this->notificationService->send(
                    user: $assignee,
                    type: NotificationType::INBOX_ASSIGNED,
                    title: 'New Inbox Item',
                    message: "New {$item->type->value} from {$item->author_name} on {$item->platform->value}.",
                    data: [
                        'inbox_item_id' => $item->id,
                        'workspace_id' => $workspace->id,
                        'platform' => $item->platform->value,
                    ],
                );
            }
        }
    }
}
