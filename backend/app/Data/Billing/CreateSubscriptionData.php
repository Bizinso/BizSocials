<?php

declare(strict_types=1);

namespace App\Data\Billing;

use App\Enums\Billing\BillingCycle;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

final class CreateSubscriptionData extends Data
{
    public function __construct(
        #[Required]
        #[Uuid]
        public string $plan_id,
        public BillingCycle $billing_cycle = BillingCycle::MONTHLY,
        #[Uuid]
        public ?string $payment_method_id = null,
    ) {}
}
