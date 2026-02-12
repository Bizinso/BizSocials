<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Enums\Platform\PlanCode;
use App\Models\Billing\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PlanDefinition Model
 *
 * Represents subscription plan definitions with pricing,
 * features, and metadata for each plan tier.
 *
 * @property string $id UUID primary key
 * @property PlanCode $code Unique plan code
 * @property string $name Human-readable plan name
 * @property string|null $description Plan description
 * @property float $price_inr_monthly Monthly price in INR
 * @property float $price_inr_yearly Yearly price in INR
 * @property float $price_usd_monthly Monthly price in USD
 * @property float $price_usd_yearly Yearly price in USD
 * @property int $trial_days Trial period in days
 * @property bool $is_active Whether the plan is active
 * @property bool $is_public Whether the plan is publicly visible
 * @property int $sort_order Display order
 * @property array<string> $features Feature list for display
 * @property array<string, mixed>|null $metadata Additional metadata
 * @property string|null $razorpay_plan_id_inr Razorpay plan ID for INR
 * @property string|null $razorpay_plan_id_usd Razorpay plan ID for USD
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PlanLimit> $limits
 * @property-read float $monthly_price Current monthly price (based on context)
 * @property-read float $yearly_price Current yearly price (based on context)
 * @property-read float $yearly_discount_percent Yearly discount percentage
 *
 * @method static Builder<static> active()
 * @method static Builder<static> public()
 */
final class PlanDefinition extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'plan_definitions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'price_inr_monthly',
        'price_inr_yearly',
        'price_usd_monthly',
        'price_usd_yearly',
        'trial_days',
        'is_active',
        'is_public',
        'sort_order',
        'features',
        'metadata',
        'razorpay_plan_id_inr',
        'razorpay_plan_id_usd',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'code' => PlanCode::class,
            'price_inr_monthly' => 'decimal:2',
            'price_inr_yearly' => 'decimal:2',
            'price_usd_monthly' => 'decimal:2',
            'price_usd_yearly' => 'decimal:2',
            'trial_days' => 'integer',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'sort_order' => 'integer',
            'features' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the plan limits.
     *
     * @return HasMany<PlanLimit>
     */
    public function limits(): HasMany
    {
        return $this->hasMany(PlanLimit::class, 'plan_id');
    }

    /**
     * Get all subscriptions for this plan.
     *
     * @return HasMany<Subscription>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    /**
     * Scope to get only active plans.
     *
     * @param  Builder<PlanDefinition>  $query
     * @return Builder<PlanDefinition>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only public plans.
     *
     * @param  Builder<PlanDefinition>  $query
     * @return Builder<PlanDefinition>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Get a specific limit value for this plan.
     *
     * @param  string  $limitKey  The limit key to look up
     * @return int The limit value, or -1 if unlimited or not found
     */
    public function getLimit(string $limitKey): int
    {
        $limit = $this->limits()->where('limit_key', $limitKey)->first();

        return $limit?->limit_value ?? -1;
    }

    /**
     * Check if a specific limit is unlimited.
     */
    public function isLimitUnlimited(string $limitKey): bool
    {
        return $this->getLimit($limitKey) === -1;
    }

    /**
     * Get the monthly price based on currency context.
     *
     * Uses INR by default, can be extended to use request context.
     *
     * @return Attribute<float, never>
     */
    protected function monthlyPrice(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                // Default to INR, can be extended to use app context
                $currency = config('app.default_currency', 'INR');

                return $currency === 'USD'
                    ? (float) $this->price_usd_monthly
                    : (float) $this->price_inr_monthly;
            }
        );
    }

    /**
     * Get the yearly price based on currency context.
     *
     * @return Attribute<float, never>
     */
    protected function yearlyPrice(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                $currency = config('app.default_currency', 'INR');

                return $currency === 'USD'
                    ? (float) $this->price_usd_yearly
                    : (float) $this->price_inr_yearly;
            }
        );
    }

    /**
     * Calculate the yearly discount percentage.
     *
     * Compares yearly price to 12x monthly price.
     *
     * @return Attribute<float, never>
     */
    protected function yearlyDiscountPercent(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                $monthly = $this->monthly_price;
                $yearly = $this->yearly_price;

                if ($monthly <= 0) {
                    return 0.0;
                }

                $yearlyFullPrice = $monthly * 12;
                $discount = (($yearlyFullPrice - $yearly) / $yearlyFullPrice) * 100;

                return round($discount, 1);
            }
        );
    }

    /**
     * Get a plan by its code.
     */
    public static function getByCode(PlanCode $code): ?self
    {
        return self::where('code', $code->value)->first();
    }

    /**
     * Get all active public plans ordered by sort_order.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, PlanDefinition>
     */
    public static function getPublicPlans(): \Illuminate\Database\Eloquent\Collection
    {
        return self::active()
            ->public()
            ->orderBy('sort_order')
            ->get();
    }
}
