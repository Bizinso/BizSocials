<?php

declare(strict_types=1);

namespace App\Data\Billing;

use Spatie\LaravelData\Data;

final class CheckoutData extends Data
{
    public function __construct(
        public string $subscription_id,
        public string $razorpay_subscription_id,
        public string $razorpay_key_id,
        public string $plan_name,
        public int $amount,
        public string $currency,
    ) {}
}
