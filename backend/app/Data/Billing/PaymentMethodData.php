<?php

declare(strict_types=1);

namespace App\Data\Billing;

use App\Enums\Billing\PaymentMethodType;
use App\Models\Billing\PaymentMethod;
use Spatie\LaravelData\Data;

final class PaymentMethodData extends Data
{
    public function __construct(
        public string $id,
        public string $type,
        public string $type_label,
        public bool $is_default,
        public ?string $card_last_four,
        public ?string $card_brand,
        public ?string $card_exp_month,
        public ?string $card_exp_year,
        public ?string $bank_name,
        public ?string $upi_id,
        public string $display_name,
        public bool $is_expired,
        public string $created_at,
    ) {}

    /**
     * Create PaymentMethodData from a PaymentMethod model.
     */
    public static function fromModel(PaymentMethod $method): self
    {
        $details = $method->details ?? [];

        return new self(
            id: $method->id,
            type: $method->type->value,
            type_label: $method->type->label(),
            is_default: $method->is_default,
            card_last_four: $method->type === PaymentMethodType::CARD ? ($details['last4'] ?? null) : null,
            card_brand: $method->type === PaymentMethodType::CARD ? ($details['brand'] ?? null) : null,
            card_exp_month: $method->type === PaymentMethodType::CARD ? (isset($details['exp_month']) ? (string) $details['exp_month'] : null) : null,
            card_exp_year: $method->type === PaymentMethodType::CARD ? (isset($details['exp_year']) ? (string) $details['exp_year'] : null) : null,
            bank_name: in_array($method->type, [PaymentMethodType::NETBANKING, PaymentMethodType::EMANDATE], true) ? ($details['bank'] ?? null) : null,
            upi_id: $method->type === PaymentMethodType::UPI ? ($details['vpa'] ?? null) : null,
            display_name: $method->getDisplayName(),
            is_expired: $method->isExpired(),
            created_at: $method->created_at->toIso8601String(),
        );
    }
}
