<?php

declare(strict_types=1);

namespace App\Models\WhatsApp;

use App\Enums\WhatsApp\WhatsAppMessageStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $campaign_id
 * @property string $opt_in_id
 * @property string $phone_number
 * @property string|null $customer_name
 * @property array|null $template_params
 * @property WhatsAppMessageStatus $status
 * @property string|null $wamid
 * @property string|null $error_code
 * @property string|null $error_message
 * @property \Carbon\Carbon|null $sent_at
 * @property \Carbon\Carbon|null $delivered_at
 * @property \Carbon\Carbon|null $read_at
 *
 * @property-read WhatsAppCampaign $campaign
 * @property-read WhatsAppOptIn $optIn
 */
final class WhatsAppCampaignRecipient extends Model
{
    use HasUuids;

    protected $table = 'whatsapp_campaign_recipients';

    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'opt_in_id',
        'phone_number',
        'customer_name',
        'template_params',
        'status',
        'wamid',
        'error_code',
        'error_message',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => WhatsAppMessageStatus::class,
            'template_params' => 'array',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<WhatsAppCampaign, WhatsAppCampaignRecipient> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(WhatsAppCampaign::class, 'campaign_id');
    }

    /** @return BelongsTo<WhatsAppOptIn, WhatsAppCampaignRecipient> */
    public function optIn(): BelongsTo
    {
        return $this->belongsTo(WhatsAppOptIn::class, 'opt_in_id');
    }

    public function markSent(string $wamid): void
    {
        $this->update([
            'status' => WhatsAppMessageStatus::SENT,
            'wamid' => $wamid,
            'sent_at' => now(),
        ]);
    }

    public function markFailed(string $errorCode, string $errorMessage): void
    {
        $this->update([
            'status' => WhatsAppMessageStatus::FAILED,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
        ]);
    }
}
