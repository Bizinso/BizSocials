<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BelongsToTenant Trait
 *
 * Provides multi-tenant scoping for models that belong to a tenant.
 * Automatically adds global scope to filter records by tenant_id.
 *
 * @property string $tenant_id Tenant UUID
 * @property-read Tenant $tenant
 *
 * @method static Builder<static> forTenant(string $tenantId)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait BelongsToTenant
{
    /**
     * Boot the trait.
     */
    public static function bootBelongsToTenant(): void
    {
        static::creating(function ($model) {
            // Automatically set tenant_id if not set and we have a current tenant
            if (empty($model->tenant_id) && app()->has('current_tenant')) {
                $model->tenant_id = app('current_tenant')->id;
            }
        });

        // Add global scope to filter by tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->has('current_tenant')) {
                $builder->where(
                    $builder->getModel()->getTable() . '.tenant_id',
                    app('current_tenant')->id
                );
            }
        });
    }

    /**
     * Get the tenant that this model belongs to.
     *
     * @return BelongsTo<Tenant, static>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to filter by tenant.
     *
     * @param  Builder<static>  $query
     * @param  string  $tenantId
     * @return Builder<static>
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->withoutGlobalScope('tenant')->where('tenant_id', $tenantId);
    }

    /**
     * Scope to include all tenants (bypass global scope).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }
}
