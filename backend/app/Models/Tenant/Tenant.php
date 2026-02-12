<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\TenantStatus;
use App\Enums\Tenant\TenantType;
use App\Models\Platform\PlanDefinition;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

/**
 * Tenant Model
 *
 * Represents a customer organization in the multi-tenant platform.
 * Tenants are the billing entities that own workspaces, users, and content.
 *
 * @property string $id UUID primary key
 * @property string $name Tenant display name
 * @property string $slug URL-safe unique identifier
 * @property TenantType $type Tenant type (B2B, B2C, Individual, etc.)
 * @property TenantStatus $status Account status
 * @property string|null $owner_user_id Owner user UUID
 * @property string|null $plan_id Subscription plan UUID
 * @property \Carbon\Carbon|null $trial_ends_at Trial period end date
 * @property array|null $settings Tenant-wide configuration
 * @property \Carbon\Carbon|null $onboarding_completed_at Onboarding completion timestamp
 * @property array|null $metadata Additional metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read PlanDefinition|null $plan
 * @property-read TenantProfile|null $profile
 * @property-read TenantOnboarding|null $onboarding
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TenantUsage> $usageRecords
 *
 * @method static Builder<static> active()
 * @method static Builder<static> ofType(TenantType $type)
 * @method static Builder<static> onTrial()
 */
final class Tenant extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tenants';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'type',
        'status',
        'owner_user_id',
        'plan_id',
        'trial_ends_at',
        'settings',
        'onboarding_completed_at',
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
            'type' => TenantType::class,
            'status' => TenantStatus::class,
            'trial_ends_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'settings' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the subscription plan for this tenant.
     *
     * @return BelongsTo<PlanDefinition, Tenant>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanDefinition::class, 'plan_id');
    }

    /**
     * Get the business profile for this tenant.
     *
     * @return HasOne<TenantProfile>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(TenantProfile::class);
    }

    /**
     * Get the onboarding record for this tenant.
     *
     * @return HasOne<TenantOnboarding>
     */
    public function onboarding(): HasOne
    {
        return $this->hasOne(TenantOnboarding::class);
    }

    /**
     * Get all usage records for this tenant.
     *
     * @return HasMany<TenantUsage>
     */
    public function usageRecords(): HasMany
    {
        return $this->hasMany(TenantUsage::class);
    }

    /**
     * Get all users belonging to this tenant.
     *
     * @return HasMany<User>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all workspaces belonging to this tenant.
     *
     * @return HasMany<Workspace>
     */
    public function workspaces(): HasMany
    {
        return $this->hasMany(Workspace::class);
    }

    /**
     * Scope to get only active tenants.
     *
     * @param  Builder<Tenant>  $query
     * @return Builder<Tenant>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', TenantStatus::ACTIVE);
    }

    /**
     * Scope to filter by tenant type.
     *
     * @param  Builder<Tenant>  $query
     * @return Builder<Tenant>
     */
    public function scopeOfType(Builder $query, TenantType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get tenants currently on trial.
     *
     * @param  Builder<Tenant>  $query
     * @return Builder<Tenant>
     */
    public function scopeOnTrial(Builder $query): Builder
    {
        return $query->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now());
    }

    /**
     * Check if the tenant is active.
     */
    public function isActive(): bool
    {
        return $this->status === TenantStatus::ACTIVE;
    }

    /**
     * Check if the tenant is currently on trial.
     */
    public function isOnTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    /**
     * Get the number of trial days remaining.
     */
    public function trialDaysRemaining(): int
    {
        if (! $this->isOnTrial()) {
            return 0;
        }

        return (int) now()->diffInDays($this->trial_ends_at, false);
    }

    /**
     * Check if the tenant has completed onboarding.
     */
    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    /**
     * Get a setting value using dot notation.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->settings ?? [], $key, $default);
    }

    /**
     * Set a setting value using dot notation.
     */
    public function setSetting(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        Arr::set($settings, $key, $value);
        $this->settings = $settings;
        $this->save();
    }

    /**
     * Activate the tenant.
     */
    public function activate(): void
    {
        $this->status = TenantStatus::ACTIVE;
        $this->save();
    }

    /**
     * Suspend the tenant.
     */
    public function suspend(?string $reason = null): void
    {
        $this->status = TenantStatus::SUSPENDED;

        if ($reason !== null) {
            $metadata = $this->metadata ?? [];
            $metadata['suspension_reason'] = $reason;
            $metadata['suspended_at'] = now()->toIso8601String();
            $this->metadata = $metadata;
        }

        $this->save();
    }

    /**
     * Terminate the tenant.
     */
    public function terminate(): void
    {
        $this->status = TenantStatus::TERMINATED;

        $metadata = $this->metadata ?? [];
        $metadata['terminated_at'] = now()->toIso8601String();
        $this->metadata = $metadata;

        $this->save();
    }
}
