<?php

declare(strict_types=1);

namespace App\Listeners\Social;

use App\Enums\Notification\NotificationType;
use App\Events\Social\AccountConnected;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyAccountConnected implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(AccountConnected $event): void
    {
        $account = $event->socialAccount;
        $workspace = $account->workspace;

        if ($workspace === null) {
            return;
        }

        $members = $workspace->members;

        foreach ($members as $member) {
            $this->notificationService->send(
                user: $member,
                type: NotificationType::ACCOUNT_CONNECTED,
                title: 'Social Account Connected',
                message: "{$account->platform_display_name} account \"{$account->account_name}\" has been connected.",
                data: [
                    'social_account_id' => $account->id,
                    'workspace_id' => $workspace->id,
                    'platform' => $account->platform->value,
                ],
            );
        }
    }
}
