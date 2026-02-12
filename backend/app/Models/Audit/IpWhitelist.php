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
 * IpWhitelist Model
 *
 * Represents an IP address whitelist entry for a tenant.
 *
 * @property string $id UUID primary key
 * @property string $tenant_id Tenant UUID
 * @property string $ip_address IP address
 * @property string|null $cidr_range CIDR range for IP ranges
 * @property string|null $label Label for this entry
 * @property string|null $description Description
 * @property bool $is_active Whether the entry is active
 * @property string $created_by Creator user UUID
 * @property \Carbon\Carbon|null $expires_at Expiration timestamp
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Tenant $tenant
 * @property-read User $creator
 *
 * @method static Builder<static> forTenant(string $tenantId)
 * @method static Builder<static> active()
 * @method static Builder<static> expired()
 * @method static Builder<static> byIp(string $ip)
 * @method static Builder<static> ordered()
 */
final class IpWhitelist extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ip_whitelist';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'ip_address',
        'cidr_range',
        'label',
        'description',
        'is_active',
        'created_by',
        'expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant.
     *
     * @return BelongsTo<Tenant, IpWhitelist>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the user who created this entry.
     *
     * @return BelongsTo<User, IpWhitelist>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to filter by tenant.
     *
     * @param  Builder<IpWhitelist>  $query
     * @return Builder<IpWhitelist>
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to get active entries.
     *
     * @param  Builder<IpWhitelist>  $query
     * @return Builder<IpWhitelist>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to get expired entries.
     *
     * @param  Builder<IpWhitelist>  $query
     * @return Builder<IpWhitelist>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope to filter by IP address.
     *
     * @param  Builder<IpWhitelist>  $query
     * @return Builder<IpWhitelist>
     */
    public function scopeByIp(Builder $query, string $ip): Builder
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope to order by created date.
     *
     * @param  Builder<IpWhitelist>  $query
     * @return Builder<IpWhitelist>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Check if the entry is active.
     */
    public function isActive(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Check if the entry has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if the given IP is contained in this whitelist entry.
     */
    public function containsIp(string $ip): bool
    {
        if ($this->ip_address === $ip) {
            return true;
        }

        if ($this->cidr_range) {
            return $this->ipInCidr($ip, $this->cidr_range);
        }

        return false;
    }

    /**
     * Check if an IP is within a CIDR range.
     */
    private function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        $bits = (int) $bits;

        $ip = ip2long($ip);
        $subnet = ip2long($subnet);

        if ($ip === false || $subnet === false) {
            return false;
        }

        $mask = -1 << (32 - $bits);
        $subnet &= $mask;

        return ($ip & $mask) === $subnet;
    }

    /**
     * Deactivate this entry.
     */
    public function deactivate(): void
    {
        $this->is_active = false;
        $this->save();
    }

    /**
     * Activate this entry.
     */
    public function activate(): void
    {
        $this->is_active = true;
        $this->save();
    }
}
