<?php

declare(strict_types=1);

namespace App\Listeners\Billing;

use App\Enums\Notification\NotificationType;
use App\Events\Billing\SubscriptionCreated;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifySubscriptionCreated implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(SubscriptionCreated $event): void
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
            type: NotificationType::SUBSCRIPTION_CREATED,
            title: 'Subscription Created',
            message: "Your subscription to the {$subscription->plan?->name} plan has been activated.",
            data: [
                'subscription_id' => $subscription->id,
                'plan_name' => $subscription->plan?->name,
            ],
        );
    }
}
