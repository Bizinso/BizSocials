<?php

declare(strict_types=1);

namespace App\Models\Audit;

use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ApiAccessLog Model
 *
 * Represents an API access log entry for tracking API requests.
 *
 * @property string $id UUID primary key
 * @property string|null $tenant_id Tenant UUID
 * @property string|null $user_id User UUID
 * @property string|null $api_key_id API Key UUID
 * @property string $method HTTP method
 * @property string $endpoint API endpoint
 * @property int $status_code HTTP status code
 * @property int|null $response_time_ms Response time in milliseconds
 * @property int|null $request_size_bytes Request size in bytes
 * @property int|null $response_size_bytes Response size in bytes
 * @property string|null $ip_address IP address
 * @property string|null $user_agent User agent string
 * @property array|null $request_headers Request headers
 * @property array|null $request_params Request parameters
 * @property string|null $error_message Error message
 * @property string|null $request_id Request ID for correlation
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Tenant|null $tenant
 * @property-read User|null $user
 *
 * @method static Builder<static> forTenant(string $tenantId)
 * @method static Builder<static> forUser(string $userId)
 * @method static Builder<static> byEndpoint(string $endpoint)
 * @method static Builder<static> byStatus(int $statusCode)
 * @method static Builder<static> errors()
 * @method static Builder<static> slow(int $thresholdMs = 1000)
 * @method static Builder<static> recent(int $limit = 10)
 */
final class ApiAccessLog extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'api_access_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'api_key_id',
        'method',
        'endpoint',
        'status_code',
        'response_time_ms',
        'request_size_bytes',
        'response_size_bytes',
        'ip_address',
        'user_agent',
        'request_headers',
        'request_params',
        'error_message',
        'request_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'response_time_ms' => 'integer',
            'request_size_bytes' => 'integer',
            'response_size_bytes' => 'integer',
            'request_headers' => 'array',
            'request_params' => 'array',
        ];
    }

    /**
     * Get the tenant.
     *
     * @return BelongsTo<Tenant, ApiAccessLog>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the user.
     *
     * @return BelongsTo<User, ApiAccessLog>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope to filter by tenant.
     *
     * @param  Builder<ApiAccessLog>  $query
     * @return Builder<ApiAccessLog>
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by user.
     *
     * @param  Builder<ApiAccessLog>  $query
     * @return Builder<ApiAccessLog>
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by endpoint.
     *
     * @param  Builder<ApiAccessLog>  $query
     * @return Builder<ApiAccessLog>
     */
    public function scopeByEndpoint(Builder $query, string $endpoint): Builder
    {
        return $query->where('endpoint', 'like', "%{$endpoint}%");
    }

    /**
     * Scope to filter by status code.
     *
     * @param  Builder<ApiAccessLog>  $query
     * @return Builder<ApiAccessLog>
     */
    public function scopeByStatus(Builder $query, int $statusCode): Builder
    {
        return $query->where('status_code', $statusCode);
    }

    /**
     * Scope to get error responses.
     *
     * @param  Builder<ApiAccessLog>  $query
     * @return Builder<ApiAccessLog>
     */
    public function scopeErrors(Builder $query): Builder
    {
        return $query->where('status_code', '>=', 400);
    }

    /**
     * Scope to get slow requests.
     *
     * @param  Builder<ApiAccessLog>  $query
     * @return Builder<ApiAccessLog>
     */
    public function scopeSlow(Builder $query, int $thresholdMs = 1000): Builder
    {
        return $query->where('response_time_ms', '>=', $thresholdMs);
    }

    /**
     * Scope to get recent logs.
     *
     * @param  Builder<ApiAccessLog>  $query
     * @return Builder<ApiAccessLog>
     */
    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Check if the response was successful (2xx).
     */
    public function isSuccess(): bool
    {
        return $this->status_code >= 200 && $this->status_code < 300;
    }

    /**
     * Check if the response was an error (4xx or 5xx).
     */
    public function isError(): bool
    {
        return $this->status_code >= 400;
    }

    /**
     * Check if the request was slow.
     */
    public function isSlow(int $thresholdMs = 1000): bool
    {
        return $this->response_time_ms !== null && $this->response_time_ms >= $thresholdMs;
    }

    /**
     * Get formatted response time.
     */
    public function getFormattedResponseTime(): string
    {
        if ($this->response_time_ms === null) {
            return 'N/A';
        }

        if ($this->response_time_ms >= 1000) {
            return number_format($this->response_time_ms / 1000, 2) . 's';
        }

        return $this->response_time_ms . 'ms';
    }
}
