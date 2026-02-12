<?php

declare(strict_types=1);

namespace App\Listeners\Billing;

use App\Enums\Notification\NotificationType;
use App\Events\Billing\TrialEnding;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyTrialEnding implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(TrialEnding $event): void
    {
        $subscription = $event->subscription;
        $tenant = $subscription->tenant;

        if ($tenant === null) {
            return;
        }

        $owner = $tenant->owner;

        if ($owner === null) {
            return;
        }

        $this->notificationService->send(
            user: $owner,
            type: NotificationType::TRIAL_ENDING,
            title: 'Trial Ending Soon',
            message: "Your trial ends in {$event->daysRemaining} days. Upgrade now to keep all features.",
            data: [
                'subscription_id' => $subscription->id,
                'days_remaining' => $event->daysRemaining,
            ],
        );
    }
}
