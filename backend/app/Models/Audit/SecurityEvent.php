<?php

declare(strict_types=1);

namespace App\Models\Audit;

use App\Enums\Audit\SecurityEventType;
use App\Enums\Audit\SecuritySeverity;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SecurityEvent Model
 *
 * Represents a security event for tracking security-related activities.
 *
 * @property string $id UUID primary key
 * @property string|null $tenant_id Tenant UUID
 * @property string|null $user_id User UUID
 * @property SecurityEventType $event_type Type of security event
 * @property SecuritySeverity $severity Severity level
 * @property string|null $description Event description
 * @property array|null $metadata Additional metadata
 * @property string|null $ip_address IP address
 * @property string|null $user_agent User agent string
 * @property string|null $country_code Country code
 * @property string|null $city City name
 * @property bool $is_resolved Whether event is resolved
 * @property string|null $resolved_by Resolver admin UUID
 * @property \Carbon\Carbon|null $resolved_at Resolution timestamp
 * @property string|null $resolution_notes Resolution notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Tenant|null $tenant
 * @property-read User|null $user
 * @property-read SuperAdminUser|null $resolver
 *
 * @method static Builder<static> forTenant(string $tenantId)
 * @method static Builder<static> forUser(string $userId)
 * @method static Builder<static> byType(SecurityEventType $type)
 * @method static Builder<static> bySeverity(SecuritySeverity $severity)
 * @method static Builder<static> unresolved()
 * @method static Builder<static> critical()
 * @method static Builder<static> recent(int $limit = 10)
 * @method static Builder<static> fromIp(string $ip)
 */
final class SecurityEvent extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'security_events';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'event_type',
        'severity',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
        'country_code',
        'city',
        'is_resolved',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => SecurityEventType::class,
            'severity' => SecuritySeverity::class,
            'metadata' => 'array',
            'is_resolved' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant.
     *
     * @return BelongsTo<Tenant, SecurityEvent>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the user.
     *
     * @return BelongsTo<User, SecurityEvent>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the admin who resolved this event.
     *
     * @return BelongsTo<SuperAdminUser, SecurityEvent>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(SuperAdminUser::class, 'resolved_by');
    }

    /**
     * Scope to filter by tenant.
     *
     * @param  Builder<SecurityEvent>  $query
     * @return Builder<SecurityEvent>
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by user.
     *
     * @param  Builder<SecurityEvent>  $query
     * @return Builder<SecurityEvent>
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by event type.
     *
     * @param  Builder<SecurityEvent>  $query
     * @return Builder<SecurityEvent>
     */
    public function scopeByType(Builder $query, SecurityEventType $type): Builder
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope to filter by severity.
     *
     * @param  Builder<SecurityEvent>  $query
     * @return Builder<SecurityEvent>
     */
    public function scopeBySeverity(Builder $query, SecuritySeverity $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope to get unresolved events.
     *
     * @param  Builder<SecurityEvent>  $query
     * @return Builder<SecurityEvent>
     */
    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->where('is_resolved', false);
    }

    /**
     * Scope to get critical events.
     *
     * @param  Builder<SecurityEvent>  $query
     * @return Builder<SecurityEvent>
     */
    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('severity', SecuritySeverity::CRITICAL);
    }

    /**
     * Scope to get recent events.
     *
     * @param  Builder<SecurityEvent>  $query
     * @return Builder<SecurityEvent>
     */
    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope to filter by IP address.
     *
     * @param  Builder<SecurityEvent>  $query
     * @return Builder<SecurityEvent>
     */
    public function scopeFromIp(Builder $query, string $ip): Builder
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Check if the event is resolved.
     */
    public function isResolved(): bool
    {
        return $this->is_resolved;
    }

    /**
     * Check if the event is critical.
     */
    public function isCritical(): bool
    {
        return $this->severity === SecuritySeverity::CRITICAL;
    }

    /**
     * Resolve this security event.
     */
    public function resolve(SuperAdminUser $admin, ?string $notes = null): void
    {
        $this->is_resolved = true;
        $this->resolved_by = $admin->id;
        $this->resolved_at = now();
        $this->resolution_notes = $notes;
        $this->save();
    }

    /**
     * Check if this event requires an alert.
     */
    public function requiresAlert(): bool
    {
        return $this->event_type->requiresAlert();
    }
}
