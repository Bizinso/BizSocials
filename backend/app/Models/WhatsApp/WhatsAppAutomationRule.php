<?php

declare(strict_types=1);

namespace App\Models\WhatsApp;

use App\Enums\WhatsApp\WhatsAppAutomationAction;
use App\Enums\WhatsApp\WhatsAppAutomationTrigger;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $workspace_id
 * @property string $name
 * @property bool $is_active
 * @property WhatsAppAutomationTrigger $trigger_type
 * @property array|null $trigger_conditions
 * @property WhatsAppAutomationAction $action_type
 * @property array|null $action_params
 * @property int $priority
 * @property int $execution_count
 *
 * @property-read Workspace $workspace
 */
final class WhatsAppAutomationRule extends Model
{
    use HasUuids;

    protected $table = 'whatsapp_automation_rules';

    protected $fillable = [
        'workspace_id', 'name', 'is_active', 'trigger_type', 'trigger_conditions',
        'action_type', 'action_params', 'priority', 'execution_count',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'trigger_type' => WhatsAppAutomationTrigger::class,
            'trigger_conditions' => 'array',
            'action_type' => WhatsAppAutomationAction::class,
            'action_params' => 'array',
        ];
    }

    /** @return BelongsTo<Workspace, WhatsAppAutomationRule> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @param Builder<WhatsAppAutomationRule> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderByDesc('priority');
    }

    /** @param Builder<WhatsAppAutomationRule> $query */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function incrementExecution(): void
    {
        $this->increment('execution_count');
    }
}
