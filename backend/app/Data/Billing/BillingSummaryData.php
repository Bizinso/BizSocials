<?php

declare(strict_types=1);

namespace App\Data\Billing;

use Spatie\LaravelData\Data;

final class BillingSummaryData extends Data
{
    public function __construct(
        public ?SubscriptionData $current_subscription,
        public ?string $next_billing_date,
        public ?string $next_billing_amount,
        public int $total_invoices,
        public string $total_paid,
        public ?PaymentMethodData $default_payment_method,
    ) {}
}
