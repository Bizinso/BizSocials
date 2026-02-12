<?php

declare(strict_types=1);

namespace App\Data\WhatsApp;

use App\Models\WhatsApp\WhatsAppOptIn;
use Spatie\LaravelData\Data;

final class WhatsAppOptInData extends Data
{
    public function __construct(
        public string $id,
        public string $workspace_id,
        public string $phone_number,
        public ?string $customer_name,
        public string $source,
        public string $opted_in_at,
        public ?string $opted_out_at,
        public ?string $opt_in_proof,
        public bool $is_active,
        public ?array $tags,
        public string $created_at,
    ) {}

    public static function fromModel(WhatsAppOptIn $optIn): self
    {
        return new self(
            id: $optIn->id,
            workspace_id: $optIn->workspace_id,
            phone_number: $optIn->phone_number,
            customer_name: $optIn->customer_name,
            source: $optIn->source->value,
            opted_in_at: $optIn->opted_in_at->toIso8601String(),
            opted_out_at: $optIn->opted_out_at?->toIso8601String(),
            opt_in_proof: $optIn->opt_in_proof,
            is_active: $optIn->is_active,
            tags: $optIn->tags,
            created_at: $optIn->created_at->toIso8601String(),
        );
    }
}
