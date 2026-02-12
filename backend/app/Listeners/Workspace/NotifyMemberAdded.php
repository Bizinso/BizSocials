<?php

declare(strict_types=1);

namespace App\Listeners\Workspace;

use App\Enums\Notification\NotificationType;
use App\Events\Workspace\MemberAdded;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyMemberAdded implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(MemberAdded $event): void
    {
        $this->notificationService->send(
            user: $event->user,
            type: NotificationType::MEMBER_ADDED,
            title: 'Added to Workspace',
            message: "You have been added to the workspace \"{$event->workspace->name}\".",
            data: [
                'workspace_id' => $event->workspace->id,
                'workspace_name' => $event->workspace->name,
            ],
        );
    }
}
