<?php

declare(strict_types=1);

namespace App\Models\Support;

use App\Enums\Support\SupportChannel;
use App\Enums\Support\SupportCommentType;
use App\Enums\Support\SupportTicketPriority;
use App\Enums\Support\SupportTicketStatus;
use App\Enums\Support\SupportTicketType;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * SupportTicket Model
 *
 * Represents a customer support ticket.
 *
 * @property string $id UUID primary key
 * @property string $ticket_number Unique ticket number (TKT-XXXXXX)
 * @property string|null $tenant_id Tenant UUID
 * @property string|null $user_id User UUID
 * @property string $requester_email Requester's email
 * @property string $requester_name Requester's name
 * @property string|null $category_id Category UUID
 * @property string $subject Ticket subject
 * @property string $description Ticket description
 * @property SupportTicketType $ticket_type Type of ticket
 * @property SupportTicketPriority $priority Ticket priority
 * @property SupportTicketStatus $status Ticket status
 * @property SupportChannel $channel Submission channel
 * @property string|null $assigned_to Assigned admin UUID
 * @property string|null $assigned_team_id Assigned team UUID
 * @property \Carbon\Carbon|null $first_response_at First response timestamp
 * @property \Carbon\Carbon|null $resolved_at Resolved timestamp
 * @property \Carbon\Carbon|null $closed_at Closed timestamp
 * @property \Carbon\Carbon|null $last_activity_at Last activity timestamp
 * @property \Carbon\Carbon|null $sla_due_at SLA due timestamp
 * @property bool $is_sla_breached Whether SLA is breached
 * @property int $comment_count Number of comments
 * @property int $attachment_count Number of attachments
 * @property array|null $custom_fields Custom fields JSON
 * @property string|null $browser_info Browser info
 * @property string|null $page_url Page URL where ticket was submitted
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read Tenant|null $tenant
 * @property-read User|null $user
 * @property-read SupportCategory|null $category
 * @property-read SuperAdminUser|null $assignee
 * @property-read Collection<int, SupportTicketComment> $comments
 * @property-read Collection<int, SupportTicketAttachment> $attachments
 * @property-read Collection<int, SupportTicketTag> $tags
 * @property-read Collection<int, SupportTicketWatcher> $watchers
 *
 * @method static Builder<static> newTickets()
 * @method static Builder<static> open()
 * @method static Builder<static> pending()
 * @method static Builder<static> closed()
 * @method static Builder<static> byStatus(SupportTicketStatus $status)
 * @method static Builder<static> byPriority(SupportTicketPriority $priority)
 * @method static Builder<static> byType(SupportTicketType $type)
 * @method static Builder<static> forTenant(string $tenantId)
 * @method static Builder<static> forUser(string $userId)
 * @method static Builder<static> assignedTo(string $adminId)
 * @method static Builder<static> unassigned()
 * @method static Builder<static> overdue()
 * @method static Builder<static> search(string $query)
 */
final class SupportTicket extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'support_tickets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_number',
        'tenant_id',
        'user_id',
        'requester_email',
        'requester_name',
        'category_id',
        'subject',
        'description',
        'ticket_type',
        'priority',
        'status',
        'channel',
        'assigned_to',
        'assigned_team_id',
        'first_response_at',
        'resolved_at',
        'closed_at',
        'last_activity_at',
        'sla_due_at',
        'is_sla_breached',
        'comment_count',
        'attachment_count',
        'custom_fields',
        'browser_info',
        'page_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ticket_type' => SupportTicketType::class,
            'priority' => SupportTicketPriority::class,
            'status' => SupportTicketStatus::class,
            'channel' => SupportChannel::class,
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'sla_due_at' => 'datetime',
            'is_sla_breached' => 'boolean',
            'comment_count' => 'integer',
            'attachment_count' => 'integer',
            'custom_fields' => 'array',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (SupportTicket $ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = self::generateTicketNumber();
            }
            if (empty($ticket->sla_due_at) && $ticket->priority) {
                $ticket->sla_due_at = $ticket->calculateSlaDue();
            }
        });
    }

    /**
     * Generate a unique ticket number.
     */
    public static function generateTicketNumber(): string
    {
        do {
            $number = 'TKT-' . strtoupper(Str::random(6));
        } while (self::where('ticket_number', $number)->exists());

        return $number;
    }

    /**
     * Get the tenant.
     *
     * @return BelongsTo<Tenant, SupportTicket>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the user who submitted the ticket.
     *
     * @return BelongsTo<User, SupportTicket>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the category.
     *
     * @return BelongsTo<SupportCategory, SupportTicket>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(SupportCategory::class, 'category_id');
    }

    /**
     * Get the assigned admin.
     *
     * @return BelongsTo<SuperAdminUser, SupportTicket>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(SuperAdminUser::class, 'assigned_to');
    }

    /**
     * Get the comments.
     *
     * @return HasMany<SupportTicketComment>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(SupportTicketComment::class, 'ticket_id');
    }

    /**
     * Get the attachments.
     *
     * @return HasMany<SupportTicketAttachment>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class, 'ticket_id');
    }

    /**
     * Get the tags.
     *
     * @return BelongsToMany<SupportTicketTag>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            SupportTicketTag::class,
            'support_ticket_tag_assignments',
            'ticket_id',
            'tag_id'
        )->withTimestamps();
    }

    /**
     * Get the watchers.
     *
     * @return HasMany<SupportTicketWatcher>
     */
    public function watchers(): HasMany
    {
        return $this->hasMany(SupportTicketWatcher::class, 'ticket_id');
    }

    /**
     * Scope to get new tickets.
     *
     * @param  Builder<SupportTicket>  $query
     * @return Builder<SupportTicket>
     */
    public function scopeNewTickets(Builder $query): Builder
    {
        return $query->where('status', SupportTicketStatus::NEW);
    }

    /**
     * Scope to get open tickets.
     *
     * @param  Builder<SupportTicket>  $query
     * @return Builder<SupportTicket>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            SupportTicketStatus::NEW,
            SupportTicketStatus::OPEN,
            SupportTicketStatus::IN_PROGRESS,
            SupportTicketStatus::REOPENED,
        ]);
    }

    /**
     * Scope to get pending tickets.
     *
     * @param  Builder<SupportTicket>  $query
     * @return Builder<SupportTicket>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [
            SupportTicketStatus::WAITING_CUSTOMER,
            SupportTicketStatus::WAITING_INTERNAL,
        ]);
    }

    /**
     * Scope to get closed tickets.
     *
     * @param  Builder<SupportTicket>  $query
     * @return Builder<SupportTicket>
     */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->whereIn('status', [
            SupportTicketStatus::RESOLVED,
            SupportTicketStatus::CLOSED,
        ]);
    }

    /**
     * Scope to filter by status.
     *
     * @param  Builder<SupportTicket>  $query
     * @return Builder<SupportTicket>
     */
    public function scopeByStatus(Builder $query, SupportTicketStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by priority.
     *
     * @param  Builder<SupportTicket>  $query
     * @return Builder<SupportTicket>
     */
    public function scopeByPriority(Builder $query, SupportTicketPriority $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to filter by type.
     *
     * @param  Builder<SupportTicket>  $query
     * @return Builder<SupportTicket>
     */
    public function scopeByType(Builder $query, SupportTicketType $type): Builder
    {
        return $query->where('ticket_type', $type);
    }

    /**
     * Scope to filter by tenant.
     *
     * @param  Builder<SupportTicket>  $query
     * @return Builder<SupportTicket>
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by user.
     *
     * @param  Builder<SupportTicket>  $query
     * @return Builder<SupportTicket>
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by assigned admin.
     *
     * @param  Builder<SupportTicket>  $query
     * @return Builder<SupportTicket>
     */
    public function scopeAssignedTo(Builder $query, string $adminId): Builder
    {
        return $query->where('assigned_to', $adminId);
    }

    /**
     * Scope to get unassigned tickets.
     *
     * @param  Builder<SupportTicket>  $query
     * @return Builder<SupportTicket>
     */
    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('assigned_to');
    }

    /**
     * Scope to get overdue tickets.
     *
     * @param  Builder<SupportTicket>  $query
     * @return Builder<SupportTicket>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('sla_due_at', '<', now())
            ->whereNotIn('status', [SupportTicketStatus::RESOLVED, SupportTicketStatus::CLOSED]);
    }

    /**
     * Scope to search tickets.
     *
     * @param  Builder<SupportTicket>  $query
     * @return Builder<SupportTicket>
     */
    public function scopeSearch(Builder $query, string $searchQuery): Builder
    {
        return $query->where(function (Builder $q) use ($searchQuery) {
            $q->where('ticket_number', 'like', "%{$searchQuery}%")
                ->orWhere('subject', 'like', "%{$searchQuery}%")
                ->orWhere('description', 'like', "%{$searchQuery}%")
                ->orWhere('requester_email', 'like', "%{$searchQuery}%");
        });
    }

    /**
     * Check if the ticket is new.
     */
    public function isNew(): bool
    {
        return $this->status === SupportTicketStatus::NEW;
    }

    /**
     * Check if the ticket is in an open state.
     */
    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    /**
     * Check if the ticket is in a pending state.
     */
    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    /**
     * Check if the ticket is closed.
     */
    public function isClosed(): bool
    {
        return $this->status->isClosed();
    }

    /**
     * Check if the ticket is overdue.
     */
    public function isOverdue(): bool
    {
        if ($this->isClosed() || !$this->sla_due_at) {
            return false;
        }

        return $this->sla_due_at->isPast();
    }

    /**
     * Assign the ticket to an admin.
     */
    public function assign(SuperAdminUser $admin): void
    {
        $this->assigned_to = $admin->id;
        if ($this->status === SupportTicketStatus::NEW) {
            $this->status = SupportTicketStatus::OPEN;
        }
        $this->save();
    }

    /**
     * Unassign the ticket.
     */
    public function unassign(): void
    {
        $this->assigned_to = null;
        $this->save();
    }

    /**
     * Change the ticket status.
     */
    public function changeStatus(SupportTicketStatus $newStatus): bool
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            return false;
        }

        $this->status = $newStatus;

        if ($newStatus === SupportTicketStatus::RESOLVED) {
            $this->resolved_at = now();
        }

        if ($newStatus === SupportTicketStatus::CLOSED) {
            $this->closed_at = now();
        }

        $this->last_activity_at = now();
        $this->save();

        return true;
    }

    /**
     * Resolve the ticket.
     */
    public function resolve(): bool
    {
        return $this->changeStatus(SupportTicketStatus::RESOLVED);
    }

    /**
     * Close the ticket.
     */
    public function close(): bool
    {
        return $this->changeStatus(SupportTicketStatus::CLOSED);
    }

    /**
     * Reopen the ticket.
     */
    public function reopen(): bool
    {
        return $this->changeStatus(SupportTicketStatus::REOPENED);
    }

    /**
     * Add a comment to the ticket.
     */
    public function addComment(
        string $content,
        SupportCommentType $type = SupportCommentType::REPLY,
        ?string $userId = null,
        ?string $adminId = null,
        bool $isInternal = false
    ): SupportTicketComment {
        $comment = $this->comments()->create([
            'content' => $content,
            'comment_type' => $type,
            'user_id' => $userId,
            'admin_id' => $adminId,
            'is_internal' => $isInternal,
        ]);

        $this->increment('comment_count');
        $this->last_activity_at = now();

        // Record first response time if this is the first staff response
        if ($adminId && !$this->first_response_at && $type === SupportCommentType::REPLY) {
            $this->first_response_at = now();
        }

        $this->save();

        return $comment;
    }

    /**
     * Calculate the SLA due date based on priority.
     */
    public function calculateSlaDue(): \Carbon\Carbon
    {
        $slaHours = $this->priority->slaHours();

        return now()->addHours($slaHours);
    }

    /**
     * Check and update SLA breach status.
     */
    public function checkSlaBreach(): void
    {
        if ($this->isOverdue() && !$this->is_sla_breached) {
            $this->is_sla_breached = true;
            $this->save();
        }
    }

    /**
     * Get the requester display name.
     */
    public function getRequesterDisplay(): string
    {
        return $this->requester_name ?: $this->requester_email;
    }
}
