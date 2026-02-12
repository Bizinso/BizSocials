<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Enums\Billing\BillingCycle;
use App\Enums\Billing\Currency;
use App\Enums\Billing\SubscriptionStatus;
use App\Models\Platform\PlanDefinition;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Subscription Model
 *
 * Represents a tenant's subscription to a plan.
 *
 * @property string $id UUID primary key
 * @property string $tenant_id Tenant UUID
 * @property string $plan_id Plan UUID
 * @property SubscriptionStatus $status Subscription status
 * @property BillingCycle $billing_cycle Billing cycle (monthly/yearly)
 * @property Currency $currency Currency code
 * @property float $amount Amount per billing cycle
 * @property string|null $razorpay_subscription_id Razorpay subscription ID
 * @property string|null $razorpay_customer_id Razorpay customer ID
 * @property \Carbon\Carbon|null $current_period_start Current period start
 * @property \Carbon\Carbon|null $current_period_end Current period end
 * @property \Carbon\Carbon|null $trial_start Trial period start
 * @property \Carbon\Carbon|null $trial_end Trial period end
 * @property \Carbon\Carbon|null $cancelled_at Cancellation timestamp
 * @property bool $cancel_at_period_end Whether to cancel at period end
 * @property \Carbon\Carbon|null $ended_at End timestamp
 * @property array|null $metadata Additional metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Tenant $tenant
 * @property-read PlanDefinition $plan
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Invoice> $invoices
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Payment> $payments
 *
 * @method static Builder<static> active()
 * @method static Builder<static> forTenant(string $tenantId)
 */
final class Subscription extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'subscriptions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'plan_id',
        'status',
        'billing_cycle',
        'currency',
        'amount',
        'razorpay_subscription_id',
        'razorpay_customer_id',
        'current_period_start',
        'current_period_end',
        'trial_start',
        'trial_end',
        'cancelled_at',
        'cancel_at_period_end',
        'ended_at',
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
            'status' => SubscriptionStatus::class,
            'billing_cycle' => BillingCycle::class,
            'currency' => Currency::class,
            'amount' => 'decimal:2',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'trial_start' => 'datetime',
            'trial_end' => 'datetime',
            'cancelled_at' => 'datetime',
            'cancel_at_period_end' => 'boolean',
            'ended_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the tenant that owns the subscription.
     *
     * @return BelongsTo<Tenant, Subscription>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the plan for this subscription.
     *
     * @return BelongsTo<PlanDefinition, Subscription>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanDefinition::class, 'plan_id');
    }

    /**
     * Get invoices for this subscription.
     *
     * @return HasMany<Invoice>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get payments for this subscription.
     *
     * @return HasMany<Payment>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scope to get only active subscriptions.
     *
     * @param  Builder<Subscription>  $query
     * @return Builder<Subscription>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::ACTIVE);
    }

    /**
     * Scope to filter by tenant.
     *
     * @param  Builder<Subscription>  $query
     * @return Builder<Subscription>
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Check if the subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::ACTIVE;
    }

    /**
     * Check if the subscription has platform access.
     */
    public function hasAccess(): bool
    {
        return $this->status->hasAccess();
    }

    /**
     * Check if the subscription is on trial.
     */
    public function isOnTrial(): bool
    {
        return $this->trial_end !== null && $this->trial_end->isFuture();
    }

    /**
     * Get the number of trial days remaining.
     */
    public function trialDaysRemaining(): int
    {
        if (! $this->isOnTrial()) {
            return 0;
        }

        return (int) max(0, now()->diffInDays($this->trial_end, false));
    }

    /**
     * Get the number of days until renewal.
     */
    public function daysUntilRenewal(): int
    {
        if ($this->current_period_end === null) {
            return 0;
        }

        return (int) max(0, now()->diffInDays($this->current_period_end, false));
    }

    /**
     * Cancel the subscription.
     */
    public function cancel(bool $atPeriodEnd = true): void
    {
        $this->cancelled_at = now();
        $this->cancel_at_period_end = $atPeriodEnd;

        if (! $atPeriodEnd) {
            $this->status = SubscriptionStatus::CANCELLED;
            $this->ended_at = now();
        }

        $this->save();
    }

    /**
     * Reactivate a cancelled subscription.
     */
    public function reactivate(): void
    {
        if ($this->status === SubscriptionStatus::CANCELLED && $this->ended_at === null) {
            $this->cancelled_at = null;
            $this->cancel_at_period_end = false;
            $this->status = SubscriptionStatus::ACTIVE;
            $this->save();
        }
    }

    /**
     * Change the subscription plan.
     */
    public function changePlan(PlanDefinition $newPlan): void
    {
        $this->plan_id = $newPlan->id;
        $this->save();
    }

    /**
     * Mark the subscription as active.
     */
    public function markAsActive(): void
    {
        $this->status = SubscriptionStatus::ACTIVE;
        $this->save();
    }

    /**
     * Mark the subscription as pending (payment due).
     */
    public function markAsPending(): void
    {
        $this->status = SubscriptionStatus::PENDING;
        $this->save();
    }

    /**
     * Mark the subscription as halted (payment failed multiple times).
     */
    public function markAsHalted(): void
    {
        $this->status = SubscriptionStatus::HALTED;
        $this->save();
    }

    /**
     * Mark the subscription as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->status = SubscriptionStatus::CANCELLED;
        $this->cancelled_at = now();
        $this->ended_at = now();
        $this->save();
    }
}
