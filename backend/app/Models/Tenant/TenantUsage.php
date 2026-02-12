<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * TenantUsage Model
 *
 * Tracks usage metrics for a tenant per billing period.
 * Used for billing calculations, limit enforcement, and analytics.
 *
 * @property string $id UUID primary key
 * @property string $tenant_id Parent tenant UUID
 * @property Carbon $period_start Start of billing period
 * @property Carbon $period_end End of billing period
 * @property string $metric_key Metric identifier
 * @property int $metric_value Metric value
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Tenant $tenant
 *
 * @method static Builder<static> forTenant(string $tenantId)
 * @method static Builder<static> forPeriod(string $start, string $end)
 * @method static Builder<static> forMetric(string $metricKey)
 * @method static Builder<static> currentPeriod()
 */
final class TenantUsage extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tenant_usage';

    /**
     * Available metric keys for usage tracking.
     */
    public const METRIC_KEYS = [
        'workspaces_count',
        'users_count',
        'social_accounts_count',
        'posts_published',
        'posts_scheduled',
        'storage_bytes_used',
        'api_calls',
        'ai_requests',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'period_start',
        'period_end',
        'metric_key',
        'metric_value',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'metric_value' => 'integer',
        ];
    }

    /**
     * Get the parent tenant.
     *
     * @return BelongsTo<Tenant, TenantUsage>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to filter by tenant.
     *
     * @param  Builder<TenantUsage>  $query
     * @return Builder<TenantUsage>
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by date range.
     *
     * @param  Builder<TenantUsage>  $query
     * @return Builder<TenantUsage>
     */
    public function scopeForPeriod(Builder $query, string $start, string $end): Builder
    {
        return $query->whereDate('period_start', '>=', $start)
            ->whereDate('period_end', '<=', $end);
    }

    /**
     * Scope to filter by metric key.
     *
     * @param  Builder<TenantUsage>  $query
     * @return Builder<TenantUsage>
     */
    public function scopeForMetric(Builder $query, string $metricKey): Builder
    {
        return $query->where('metric_key', $metricKey);
    }

    /**
     * Scope to get current billing period records.
     *
     * @param  Builder<TenantUsage>  $query
     * @return Builder<TenantUsage>
     */
    public function scopeCurrentPeriod(Builder $query): Builder
    {
        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        return $query->whereDate('period_start', $periodStart)
            ->whereDate('period_end', $periodEnd);
    }

    /**
     * Increment a metric value for the current period.
     */
    public static function incrementMetric(string $tenantId, string $metricKey, int $amount = 1): void
    {
        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        $usage = self::where('tenant_id', $tenantId)
            ->whereDate('period_start', $periodStart)
            ->where('metric_key', $metricKey)
            ->first();

        if ($usage === null) {
            $usage = self::create([
                'tenant_id' => $tenantId,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'metric_key' => $metricKey,
                'metric_value' => 0,
            ]);
        }

        $usage->metric_value += $amount;
        $usage->save();
    }

    /**
     * Decrement a metric value for the current period.
     * Will not go below 0.
     */
    public static function decrementMetric(string $tenantId, string $metricKey, int $amount = 1): void
    {
        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        $usage = self::where('tenant_id', $tenantId)
            ->whereDate('period_start', $periodStart)
            ->where('metric_key', $metricKey)
            ->first();

        if ($usage === null) {
            $usage = self::create([
                'tenant_id' => $tenantId,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'metric_key' => $metricKey,
                'metric_value' => 0,
            ]);
        }

        $usage->metric_value = max(0, $usage->metric_value - $amount);
        $usage->save();
    }

    /**
     * Set an absolute metric value for the current period.
     */
    public static function setMetric(string $tenantId, string $metricKey, int $value): void
    {
        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        $usage = self::where('tenant_id', $tenantId)
            ->whereDate('period_start', $periodStart)
            ->where('metric_key', $metricKey)
            ->first();

        if ($usage === null) {
            self::create([
                'tenant_id' => $tenantId,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'metric_key' => $metricKey,
                'metric_value' => $value,
            ]);
        } else {
            $usage->metric_value = $value;
            $usage->save();
        }
    }

    /**
     * Get a metric value for the current period.
     * Returns 0 if not found.
     */
    public static function getMetric(string $tenantId, string $metricKey): int
    {
        $periodStart = Carbon::now()->startOfMonth();

        $usage = self::where('tenant_id', $tenantId)
            ->whereDate('period_start', $periodStart)
            ->where('metric_key', $metricKey)
            ->first();

        return $usage?->metric_value ?? 0;
    }

    /**
     * Get all usage metrics for a tenant in the current period.
     *
     * @return array<string, int>
     */
    public static function getCurrentPeriodUsage(string $tenantId): array
    {
        $periodStart = Carbon::now()->startOfMonth();

        $records = self::where('tenant_id', $tenantId)
            ->whereDate('period_start', $periodStart)
            ->get();

        $usage = [];
        foreach (self::METRIC_KEYS as $key) {
            $usage[$key] = 0;
        }

        foreach ($records as $record) {
            $usage[$record->metric_key] = $record->metric_value;
        }

        return $usage;
    }
}
