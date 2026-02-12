<?php

declare(strict_types=1);

namespace App\Models\WhatsApp;

use App\Enums\WhatsApp\WhatsAppMessageDirection;
use App\Enums\WhatsApp\WhatsAppMessageStatus;
use App\Enums\WhatsApp\WhatsAppMessageType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $conversation_id
 * @property string|null $wamid
 * @property WhatsAppMessageDirection $direction
 * @property WhatsAppMessageType $type
 * @property string|null $content_text
 * @property array|null $content_payload
 * @property string|null $media_url
 * @property string|null $media_mime_type
 * @property int|null $media_file_size
 * @property string|null $template_id
 * @property string|null $sent_by_user_id
 * @property WhatsAppMessageStatus $status
 * @property \Carbon\Carbon|null $status_updated_at
 * @property string|null $error_code
 * @property string|null $error_message
 * @property \Carbon\Carbon $platform_timestamp
 * @property array|null $metadata
 *
 * @property-read WhatsAppConversation $conversation
 * @property-read User|null $sentByUser
 *
 * @method static Builder<static> inbound()
 * @method static Builder<static> outbound()
 */
final class WhatsAppMessage extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'conversation_id',
        'wamid',
        'direction',
        'type',
        'content_text',
        'content_payload',
        'media_url',
        'media_mime_type',
        'media_file_size',
        'template_id',
        'sent_by_user_id',
        'status',
        'status_updated_at',
        'error_code',
        'error_message',
        'platform_timestamp',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'direction' => WhatsAppMessageDirection::class,
            'type' => WhatsAppMessageType::class,
            'status' => WhatsAppMessageStatus::class,
            'content_payload' => 'array',
            'status_updated_at' => 'datetime',
            'platform_timestamp' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<WhatsAppConversation, WhatsAppMessage> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class);
    }

    /** @return BelongsTo<User, WhatsAppMessage> */
    public function sentByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    /** @param Builder<WhatsAppMessage> $query */
    public function scopeInbound(Builder $query): Builder
    {
        return $query->where('direction', WhatsAppMessageDirection::INBOUND);
    }

    /** @param Builder<WhatsAppMessage> $query */
    public function scopeOutbound(Builder $query): Builder
    {
        return $query->where('direction', WhatsAppMessageDirection::OUTBOUND);
    }

    public function isInbound(): bool
    {
        return $this->direction === WhatsAppMessageDirection::INBOUND;
    }

    public function isOutbound(): bool
    {
        return $this->direction === WhatsAppMessageDirection::OUTBOUND;
    }

    public function markDelivered(): void
    {
        $this->update([
            'status' => WhatsAppMessageStatus::DELIVERED,
            'status_updated_at' => now(),
        ]);
    }

    public function markRead(): void
    {
        $this->update([
            'status' => WhatsAppMessageStatus::READ,
            'status_updated_at' => now(),
        ]);
    }

    public function markFailed(string $errorCode, string $errorMessage): void
    {
        $this->update([
            'status' => WhatsAppMessageStatus::FAILED,
            'status_updated_at' => now(),
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
        ]);
    }
}
