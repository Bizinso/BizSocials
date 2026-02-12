<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ApprovalStepDecision Model
 *
 * Represents a decision made by an approver at a specific step in a workflow.
 *
 * @property string $id UUID primary key
 * @property string $post_id Post UUID
 * @property string $workflow_id Workflow UUID
 * @property int $step_order Step order in workflow
 * @property string $approver_user_id Approver user UUID
 * @property string $decision Decision (approved/rejected)
 * @property string|null $comment Optional comment
 * @property \Carbon\Carbon $decided_at When the decision was made
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Post $post
 * @property-read ApprovalWorkflow $workflow
 * @property-read User $approver
 */
final class ApprovalStepDecision extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'approval_step_decisions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'workflow_id',
        'step_order',
        'approver_user_id',
        'decision',
        'comment',
        'decided_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    /**
     * Get the post this decision belongs to.
     *
     * @return BelongsTo<Post, ApprovalStepDecision>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the workflow this decision belongs to.
     *
     * @return BelongsTo<ApprovalWorkflow, ApprovalStepDecision>
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id');
    }

    /**
     * Get the user who made this decision.
     *
     * @return BelongsTo<User, ApprovalStepDecision>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}
