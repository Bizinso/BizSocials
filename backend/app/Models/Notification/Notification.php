<?php

declare(strict_types=1);

namespace App\Models\Notification;

use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationType;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notification Model
 *
 * Represents a notification sent to a user within a tenant.
 * Notifications can be delivered through various channels (in-app, email, push, SMS).
 *
 * @property string $id UUID primary key
 * @property string $tenant_id Tenant UUID
 * @property string $user_id User UUID
 * @property NotificationType $type Notification type
 * @property NotificationChannel $channel Delivery channel
 * @property string $title Notification title
 * @property string $message Notification message body
 * @property array|null $data Additional notification data (JSON)
 * @property string|null $action_url URL for the notification action
 * @property string|null $icon Icon identifier
 * @property \Carbon\Carbon|null $read_at When notification was read
 * @property \Carbon\Carbon|null $sent_at When notification was sent
 * @property \Carbon\Carbon|null $failed_at When delivery failed
 * @property string|null $failure_reason Reason for delivery failure
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read User $user
 * @property-read Tenant $tenant
 *
 * @method static Builder<static> unread()
 * @method static Builder<static> read()
 * @method static Builder<static> ofType(NotificationType $type)
 * @method static Builder<static> ofChannel(NotificationChannel $channel)
 * @method static Builder<static> sent()
 * @method static Builder<static> pending()
 * @method static Builder<static> failed()
 * @method static Builder<static> forUser(string $userId)
 * @method static Builder<static> recent(int $days)
 */
final class Notification extends Model
{
    use HasFactory;
    use HasUuids;
    use BelongsToTenant;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notifications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'channel',
        'title',
        'message',
        'data',
        'action_url',
        'icon',
        'read_at',
        'sent_at',
        'failed_at',
        'failure_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'channel' => NotificationChannel::class,
            'data' => 'array',
            'read_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /**
     * Get the user that this notification belongs to.
     *
     * @return BelongsTo<User, Notification>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get unread notifications.
     *
     * @param  Builder<Notification>  $query
     * @return Builder<Notification>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope to get read notifications.
     *
     * @param  Builder<Notification>  $query
     * @return Builder<Notification>
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope to filter by notification type.
     *
     * @param  Builder<Notification>  $query
     * @param  NotificationType  $type
     * @return Builder<Notification>
     */
    public function scopeOfType(Builder $query, NotificationType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by delivery channel.
     *
     * @param  Builder<Notification>  $query
     * @param  NotificationChannel  $channel
     * @return Builder<Notification>
     */
    public function scopeOfChannel(Builder $query, NotificationChannel $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope to get sent notifications.
     *
     * @param  Builder<Notification>  $query
     * @return Builder<Notification>
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->whereNotNull('sent_at');
    }

    /**
     * Scope to get pending notifications (not yet sent).
     *
     * @param  Builder<Notification>  $query
     * @return Builder<Notification>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('sent_at')->whereNull('failed_at');
    }

    /**
     * Scope to get failed notifications.
     *
     * @param  Builder<Notification>  $query
     * @return Builder<Notification>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->whereNotNull('failed_at');
    }

    /**
     * Scope to filter by user.
     *
     * @param  Builder<Notification>  $query
     * @param  string  $userId
     * @return Builder<Notification>
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get recent notifications.
     *
     * @param  Builder<Notification>  $query
     * @param  int  $days
     * @return Builder<Notification>
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Check if the notification has been read.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Check if the notification has been sent.
     */
    public function isSent(): bool
    {
        return $this->sent_at !== null;
    }

    /**
     * Check if the notification delivery failed.
     */
    public function hasFailed(): bool
    {
        return $this->failed_at !== null;
    }

    /**
     * Check if the notification is pending.
     */
    public function isPending(): bool
    {
        return $this->sent_at === null && $this->failed_at === null;
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Mark the notification as unread.
     */
    public function markAsUnread(): void
    {
        if ($this->read_at !== null) {
            $this->update(['read_at' => null]);
        }
    }

    /**
     * Mark the notification as sent.
     */
    public function markAsSent(): void
    {
        $this->update(['sent_at' => now()]);
    }

    /**
     * Mark the notification as failed.
     *
     * @param  string|null  $reason
     */
    public function markAsFailed(?string $reason = null): void
    {
        $this->update([
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Get the icon for this notification.
     * Uses custom icon if set, otherwise uses the default for the notification type.
     */
    public function getIcon(): string
    {
        return $this->icon ?? $this->type->defaultIcon();
    }

    /**
     * Get data value by key using dot notation.
     */
    public function getDataValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    /**
     * Check if notification is urgent.
     */
    public function isUrgent(): bool
    {
        return $this->type->isUrgent();
    }

    /**
     * Get the category of this notification.
     */
    public function getCategory(): string
    {
        return $this->type->category();
    }

    /**
     * Create a notification for a user.
     *
     * @param  User  $user
     * @param  NotificationType  $type
     * @param  string  $title
     * @param  string  $message
     * @param  NotificationChannel  $channel
     * @param  array<string, mixed>  $data
     * @param  string|null  $actionUrl
     * @param  string|null  $icon
     * @return static
     */
    public static function createForUser(
        User $user,
        NotificationType $type,
        string $title,
        string $message,
        NotificationChannel $channel = NotificationChannel::IN_APP,
        array $data = [],
        ?string $actionUrl = null,
        ?string $icon = null
    ): static {
        return static::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'type' => $type,
            'channel' => $channel,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'action_url' => $actionUrl,
            'icon' => $icon,
        ]);
    }
}
