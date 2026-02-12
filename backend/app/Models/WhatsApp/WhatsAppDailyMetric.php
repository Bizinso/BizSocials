<?php

declare(strict_types=1);

namespace App\Models\WhatsApp;

use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $workspace_id
 * @property string $whatsapp_phone_number_id
 * @property \Carbon\Carbon $date
 * @property int $conversations_started
 * @property int $conversations_resolved
 * @property int $messages_sent
 * @property int $messages_delivered
 * @property int $messages_read
 * @property int $messages_failed
 * @property int $templates_sent
 * @property int $campaigns_sent
 * @property int|null $avg_first_response_seconds
 * @property int|null $avg_resolution_seconds
 * @property int $block_count
 *
 * @property-read Workspace $workspace
 * @property-read WhatsAppPhoneNumber $phoneNumber
 */
final class WhatsAppDailyMetric extends Model
{
    use HasUuids;

    protected $table = 'whatsapp_daily_metrics';

    protected $fillable = [
        'workspace_id', 'whatsapp_phone_number_id', 'date',
        'conversations_started', 'conversations_resolved',
        'messages_sent', 'messages_delivered', 'messages_read', 'messages_failed',
        'templates_sent', 'campaigns_sent',
        'avg_first_response_seconds', 'avg_resolution_seconds', 'block_count',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    /** @return BelongsTo<Workspace, WhatsAppDailyMetric> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<WhatsAppPhoneNumber, WhatsAppDailyMetric> */
    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(WhatsAppPhoneNumber::class, 'whatsapp_phone_number_id');
    }

    /** @param Builder<WhatsAppDailyMetric> $query */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /** @param Builder<WhatsAppDailyMetric> $query */
    public function scopeForDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('date', [$from, $to]);
    }
}
