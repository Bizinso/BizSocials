<?php

declare(strict_types=1);

namespace App\Models\Inbox;

use App\Enums\Inbox\InboxAutomationAction;
use App\Enums\Inbox\InboxAutomationTrigger;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InboxAutomationRule Model
 *
 * Represents an automation rule that can be triggered by inbox events
 * to automatically perform actions like assigning, tagging, or replying.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string $name Rule name
 * @property bool $is_active Whether the rule is active
 * @property InboxAutomationTrigger $trigger_type Trigger type
 * @property array|null $trigger_conditions Trigger conditions
 * @property InboxAutomationAction $action_type Action type
 * @property array|null $action_params Action parameters
 * @property int $priority Rule priority (higher = evaluated first)
 * @property int $execution_count Number of times executed
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> active()
 */
final class InboxAutomationRule extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inbox_automation_rules';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'name',
        'is_active',
        'trigger_type',
        'trigger_conditions',
        'action_type',
        'action_params',
        'priority',
        'execution_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'trigger_type' => InboxAutomationTrigger::class,
            'action_type' => InboxAutomationAction::class,
            'trigger_conditions' => 'array',
            'action_params' => 'array',
            'priority' => 'integer',
            'execution_count' => 'integer',
        ];
    }

    /**
     * Get the workspace that this rule belongs to.
     *
     * @return BelongsTo<Workspace, InboxAutomationRule>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<InboxAutomationRule>  $query
     * @return Builder<InboxAutomationRule>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to get only active rules.
     *
     * @param  Builder<InboxAutomationRule>  $query
     * @return Builder<InboxAutomationRule>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
