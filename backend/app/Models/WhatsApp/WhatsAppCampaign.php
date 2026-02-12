<?php

declare(strict_types=1);

namespace App\Models\WhatsApp;

use App\Enums\WhatsApp\WhatsAppCampaignStatus;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $workspace_id
 * @property string $whatsapp_phone_number_id
 * @property string $template_id
 * @property string $name
 * @property WhatsAppCampaignStatus $status
 * @property \Carbon\Carbon|null $scheduled_at
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property int $total_recipients
 * @property int $sent_count
 * @property int $delivered_count
 * @property int $read_count
 * @property int $failed_count
 * @property array|null $template_params_mapping
 * @property array|null $audience_filter
 * @property string $created_by_user_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 * @property-read WhatsAppPhoneNumber $phoneNumber
 * @property-read WhatsAppTemplate $template
 * @property-read User $createdBy
 * @property-read Collection<WhatsAppCampaignRecipient> $recipients
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 */
final class WhatsAppCampaign extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'whatsapp_campaigns';

    protected $fillable = [
        'workspace_id',
        'whatsapp_phone_number_id',
        'template_id',
        'name',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'total_recipients',
        'sent_count',
        'delivered_count',
        'read_count',
        'failed_count',
        'template_params_mapping',
        'audience_filter',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => WhatsAppCampaignStatus::class,
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'template_params_mapping' => 'array',
            'audience_filter' => 'array',
        ];
    }

    /** @return BelongsTo<Workspace, WhatsAppCampaign> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<WhatsAppPhoneNumber, WhatsAppCampaign> */
    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(WhatsAppPhoneNumber::class, 'whatsapp_phone_number_id');
    }

    /** @return BelongsTo<WhatsAppTemplate, WhatsAppCampaign> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsAppTemplate::class, 'template_id');
    }

    /** @return BelongsTo<User, WhatsAppCampaign> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return HasMany<WhatsAppCampaignRecipient> */
    public function recipients(): HasMany
    {
        return $this->hasMany(WhatsAppCampaignRecipient::class, 'campaign_id');
    }

    /** @param Builder<WhatsAppCampaign> $query */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function canEdit(): bool
    {
        return $this->status->canEdit();
    }

    public function canCancel(): bool
    {
        return $this->status->canCancel();
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    public function getDeliveryRate(): float
    {
        if ($this->sent_count === 0) {
            return 0.0;
        }

        return round(($this->delivered_count / $this->sent_count) * 100, 1);
    }

    public function getReadRate(): float
    {
        if ($this->delivered_count === 0) {
            return 0.0;
        }

        return round(($this->read_count / $this->delivered_count) * 100, 1);
    }
}
