<?php

declare(strict_types=1);

namespace App\Data\Admin;

use App\Models\Platform\FeatureFlag;
use Spatie\LaravelData\Data;

final class FeatureFlagData extends Data
{
    public function __construct(
        public string $id,
        public string $key,
        public string $name,
        public ?string $description,
        public bool $is_enabled,
        public int $rollout_percentage,
        /** @var array<string>|null */
        public ?array $allowed_plans,
        /** @var array<string>|null */
        public ?array $allowed_tenants,
        public ?array $metadata,
        public string $created_at,
        public string $updated_at,
    ) {}

    /**
     * Create FeatureFlagData from a FeatureFlag model.
     */
    public static function fromModel(FeatureFlag $flag): self
    {
        return new self(
            id: $flag->id,
            key: $flag->key,
            name: $flag->name,
            description: $flag->description,
            is_enabled: $flag->is_enabled,
            rollout_percentage: $flag->rollout_percentage,
            allowed_plans: $flag->allowed_plans,
            allowed_tenants: $flag->allowed_tenants,
            metadata: $flag->metadata,
            created_at: $flag->created_at->toIso8601String(),
            updated_at: $flag->updated_at->toIso8601String(),
        );
    }
}
