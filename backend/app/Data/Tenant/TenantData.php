<?php

declare(strict_types=1);

namespace App\Data\Tenant;

use App\Models\Tenant\Tenant;
use Spatie\LaravelData\Data;

final class TenantData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public string $type,
        public string $status,
        public ?string $logo_url,
        public ?string $website,
        public ?string $timezone,
        public array $settings,
        public string $created_at,
    ) {}

    /**
     * Create TenantData from a Tenant model.
     */
    public static function fromModel(Tenant $tenant): self
    {
        return new self(
            id: $tenant->id,
            name: $tenant->name,
            slug: $tenant->slug,
            type: $tenant->type->value,
            status: $tenant->status->value,
            logo_url: $tenant->getSetting('branding.logo_url'),
            website: $tenant->profile?->website,
            timezone: $tenant->getSetting('timezone'),
            settings: $tenant->settings ?? [],
            created_at: $tenant->created_at->toIso8601String(),
        );
    }
}
