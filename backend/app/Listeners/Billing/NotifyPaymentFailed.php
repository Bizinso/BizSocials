<?php

declare(strict_types=1);

namespace App\Listeners\Billing;

use App\Enums\Notification\NotificationType;
use App\Events\Billing\PaymentFailed;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyPaymentFailed implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(PaymentFailed $event): void
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
            type: NotificationType::PAYMENT_FAILED,
            title: 'Payment Failed',
            message: "Your payment has failed: {$event->reason}. Please update your payment method.",
            data: [
                'subscription_id' => $subscription->id,
                'reason' => $event->reason,
            ],
        );
    }
}
