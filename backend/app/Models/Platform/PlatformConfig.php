<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Enums\Platform\ConfigCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PlatformConfig Model
 *
 * Represents global platform configuration settings.
 * These settings control platform-wide behavior and can be
 * managed by super admin users through the admin panel.
 *
 * @property string $id UUID primary key
 * @property string $key Unique configuration key
 * @property array<string, mixed> $value Configuration value (JSON)
 * @property ConfigCategory $category Configuration category
 * @property string|null $description Human-readable description
 * @property bool $is_sensitive Whether the value contains sensitive data
 * @property string|null $updated_by UUID of last admin to update
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read SuperAdminUser|null $updatedByAdmin
 *
 * @method static Builder<static> byCategory(ConfigCategory $category)
 */
final class PlatformConfig extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'platform_configs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'category',
        'description',
        'is_sensitive',
        'updated_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
            'category' => ConfigCategory::class,
            'is_sensitive' => 'boolean',
        ];
    }

    /**
     * Get the admin who last updated this config.
     *
     * @return BelongsTo<SuperAdminUser, PlatformConfig>
     */
    public function updatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(SuperAdminUser::class, 'updated_by');
    }

    /**
     * Scope to filter configs by category.
     *
     * @param  Builder<PlatformConfig>  $query
     * @return Builder<PlatformConfig>
     */
    public function scopeByCategory(Builder $query, ConfigCategory $category): Builder
    {
        return $query->where('category', $category->value);
    }

    /**
     * Get a configuration value by key.
     *
     * Returns the raw value from the JSON field, or the default
     * if the configuration key doesn't exist.
     *
     * @param  mixed  $default  Default value if key not found
     * @return mixed
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $config = self::where('key', $key)->first();

        if ($config === null) {
            return $default;
        }

        // The value is stored as JSON, but for simple values we wrap them
        // Return the 'value' key if it exists, otherwise return the whole array
        $value = $config->value;

        if (is_array($value) && array_key_exists('value', $value)) {
            return $value['value'];
        }

        return $value;
    }

    /**
     * Set a configuration value by key.
     *
     * Creates or updates the configuration entry.
     *
     * @param  mixed  $value
     */
    public static function setValue(
        string $key,
        mixed $value,
        ?ConfigCategory $category = null,
        ?string $updatedBy = null
    ): self {
        $config = self::firstOrNew(['key' => $key]);

        // Wrap scalar values in an array for consistent JSON storage
        $config->value = is_array($value) ? $value : ['value' => $value];

        if ($category !== null) {
            $config->category = $category;
        }

        if ($updatedBy !== null) {
            $config->updated_by = $updatedBy;
        }

        $config->save();

        return $config;
    }
}
