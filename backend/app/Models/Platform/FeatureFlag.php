<?php

declare(strict_types=1);

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * FeatureFlag Model
 *
 * Represents feature toggles for gradual rollout of new features.
 * Supports percentage-based rollout, plan-based access control,
 * and tenant-specific overrides.
 *
 * @property string $id UUID primary key
 * @property string $key Unique feature key (e.g., 'ai.caption_generation')
 * @property string $name Human-readable feature name
 * @property string|null $description Feature description
 * @property bool $is_enabled Global enable/disable switch
 * @property int $rollout_percentage Percentage of users for gradual rollout (0-100)
 * @property array<string>|null $allowed_plans Plan codes that have access
 * @property array<string>|null $allowed_tenants Tenant IDs with explicit access
 * @property array<string, mixed>|null $metadata Additional metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @method static Builder<static> enabled()
 */
final class FeatureFlag extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'feature_flags';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'name',
        'description',
        'is_enabled',
        'rollout_percentage',
        'allowed_plans',
        'allowed_tenants',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'rollout_percentage' => 'integer',
            'allowed_plans' => 'array',
            'allowed_tenants' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Scope to get only enabled feature flags.
     *
     * @param  Builder<FeatureFlag>  $query
     * @return Builder<FeatureFlag>
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Check if the feature is enabled for a specific tenant and plan.
     *
     * Priority order:
     * 1. If the feature is globally disabled, return false
     * 2. If tenant is in allowed_tenants, return true
     * 3. If allowed_plans is set and plan is not in it, return false
     * 4. Return based on rollout percentage
     */
    public function isEnabledForTenant(string $tenantId, string $planCode): bool
    {
        // Feature must be globally enabled
        if (! $this->is_enabled) {
            return false;
        }

        // Check tenant allowlist (explicit override)
        if ($this->allowed_tenants !== null && in_array($tenantId, $this->allowed_tenants, true)) {
            return true;
        }

        // Check plan restrictions
        if ($this->allowed_plans !== null && ! in_array($planCode, $this->allowed_plans, true)) {
            return false;
        }

        // Use rollout percentage with consistent hashing
        return $this->isEnabledWithRollout($tenantId);
    }

    /**
     * Check if the feature is enabled based on rollout percentage.
     *
     * Uses consistent hashing to ensure the same identifier always
     * gets the same result (important for user experience consistency).
     *
     * @param  string  $identifier  Unique identifier (tenant ID, user ID, etc.)
     */
    public function isEnabledWithRollout(string $identifier): bool
    {
        // If rollout is 100%, always enabled
        if ($this->rollout_percentage >= 100) {
            return true;
        }

        // If rollout is 0%, always disabled
        if ($this->rollout_percentage <= 0) {
            return false;
        }

        // Create a consistent hash from the feature key and identifier
        // This ensures the same tenant always gets the same result
        $hash = crc32($this->key . ':' . $identifier);

        // Map hash to 0-99 range
        $bucket = abs($hash) % 100;

        // Enable if bucket is less than rollout percentage
        return $bucket < $this->rollout_percentage;
    }

    /**
     * Get a feature flag by key.
     */
    public static function getByKey(string $key): ?self
    {
        return self::where('key', $key)->first();
    }

    /**
     * Check if a feature is enabled for a tenant by key.
     */
    public static function isEnabled(string $key, string $tenantId, string $planCode): bool
    {
        $flag = self::getByKey($key);

        if ($flag === null) {
            return false;
        }

        return $flag->isEnabledForTenant($tenantId, $planCode);
    }
}
