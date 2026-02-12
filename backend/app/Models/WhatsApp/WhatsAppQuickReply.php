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
 * @property string $title
 * @property string $content
 * @property string|null $shortcut
 * @property string|null $category
 * @property int $usage_count
 *
 * @property-read Workspace $workspace
 */
final class WhatsAppQuickReply extends Model
{
    use HasUuids;

    protected $table = 'whatsapp_quick_replies';

    protected $fillable = [
        'workspace_id', 'title', 'content', 'shortcut', 'category', 'usage_count',
    ];

    /** @return BelongsTo<Workspace, WhatsAppQuickReply> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @param Builder<WhatsAppQuickReply> $query */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}
