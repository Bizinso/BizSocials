<?php

declare(strict_types=1);

namespace App\Listeners\Social;

use App\Enums\Notification\NotificationType;
use App\Events\Social\TokenExpiring;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyTokenExpiring implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(TokenExpiring $event): void
    {
        $account = $event->socialAccount;
        $workspace = $account->workspace;

        if ($workspace === null) {
            return;
        }

        $owner = $workspace->owner;

        if ($owner === null) {
            return;
        }

        $this->notificationService->send(
            user: $owner,
            type: NotificationType::ACCOUNT_TOKEN_EXPIRING,
            title: 'Account Token Expiring',
            message: "The access token for \"{$account->account_name}\" expires in {$event->daysUntilExpiry} days. Please reconnect.",
            data: [
                'social_account_id' => $account->id,
                'workspace_id' => $workspace->id,
                'days_until_expiry' => $event->daysUntilExpiry,
            ],
        );
    }
}
