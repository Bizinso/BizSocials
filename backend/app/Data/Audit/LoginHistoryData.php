<?php

declare(strict_types=1);

namespace App\Data\Audit;

use App\Models\Audit\LoginHistory;
use Spatie\LaravelData\Data;

final class LoginHistoryData extends Data
{
    public function __construct(
        public string $id,
        public string $user_id,
        public bool $is_successful,
        public ?string $failure_reason,
        public string $ip_address,
        public ?string $device_type,
        public ?string $browser,
        public ?string $os,
        public ?string $country_code,
        public ?string $city,
        public ?string $logged_out_at,
        public string $created_at,
    ) {}

    /**
     * Create LoginHistoryData from a LoginHistory model.
     */
    public static function fromModel(LoginHistory $history): self
    {
        return new self(
            id: $history->id,
            user_id: $history->user_id,
            is_successful: $history->successful,
            failure_reason: $history->failure_reason,
            ip_address: $history->ip_address ?? '',
            device_type: $history->device_type,
            browser: $history->browser,
            os: $history->os,
            country_code: $history->country_code,
            city: $history->city,
            logged_out_at: $history->logged_out_at?->toIso8601String(),
            created_at: $history->created_at->toIso8601String(),
        );
    }
}
