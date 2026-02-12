<?php

declare(strict_types=1);

namespace App\Models\Notification;

use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * NotificationPreference Model
 *
 * Stores user preferences for receiving notifications.
 * Allows users to enable/disable specific notification types
 * across different channels (in-app, email, push).
 *
 * @property string $id UUID primary key
 * @property string $user_id User UUID
 * @property NotificationType $notification_type Notification type
 * @property bool $in_app_enabled Whether in-app notifications are enabled
 * @property bool $email_enabled Whether email notifications are enabled
 * @property bool $push_enabled Whether push notifications are enabled
 * @property bool $sms_enabled Whether SMS notifications are enabled
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read User $user
 *
 * @method static Builder<static> forUser(string $userId)
 * @method static Builder<static> ofType(NotificationType $type)
 * @method static Builder<static> channelEnabled(NotificationChannel $channel)
 */
final class NotificationPreference extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notification_preferences';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'notification_type',
        'in_app_enabled',
        'email_enabled',
        'push_enabled',
        'sms_enabled',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'notification_type' => NotificationType::class,
            'in_app_enabled' => 'boolean',
            'email_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
        ];
    }

    /**
     * Get the user that this preference belongs to.
     *
     * @return BelongsTo<User, NotificationPreference>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by user.
     *
     * @param  Builder<NotificationPreference>  $query
     * @param  string  $userId
     * @return Builder<NotificationPreference>
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by notification type.
     *
     * @param  Builder<NotificationPreference>  $query
     * @param  NotificationType  $type
     * @return Builder<NotificationPreference>
     */
    public function scopeOfType(Builder $query, NotificationType $type): Builder
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Scope to get preferences where a specific channel is enabled.
     *
     * @param  Builder<NotificationPreference>  $query
     * @param  NotificationChannel  $channel
     * @return Builder<NotificationPreference>
     */
    public function scopeChannelEnabled(Builder $query, NotificationChannel $channel): Builder
    {
        $column = match ($channel) {
            NotificationChannel::IN_APP => 'in_app_enabled',
            NotificationChannel::EMAIL => 'email_enabled',
            NotificationChannel::PUSH => 'push_enabled',
            NotificationChannel::SMS => 'sms_enabled',
        };

        return $query->where($column, true);
    }

    /**
     * Check if a specific channel is enabled.
     *
     * @param  NotificationChannel  $channel
     * @return bool
     */
    public function isChannelEnabled(NotificationChannel $channel): bool
    {
        return match ($channel) {
            NotificationChannel::IN_APP => $this->in_app_enabled,
            NotificationChannel::EMAIL => $this->email_enabled,
            NotificationChannel::PUSH => $this->push_enabled,
            NotificationChannel::SMS => $this->sms_enabled,
        };
    }

    /**
     * Enable a specific channel.
     *
     * @param  NotificationChannel  $channel
     */
    public function enableChannel(NotificationChannel $channel): void
    {
        $column = match ($channel) {
            NotificationChannel::IN_APP => 'in_app_enabled',
            NotificationChannel::EMAIL => 'email_enabled',
            NotificationChannel::PUSH => 'push_enabled',
            NotificationChannel::SMS => 'sms_enabled',
        };

        $this->update([$column => true]);
    }

    /**
     * Disable a specific channel.
     *
     * @param  NotificationChannel  $channel
     */
    public function disableChannel(NotificationChannel $channel): void
    {
        $column = match ($channel) {
            NotificationChannel::IN_APP => 'in_app_enabled',
            NotificationChannel::EMAIL => 'email_enabled',
            NotificationChannel::PUSH => 'push_enabled',
            NotificationChannel::SMS => 'sms_enabled',
        };

        $this->update([$column => false]);
    }

    /**
     * Toggle a specific channel.
     *
     * @param  NotificationChannel  $channel
     * @return bool The new state of the channel
     */
    public function toggleChannel(NotificationChannel $channel): bool
    {
        $column = match ($channel) {
            NotificationChannel::IN_APP => 'in_app_enabled',
            NotificationChannel::EMAIL => 'email_enabled',
            NotificationChannel::PUSH => 'push_enabled',
            NotificationChannel::SMS => 'sms_enabled',
        };

        $newValue = !$this->$column;
        $this->update([$column => $newValue]);

        return $newValue;
    }

    /**
     * Get all enabled channels for this preference.
     *
     * @return array<NotificationChannel>
     */
    public function getEnabledChannels(): array
    {
        $channels = [];

        if ($this->in_app_enabled) {
            $channels[] = NotificationChannel::IN_APP;
        }
        if ($this->email_enabled) {
            $channels[] = NotificationChannel::EMAIL;
        }
        if ($this->push_enabled) {
            $channels[] = NotificationChannel::PUSH;
        }
        if ($this->sms_enabled) {
            $channels[] = NotificationChannel::SMS;
        }

        return $channels;
    }

    /**
     * Check if any channel is enabled for this notification type.
     *
     * @return bool
     */
    public function hasAnyChannelEnabled(): bool
    {
        return $this->in_app_enabled
            || $this->email_enabled
            || $this->push_enabled
            || $this->sms_enabled;
    }

    /**
     * Create or update preference for a user.
     *
     * @param  User  $user
     * @param  NotificationType  $type
     * @param  array<string, bool>  $channels
     * @return static
     */
    public static function createOrUpdateForUser(
        User $user,
        NotificationType $type,
        array $channels = []
    ): static {
        return static::updateOrCreate(
            [
                'user_id' => $user->id,
                'notification_type' => $type,
            ],
            [
                'in_app_enabled' => $channels['in_app_enabled'] ?? true,
                'email_enabled' => $channels['email_enabled'] ?? true,
                'push_enabled' => $channels['push_enabled'] ?? false,
                'sms_enabled' => $channels['sms_enabled'] ?? false,
            ]
        );
    }

    /**
     * Get the user's preference for a specific notification type.
     * Returns default preferences if not set.
     *
     * @param  User  $user
     * @param  NotificationType  $type
     * @return static
     */
    public static function getOrCreateForUser(User $user, NotificationType $type): static
    {
        return static::firstOrCreate(
            [
                'user_id' => $user->id,
                'notification_type' => $type,
            ],
            [
                'in_app_enabled' => true,
                'email_enabled' => NotificationChannel::EMAIL->isEnabledByDefault(),
                'push_enabled' => NotificationChannel::PUSH->isEnabledByDefault(),
                'sms_enabled' => NotificationChannel::SMS->isEnabledByDefault(),
            ]
        );
    }

    /**
     * Initialize default preferences for a user.
     *
     * @param  User  $user
     * @return void
     */
    public static function initializeDefaultsForUser(User $user): void
    {
        foreach (NotificationType::cases() as $type) {
            static::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'notification_type' => $type,
                ],
                [
                    'in_app_enabled' => true,
                    'email_enabled' => NotificationChannel::EMAIL->isEnabledByDefault(),
                    'push_enabled' => NotificationChannel::PUSH->isEnabledByDefault(),
                    'sms_enabled' => NotificationChannel::SMS->isEnabledByDefault(),
                ]
            );
        }
    }
}
