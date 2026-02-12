<?php

declare(strict_types=1);

namespace App\Data\Audit;

use App\Models\Audit\SessionHistory;
use Spatie\LaravelData\Data;

final class SessionData extends Data
{
    public function __construct(
        public string $id,
        public string $user_id,
        public string $status,
        public string $ip_address,
        public ?string $device_type,
        public ?string $device_name,
        public ?string $browser,
        public ?string $os,
        public ?string $country_code,
        public ?string $city,
        public bool $is_current,
        public ?string $last_activity_at,
        public ?string $expires_at,
        public ?string $revoked_at,
        public string $created_at,
    ) {}

    /**
     * Create SessionData from a SessionHistory model.
     */
    public static function fromModel(SessionHistory $session): self
    {
        return new self(
            id: $session->id,
            user_id: $session->user_id,
            status: $session->status->value,
            ip_address: $session->ip_address ?? '',
            device_type: $session->device_type,
            device_name: $session->device_name,
            browser: $session->browser,
            os: $session->os,
            country_code: $session->country_code,
            city: $session->city,
            is_current: $session->is_current,
            last_activity_at: $session->last_activity_at?->toIso8601String(),
            expires_at: $session->expires_at?->toIso8601String(),
            revoked_at: $session->revoked_at?->toIso8601String(),
            created_at: $session->created_at->toIso8601String(),
        );
    }
}
