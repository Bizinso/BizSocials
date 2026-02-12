<?php

declare(strict_types=1);

namespace App\Listeners\Workspace;

use App\Enums\Notification\NotificationType;
use App\Events\Workspace\MemberRemoved;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyMemberRemoved implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(MemberRemoved $event): void
    {
        $user = User::find($event->userId);

        if ($user === null) {
            return;
        }

        $this->notificationService->send(
            user: $user,
            type: NotificationType::MEMBER_REMOVED,
            title: 'Removed from Workspace',
            message: "You have been removed from the workspace \"{$event->workspace->name}\".",
            data: [
                'workspace_id' => $event->workspace->id,
                'workspace_name' => $event->workspace->name,
            ],
        );
    }
}
