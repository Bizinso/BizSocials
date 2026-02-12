<?php

declare(strict_types=1);

namespace App\Models\WhatsApp;

use App\Enums\WhatsApp\WhatsAppAccountStatus;
use App\Enums\WhatsApp\WhatsAppMessagingTier;
use App\Enums\WhatsApp\WhatsAppQualityRating;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $meta_business_account_id
 * @property string $waba_id
 * @property string $name
 * @property WhatsAppAccountStatus $status
 * @property WhatsAppQualityRating $quality_rating
 * @property WhatsAppMessagingTier $messaging_limit_tier
 * @property string $access_token_encrypted
 * @property string $webhook_verify_token
 * @property array|null $webhook_subscribed_fields
 * @property \Carbon\Carbon|null $compliance_accepted_at
 * @property string|null $compliance_accepted_by_user_id
 * @property bool $is_marketing_enabled
 * @property string|null $suspended_reason
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read Tenant $tenant
 * @property-read User|null $complianceAcceptedBy
 * @property-read Collection<WhatsAppPhoneNumber> $phoneNumbers
 *
 * @method static Builder<static> forTenant(string $tenantId)
 * @method static Builder<static> verified()
 * @method static Builder<static> active()
 */
final class WhatsAppBusinessAccount extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'whatsapp_business_accounts';

    protected $fillable = [
        'tenant_id',
        'meta_business_account_id',
        'waba_id',
        'name',
        'status',
        'quality_rating',
        'messaging_limit_tier',
        'access_token_encrypted',
        'webhook_verify_token',
        'webhook_subscribed_fields',
        'compliance_accepted_at',
        'compliance_accepted_by_user_id',
        'is_marketing_enabled',
        'suspended_reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => WhatsAppAccountStatus::class,
            'quality_rating' => WhatsAppQualityRating::class,
            'messaging_limit_tier' => WhatsAppMessagingTier::class,
            'webhook_subscribed_fields' => 'array',
            'compliance_accepted_at' => 'datetime',
            'is_marketing_enabled' => 'boolean',
            'metadata' => 'array',
            'access_token_encrypted' => 'encrypted',
        ];
    }

    /** @return BelongsTo<Tenant, WhatsAppBusinessAccount> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return HasMany<WhatsAppPhoneNumber> */
    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(WhatsAppPhoneNumber::class);
    }

    /** @return BelongsTo<User, WhatsAppBusinessAccount> */
    public function complianceAcceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'compliance_accepted_by_user_id');
    }

    /** @return HasManyThrough<WhatsAppConversation, WhatsAppPhoneNumber> */
    public function conversations(): HasManyThrough
    {
        return $this->hasManyThrough(
            WhatsAppConversation::class,
            WhatsAppPhoneNumber::class,
            'whatsapp_business_account_id',
            'whatsapp_phone_number_id',
        );
    }

    /** @return HasMany<AccountRiskAlert> */
    public function alerts(): HasMany
    {
        return $this->hasMany(AccountRiskAlert::class);
    }


    /** @param Builder<WhatsAppBusinessAccount> $query */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /** @param Builder<WhatsAppBusinessAccount> $query */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', WhatsAppAccountStatus::VERIFIED);
    }

    /** @param Builder<WhatsAppBusinessAccount> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            WhatsAppAccountStatus::PENDING_VERIFICATION,
            WhatsAppAccountStatus::VERIFIED,
        ]);
    }

    public function isVerified(): bool
    {
        return $this->status === WhatsAppAccountStatus::VERIFIED;
    }

    public function isSuspended(): bool
    {
        return $this->status === WhatsAppAccountStatus::SUSPENDED;
    }

    public function canSendMarketing(): bool
    {
        return $this->is_marketing_enabled && $this->isVerified();
    }

    public function getDecryptedToken(): string
    {
        return $this->access_token_encrypted;
    }
}
