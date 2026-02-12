<?php

declare(strict_types=1);

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PlanLimit Model
 *
 * Represents limits/quotas for each subscription plan.
 * Each limit is identified by a key and has a numeric value.
 * A value of -1 indicates unlimited.
 *
 * @property string $id UUID primary key
 * @property string $plan_id Foreign key to plan_definitions
 * @property string $limit_key Limit identifier
 * @property int $limit_value Limit value (-1 = unlimited)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read PlanDefinition $plan
 */
final class PlanLimit extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * Value indicating unlimited.
     */
    public const UNLIMITED = -1;

    /**
     * Available limit keys.
     *
     * @var array<string>
     */
    public const LIMIT_KEYS = [
        'max_workspaces',
        'max_users',
        'max_social_accounts',
        'max_posts_per_month',
        'max_scheduled_posts',
        'max_team_members_per_workspace',
        'max_storage_gb',
        'max_api_calls_per_day',
        'ai_requests_per_month',
        'analytics_history_days',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'plan_limits';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'plan_id',
        'limit_key',
        'limit_value',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'limit_value' => 'integer',
        ];
    }

    /**
     * Get the plan definition that owns this limit.
     *
     * @return BelongsTo<PlanDefinition, PlanLimit>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanDefinition::class, 'plan_id');
    }

    /**
     * Check if this limit is unlimited.
     */
    public function isUnlimited(): bool
    {
        return $this->limit_value === self::UNLIMITED;
    }

    /**
     * Check if a given value exceeds this limit.
     *
     * Returns false if the limit is unlimited.
     */
    public function exceedsLimit(int $value): bool
    {
        if ($this->isUnlimited()) {
            return false;
        }

        return $value > $this->limit_value;
    }

    /**
     * Get the remaining quota given current usage.
     *
     * Returns null if unlimited, 0 if exceeded, otherwise remaining.
     */
    public function getRemainingQuota(int $currentUsage): ?int
    {
        if ($this->isUnlimited()) {
            return null;
        }

        return max(0, $this->limit_value - $currentUsage);
    }

    /**
     * Check if a limit key is valid.
     */
    public static function isValidLimitKey(string $key): bool
    {
        return in_array($key, self::LIMIT_KEYS, true);
    }
}
