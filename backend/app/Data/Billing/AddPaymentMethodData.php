<?php

declare(strict_types=1);

namespace App\Data\Billing;

use App\Enums\Billing\PaymentMethodType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class AddPaymentMethodData extends Data
{
    public function __construct(
        #[Required]
        public PaymentMethodType $type,
        public bool $is_default = false,
        // Card details (stubbed - in real implementation this would be a Razorpay token)
        public ?string $card_token = null,
        // UPI details
        public ?string $upi_id = null,
        // Card details for testing/stubbing
        public ?string $card_last4 = null,
        public ?string $card_brand = null,
        public ?int $card_exp_month = null,
        public ?int $card_exp_year = null,
        // Bank details for netbanking/emandate
        public ?string $bank_name = null,
    ) {}
}
