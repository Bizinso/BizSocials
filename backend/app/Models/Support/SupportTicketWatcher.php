<?php

declare(strict_types=1);

namespace App\Models\Support;

use App\Models\Platform\SuperAdminUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SupportTicketWatcher Model
 *
 * Represents a user or admin watching a support ticket for updates.
 *
 * @property string $id UUID primary key
 * @property string $ticket_id Ticket UUID
 * @property string|null $user_id User UUID
 * @property string|null $admin_id Admin UUID
 * @property string|null $email Email address for notifications
 * @property bool $notify_on_reply Notify on new replies
 * @property bool $notify_on_status_change Notify on status changes
 * @property bool $notify_on_assignment Notify on assignment changes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read SupportTicket $ticket
 * @property-read User|null $user
 * @property-read SuperAdminUser|null $admin
 *
 * @method static Builder<static> forTicket(string $ticketId)
 * @method static Builder<static> byUser(string $userId)
 * @method static Builder<static> byAdmin(string $adminId)
 * @method static Builder<static> shouldNotifyOnReply()
 * @method static Builder<static> shouldNotifyOnStatusChange()
 */
final class SupportTicketWatcher extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'support_ticket_watchers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_id',
        'user_id',
        'admin_id',
        'email',
        'notify_on_reply',
        'notify_on_status_change',
        'notify_on_assignment',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'notify_on_reply' => 'boolean',
            'notify_on_status_change' => 'boolean',
            'notify_on_assignment' => 'boolean',
        ];
    }

    /**
     * Get the ticket.
     *
     * @return BelongsTo<SupportTicket, SupportTicketWatcher>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    /**
     * Get the user.
     *
     * @return BelongsTo<User, SupportTicketWatcher>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the admin.
     *
     * @return BelongsTo<SuperAdminUser, SupportTicketWatcher>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(SuperAdminUser::class, 'admin_id');
    }

    /**
     * Scope to filter by ticket.
     *
     * @param  Builder<SupportTicketWatcher>  $query
     * @return Builder<SupportTicketWatcher>
     */
    public function scopeForTicket(Builder $query, string $ticketId): Builder
    {
        return $query->where('ticket_id', $ticketId);
    }

    /**
     * Scope to filter by user.
     *
     * @param  Builder<SupportTicketWatcher>  $query
     * @return Builder<SupportTicketWatcher>
     */
    public function scopeByUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by admin.
     *
     * @param  Builder<SupportTicketWatcher>  $query
     * @return Builder<SupportTicketWatcher>
     */
    public function scopeByAdmin(Builder $query, string $adminId): Builder
    {
        return $query->where('admin_id', $adminId);
    }

    /**
     * Scope to get watchers who should be notified on reply.
     *
     * @param  Builder<SupportTicketWatcher>  $query
     * @return Builder<SupportTicketWatcher>
     */
    public function scopeShouldNotifyOnReply(Builder $query): Builder
    {
        return $query->where('notify_on_reply', true);
    }

    /**
     * Scope to get watchers who should be notified on status change.
     *
     * @param  Builder<SupportTicketWatcher>  $query
     * @return Builder<SupportTicketWatcher>
     */
    public function scopeShouldNotifyOnStatusChange(Builder $query): Builder
    {
        return $query->where('notify_on_status_change', true);
    }

    /**
     * Check if the watcher is a user.
     */
    public function isUser(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Check if the watcher is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->admin_id !== null;
    }

    /**
     * Get the watcher's email address.
     */
    public function getWatcherEmail(): ?string
    {
        if ($this->email) {
            return $this->email;
        }

        if ($this->user) {
            return $this->user->email;
        }

        if ($this->admin) {
            return $this->admin->email;
        }

        return null;
    }

    /**
     * Check if the watcher should be notified for a given event type.
     */
    public function shouldNotifyFor(string $eventType): bool
    {
        return match ($eventType) {
            'reply' => $this->notify_on_reply,
            'status_change' => $this->notify_on_status_change,
            'assignment' => $this->notify_on_assignment,
            default => false,
        };
    }
}
