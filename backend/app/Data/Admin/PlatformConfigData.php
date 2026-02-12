<?php

declare(strict_types=1);

namespace App\Data\Admin;

use App\Models\Platform\PlatformConfig;
use Spatie\LaravelData\Data;

final class PlatformConfigData extends Data
{
    public function __construct(
        public string $id,
        public string $key,
        public mixed $value,
        public string $category,
        public string $category_label,
        public ?string $description,
        public bool $is_sensitive,
        public ?string $updated_by,
        public ?string $updated_by_name,
        public string $created_at,
        public string $updated_at,
    ) {}

    /**
     * Create PlatformConfigData from a PlatformConfig model.
     */
    public static function fromModel(PlatformConfig $config, bool $maskSensitive = true): self
    {
        $config->loadMissing(['updatedByAdmin']);

        // Get the actual value from the JSON storage
        $value = $config->value;
        if (is_array($value) && array_key_exists('value', $value)) {
            $value = $value['value'];
        }

        // Mask sensitive values
        if ($maskSensitive && $config->is_sensitive) {
            $value = '********';
        }

        return new self(
            id: $config->id,
            key: $config->key,
            value: $value,
            category: $config->category->value,
            category_label: $config->category->label(),
            description: $config->description,
            is_sensitive: $config->is_sensitive,
            updated_by: $config->updated_by,
            updated_by_name: $config->updatedByAdmin?->name,
            created_at: $config->created_at->toIso8601String(),
            updated_at: $config->updated_at->toIso8601String(),
        );
    }
}
