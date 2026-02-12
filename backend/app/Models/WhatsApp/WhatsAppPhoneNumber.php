<?php

declare(strict_types=1);

namespace App\Models\WhatsApp;

use App\Enums\WhatsApp\WhatsAppPhoneStatus;
use App\Enums\WhatsApp\WhatsAppQualityRating;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $whatsapp_business_account_id
 * @property string $phone_number_id
 * @property string $phone_number
 * @property string $display_name
 * @property string|null $verified_name
 * @property WhatsAppQualityRating $quality_rating
 * @property WhatsAppPhoneStatus $status
 * @property bool $is_primary
 * @property string|null $category
 * @property string|null $description
 * @property string|null $address
 * @property string|null $website
 * @property string|null $support_email
 * @property string|null $profile_picture_url
 * @property int $daily_send_count
 * @property int $daily_send_limit
 * @property array|null $metadata
 *
 * @property-read WhatsAppBusinessAccount $businessAccount
 * @property-read Collection<WhatsAppConversation> $conversations
 *
 * @method static Builder<static> active()
 * @method static Builder<static> primary()
 */
final class WhatsAppPhoneNumber extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'whatsapp_phone_numbers';

    protected $fillable = [
        'whatsapp_business_account_id',
        'phone_number_id',
        'phone_number',
        'display_name',
        'verified_name',
        'quality_rating',
        'status',
        'is_primary',
        'category',
        'description',
        'address',
        'website',
        'support_email',
        'profile_picture_url',
        'daily_send_count',
        'daily_send_limit',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quality_rating' => WhatsAppQualityRating::class,
            'status' => WhatsAppPhoneStatus::class,
            'is_primary' => 'boolean',
            'daily_send_count' => 'integer',
            'daily_send_limit' => 'integer',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<WhatsAppBusinessAccount, WhatsAppPhoneNumber> */
    public function businessAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsAppBusinessAccount::class, 'whatsapp_business_account_id');
    }

    /** @return HasMany<WhatsAppConversation> */
    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsAppConversation::class, 'whatsapp_phone_number_id');
    }

    /** @param Builder<WhatsAppPhoneNumber> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', WhatsAppPhoneStatus::ACTIVE);
    }

    /** @param Builder<WhatsAppPhoneNumber> $query */
    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    public function isActive(): bool
    {
        return $this->status === WhatsAppPhoneStatus::ACTIVE;
    }

    public function canSend(): bool
    {
        return $this->isActive() && !$this->hasReachedDailyLimit();
    }

    public function incrementDailySendCount(): void
    {
        $this->increment('daily_send_count');
    }

    public function resetDailySendCount(): void
    {
        $this->update(['daily_send_count' => 0]);
    }

    public function hasReachedDailyLimit(): bool
    {
        return $this->daily_send_count >= $this->daily_send_limit;
    }
}
