<?php

declare(strict_types=1);

namespace App\Data\WhatsApp;

use App\Models\WhatsApp\WhatsAppBusinessAccount;
use Illuminate\Database\Eloquent\Collection;
use Spatie\LaravelData\Data;

final class WhatsAppBusinessAccountData extends Data
{
    public function __construct(
        public string $id,
        public string $tenant_id,
        public string $waba_id,
        public string $name,
        public string $status,
        public string $quality_rating,
        public string $messaging_limit_tier,
        public bool $is_marketing_enabled,
        public ?string $compliance_accepted_at,
        public ?string $suspended_reason,
        public ?array $phone_numbers,
        public string $created_at,
        public string $updated_at,
    ) {}

    public static function fromModel(WhatsAppBusinessAccount $account): self
    {
        return new self(
            id: $account->id,
            tenant_id: $account->tenant_id,
            waba_id: $account->waba_id,
            name: $account->name,
            status: $account->status->value,
            quality_rating: $account->quality_rating->value,
            messaging_limit_tier: $account->messaging_limit_tier->value,
            is_marketing_enabled: $account->is_marketing_enabled,
            compliance_accepted_at: $account->compliance_accepted_at?->toIso8601String(),
            suspended_reason: $account->suspended_reason,
            phone_numbers: $account->relationLoaded('phoneNumbers')
                ? WhatsAppPhoneNumberData::collection($account->phoneNumbers)->toArray()
                : null,
            created_at: $account->created_at->toIso8601String(),
            updated_at: $account->updated_at->toIso8601String(),
        );
    }

    /** @return array<int, self> */
    public static function collection(Collection $accounts): array
    {
        return $accounts->map(fn (WhatsAppBusinessAccount $a) => self::fromModel($a))->toArray();
    }
}
