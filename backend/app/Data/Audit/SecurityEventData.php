<?php

declare(strict_types=1);

namespace App\Data\Audit;

use App\Models\Audit\SecurityEvent;
use Spatie\LaravelData\Data;

final class SecurityEventData extends Data
{
    public function __construct(
        public string $id,
        public string $event_type,
        public string $severity,
        public ?string $user_id,
        public ?string $user_name,
        public ?string $ip_address,
        public ?string $user_agent,
        public ?string $country_code,
        public ?string $city,
        public ?string $description,
        public ?array $metadata,
        public bool $is_resolved,
        public ?string $resolved_by,
        public ?string $resolved_at,
        public ?string $resolution_notes,
        public string $created_at,
    ) {}

    /**
     * Create SecurityEventData from a SecurityEvent model.
     */
    public static function fromModel(SecurityEvent $event): self
    {
        $event->loadMissing(['user', 'resolver']);

        return new self(
            id: $event->id,
            event_type: $event->event_type->value,
            severity: $event->severity->value,
            user_id: $event->user_id,
            user_name: $event->user?->name,
            ip_address: $event->ip_address,
            user_agent: $event->user_agent,
            country_code: $event->country_code,
            city: $event->city,
            description: $event->description,
            metadata: $event->metadata,
            is_resolved: $event->is_resolved,
            resolved_by: $event->resolved_by,
            resolved_at: $event->resolved_at?->toIso8601String(),
            resolution_notes: $event->resolution_notes,
            created_at: $event->created_at->toIso8601String(),
        );
    }
}
