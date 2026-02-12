<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Enums\Billing\Currency;
use App\Enums\Billing\PaymentStatus;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payment Model
 *
 * Represents a payment transaction.
 *
 * @property string $id UUID primary key
 * @property string $tenant_id Tenant UUID
 * @property string|null $subscription_id Subscription UUID
 * @property string|null $invoice_id Invoice UUID
 * @property string|null $razorpay_payment_id Razorpay payment ID
 * @property string|null $razorpay_order_id Razorpay order ID
 * @property PaymentStatus $status Payment status
 * @property float $amount Payment amount
 * @property Currency $currency Currency code
 * @property string|null $method Payment method (card, upi, etc.)
 * @property array|null $method_details Payment method details
 * @property float|null $fee Razorpay fee
 * @property float|null $tax_on_fee GST on fee
 * @property string|null $error_code Error code if failed
 * @property string|null $error_description Error description if failed
 * @property \Carbon\Carbon|null $captured_at Capture timestamp
 * @property \Carbon\Carbon|null $refunded_at Refund timestamp
 * @property float|null $refund_amount Refund amount
 * @property array|null $metadata Additional metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Tenant $tenant
 * @property-read Subscription|null $subscription
 * @property-read Invoice|null $invoice
 *
 * @method static Builder<static> forTenant(string $tenantId)
 * @method static Builder<static> successful()
 * @method static Builder<static> failed()
 */
final class Payment extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'invoice_id',
        'razorpay_payment_id',
        'razorpay_order_id',
        'status',
        'amount',
        'currency',
        'method',
        'method_details',
        'fee',
        'tax_on_fee',
        'error_code',
        'error_description',
        'captured_at',
        'refunded_at',
        'refund_amount',
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
            'status' => PaymentStatus::class,
            'currency' => Currency::class,
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'tax_on_fee' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'method_details' => 'array',
            'metadata' => 'array',
            'captured_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant that owns the payment.
     *
     * @return BelongsTo<Tenant, Payment>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the subscription for this payment.
     *
     * @return BelongsTo<Subscription, Payment>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the invoice for this payment.
     *
     * @return BelongsTo<Invoice, Payment>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Scope to filter by tenant.
     *
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to get only successful (captured) payments.
     *
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::CAPTURED);
    }

    /**
     * Scope to get only failed payments.
     *
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::FAILED);
    }

    /**
     * Check if the payment was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::CAPTURED;
    }

    /**
     * Check if the payment failed.
     */
    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    /**
     * Check if the payment was refunded.
     */
    public function isRefunded(): bool
    {
        return $this->status === PaymentStatus::REFUNDED;
    }

    /**
     * Mark the payment as captured.
     */
    public function markAsCaptured(): void
    {
        $this->status = PaymentStatus::CAPTURED;
        $this->captured_at = now();
        $this->save();
    }

    /**
     * Mark the payment as failed.
     */
    public function markAsFailed(string $errorCode, string $errorDescription): void
    {
        $this->status = PaymentStatus::FAILED;
        $this->error_code = $errorCode;
        $this->error_description = $errorDescription;
        $this->save();
    }

    /**
     * Mark the payment as refunded.
     */
    public function markAsRefunded(float $amount): void
    {
        $this->status = PaymentStatus::REFUNDED;
        $this->refunded_at = now();
        $this->refund_amount = $amount;
        $this->save();
    }

    /**
     * Get the net amount (amount - fee - tax_on_fee).
     */
    public function getNetAmount(): float
    {
        $amount = (float) $this->amount;
        $fee = (float) ($this->fee ?? 0);
        $taxOnFee = (float) ($this->tax_on_fee ?? 0);

        return $amount - $fee - $taxOnFee;
    }
}
