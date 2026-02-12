<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\Platform\ConfigCategory;
use App\Models\Platform\PlatformConfig;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class PlatformConfigService extends BaseService
{
    /**
     * List all platform configs.
     *
     * @return Collection<int, PlatformConfig>
     */
    public function list(): Collection
    {
        return PlatformConfig::with(['updatedByAdmin'])
            ->orderBy('category')
            ->orderBy('key')
            ->get();
    }

    /**
     * Get a config by key.
     *
     * @throws ModelNotFoundException
     */
    public function get(string $key): PlatformConfig
    {
        $config = PlatformConfig::with(['updatedByAdmin'])
            ->where('key', $key)
            ->first();

        if ($config === null) {
            throw new ModelNotFoundException('Configuration not found.');
        }

        return $config;
    }

    /**
     * Get config value by key.
     *
     * @return mixed
     */
    public function getValue(string $key, mixed $default = null): mixed
    {
        return PlatformConfig::getValue($key, $default);
    }

    /**
     * Set a config value.
     */
    public function set(
        string $key,
        mixed $value,
        ?ConfigCategory $category = null,
        ?string $description = null,
        bool $isSensitive = false,
        ?string $updatedBy = null
    ): PlatformConfig {
        return $this->transaction(function () use ($key, $value, $category, $description, $isSensitive, $updatedBy) {
            $config = PlatformConfig::firstOrNew(['key' => $key]);

            // Wrap scalar values in an array for consistent JSON storage
            $config->value = is_array($value) ? $value : ['value' => $value];

            if ($category !== null) {
                $config->category = $category;
            } elseif (!$config->exists) {
                // Default category for new configs
                $config->category = ConfigCategory::GENERAL;
            }

            if ($description !== null) {
                $config->description = $description;
            }

            $config->is_sensitive = $isSensitive;

            if ($updatedBy !== null) {
                $config->updated_by = $updatedBy;
            }

            $config->save();

            $this->log('Platform config set', [
                'key' => $key,
                'category' => $config->category->value,
                'updated_by' => $updatedBy,
            ]);

            return $config->fresh(['updatedByAdmin']);
        });
    }

    /**
     * Delete a config.
     */
    public function delete(string $key): void
    {
        $this->transaction(function () use ($key) {
            $config = PlatformConfig::where('key', $key)->first();

            if ($config !== null) {
                $config->delete();

                $this->log('Platform config deleted', [
                    'key' => $key,
                ]);
            }
        });
    }

    /**
     * Get configs by category.
     *
     * @return Collection<int, PlatformConfig>
     */
    public function getByCategory(ConfigCategory $category): Collection
    {
        return PlatformConfig::with(['updatedByAdmin'])
            ->byCategory($category)
            ->orderBy('key')
            ->get();
    }

    /**
     * Get configs grouped by category.
     *
     * @return array<string, Collection<int, PlatformConfig>>
     */
    public function getAllGroupedByCategory(): array
    {
        $configs = $this->list();

        $grouped = [];
        foreach (ConfigCategory::cases() as $category) {
            $grouped[$category->value] = $configs->filter(
                fn (PlatformConfig $config) => $config->category === $category
            )->values();
        }

        return $grouped;
    }

    /**
     * Bulk set multiple configs.
     *
     * @param array<string, mixed> $configs Array of key => value pairs
     */
    public function bulkSet(
        array $configs,
        ?ConfigCategory $category = null,
        ?string $updatedBy = null
    ): void {
        $this->transaction(function () use ($configs, $category, $updatedBy) {
            foreach ($configs as $key => $value) {
                $this->set($key, $value, $category, null, false, $updatedBy);
            }

            $this->log('Platform configs bulk set', [
                'keys' => array_keys($configs),
                'count' => count($configs),
                'updated_by' => $updatedBy,
            ]);
        });
    }

    /**
     * Get all config keys.
     *
     * @return array<string>
     */
    public function getAllKeys(): array
    {
        return PlatformConfig::pluck('key')->toArray();
    }

    /**
     * Check if a config key exists.
     */
    public function exists(string $key): bool
    {
        return PlatformConfig::where('key', $key)->exists();
    }
}
