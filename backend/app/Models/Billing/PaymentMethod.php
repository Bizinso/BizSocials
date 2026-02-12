<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Enums\Billing\PaymentMethodType;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PaymentMethod Model
 *
 * Represents a stored payment method for a tenant.
 *
 * @property string $id UUID primary key
 * @property string $tenant_id Tenant UUID
 * @property string|null $razorpay_token_id Razorpay token ID
 * @property PaymentMethodType $type Payment method type
 * @property bool $is_default Whether this is the default payment method
 * @property array $details Payment method details (masked)
 * @property \Carbon\Carbon|null $expires_at Expiry date for cards
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Tenant $tenant
 *
 * @method static Builder<static> forTenant(string $tenantId)
 * @method static Builder<static> default()
 * @method static Builder<static> ofType(PaymentMethodType $type)
 */
final class PaymentMethod extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payment_methods';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'razorpay_token_id',
        'type',
        'is_default',
        'details',
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
            'type' => PaymentMethodType::class,
            'is_default' => 'boolean',
            'details' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant that owns the payment method.
     *
     * @return BelongsTo<Tenant, PaymentMethod>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to filter by tenant.
     *
     * @param  Builder<PaymentMethod>  $query
     * @return Builder<PaymentMethod>
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to get only default payment methods.
     *
     * @param  Builder<PaymentMethod>  $query
     * @return Builder<PaymentMethod>
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to filter by payment method type.
     *
     * @param  Builder<PaymentMethod>  $query
     * @return Builder<PaymentMethod>
     */
    public function scopeOfType(Builder $query, PaymentMethodType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Check if the payment method is expired.
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if this is the default payment method.
     */
    public function isDefault(): bool
    {
        return $this->is_default;
    }

    /**
     * Set this payment method as the default.
     */
    public function setAsDefault(): void
    {
        // Remove default from other payment methods for this tenant
        self::where('tenant_id', $this->tenant_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->is_default = true;
        $this->save();
    }

    /**
     * Get a display name for the payment method.
     * E.g., "Visa ending in 4242"
     */
    public function getDisplayName(): string
    {
        $details = $this->details ?? [];

        return match ($this->type) {
            PaymentMethodType::CARD => sprintf(
                '%s ending in %s',
                $details['brand'] ?? 'Card',
                $details['last4'] ?? '****'
            ),
            PaymentMethodType::UPI => sprintf(
                'UPI - %s',
                $details['vpa'] ?? 'Unknown'
            ),
            PaymentMethodType::NETBANKING => sprintf(
                'Net Banking - %s',
                $details['bank'] ?? 'Unknown Bank'
            ),
            PaymentMethodType::WALLET => sprintf(
                'Wallet - %s',
                $details['provider'] ?? 'Unknown'
            ),
            PaymentMethodType::EMANDATE => sprintf(
                'e-Mandate - %s',
                $details['bank'] ?? 'Unknown Bank'
            ),
        };
    }

    /**
     * Get the last 4 digits (for cards).
     */
    public function getLast4(): ?string
    {
        if ($this->type !== PaymentMethodType::CARD) {
            return null;
        }

        return $this->details['last4'] ?? null;
    }

    /**
     * Get the expiry date formatted as MM/YY.
     */
    public function getExpiryDate(): ?string
    {
        if ($this->type !== PaymentMethodType::CARD) {
            return null;
        }

        $details = $this->details ?? [];

        if (! isset($details['exp_month']) || ! isset($details['exp_year'])) {
            return null;
        }

        return sprintf('%02d/%02d', $details['exp_month'], $details['exp_year'] % 100);
    }
}
