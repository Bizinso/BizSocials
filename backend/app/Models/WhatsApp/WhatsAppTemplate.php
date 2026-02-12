<?php

declare(strict_types=1);

namespace App\Models\WhatsApp;

use App\Enums\WhatsApp\WhatsAppTemplateCategory;
use App\Enums\WhatsApp\WhatsAppTemplateStatus;
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
 * @property string|null $meta_template_id
 * @property string $name
 * @property string $language
 * @property WhatsAppTemplateCategory $category
 * @property WhatsAppTemplateStatus $status
 * @property string|null $rejection_reason
 * @property string $header_type
 * @property string|null $header_content
 * @property string $body_text
 * @property string|null $footer_text
 * @property array|null $buttons
 * @property array|null $sample_values
 * @property int $usage_count
 * @property \Carbon\Carbon|null $last_used_at
 * @property \Carbon\Carbon|null $submitted_at
 * @property \Carbon\Carbon|null $approved_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 * @property-read WhatsAppPhoneNumber $phoneNumber
 * @property-read Collection<WhatsAppCampaign> $campaigns
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> approved()
 */
final class WhatsAppTemplate extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'workspace_id',
        'whatsapp_phone_number_id',
        'meta_template_id',
        'name',
        'language',
        'category',
        'status',
        'rejection_reason',
        'header_type',
        'header_content',
        'body_text',
        'footer_text',
        'buttons',
        'sample_values',
        'usage_count',
        'last_used_at',
        'submitted_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => WhatsAppTemplateCategory::class,
            'status' => WhatsAppTemplateStatus::class,
            'buttons' => 'array',
            'sample_values' => 'array',
            'last_used_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Workspace, WhatsAppTemplate> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<WhatsAppPhoneNumber, WhatsAppTemplate> */
    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(WhatsAppPhoneNumber::class, 'whatsapp_phone_number_id');
    }

    /** @return HasMany<WhatsAppCampaign> */
    public function campaigns(): HasMany
    {
        return $this->hasMany(WhatsAppCampaign::class, 'template_id');
    }

    /** @param Builder<WhatsAppTemplate> $query */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /** @param Builder<WhatsAppTemplate> $query */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', WhatsAppTemplateStatus::APPROVED);
    }

    public function isApproved(): bool
    {
        return $this->status === WhatsAppTemplateStatus::APPROVED;
    }

    public function canSubmit(): bool
    {
        return $this->status->canSubmit();
    }

    public function canSend(): bool
    {
        return $this->status->canSend();
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }
}
