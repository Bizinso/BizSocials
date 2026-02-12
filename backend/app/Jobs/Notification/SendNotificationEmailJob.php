<?php

declare(strict_types=1);

namespace App\Jobs\Notification;

use App\Enums\Notification\NotificationChannel;
use App\Mail\NotificationMail;
use App\Models\Notification\Notification;
use App\Models\Notification\NotificationPreference;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * SendNotificationEmailJob
 *
 * Queued job to send notification emails asynchronously.
 * Handles email delivery with proper error handling and retry logic.
 *
 * Features:
 * - Respects user notification preferences
 * - Uses exponential backoff for retries
 * - Updates notification status on success/failure
 * - Logs all email sending activities
 * - Supports both notification ID and notification instance
 */
final class SendNotificationEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 60;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 900];

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public int $maxExceptions = 2;

    /**
     * The notification ID (used when constructed with ID).
     */
    private ?string $notificationId = null;

    /**
     * Create a new job instance.
     *
     * @param Notification|string $notification The notification or notification ID
     */
    public function __construct(
        public Notification|string $notification
    ) {
        $this->onQueue('notifications');

        if (is_string($notification)) {
            $this->notificationId = $notification;
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        // Resolve notification if only ID was provided
        $notification = $this->resolveNotification();

        if ($notification === null) {
            Log::warning('[SendNotificationEmailJob] Notification not found', [
                'notification_id' => $this->notificationId ?? ($this->notification instanceof Notification ? $this->notification->id : 'unknown'),
            ]);
            return;
        }

        // Skip if already sent
        if ($notification->isSent()) {
            Log::debug('[SendNotificationEmailJob] Notification already sent', [
                'notification_id' => $notification->id,
            ]);
            return;
        }

        // Skip if this is not an email notification
        if ($notification->channel !== NotificationChannel::EMAIL) {
            Log::debug('[SendNotificationEmailJob] Not an email notification', [
                'notification_id' => $notification->id,
                'channel' => $notification->channel->value,
            ]);
            return;
        }

        $user = $notification->user;

        if ($user === null) {
            Log::warning('[SendNotificationEmailJob] User not found for notification', [
                'notification_id' => $notification->id,
            ]);
            $notification->markAsFailed('User not found');
            return;
        }

        if ($user->email === null) {
            Log::warning('[SendNotificationEmailJob] User has no email address', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
            ]);
            $notification->markAsFailed('User has no email address');
            return;
        }

        // Check user preferences
        if (!$this->shouldSendEmail($user, $notification)) {
            Log::debug('[SendNotificationEmailJob] User has disabled email for this notification type', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'notification_type' => $notification->type->value,
            ]);
            $notification->markAsFailed('Email disabled by user preferences');
            return;
        }

        try {
            $this->sendEmail($user, $notification);

            // Mark the notification as sent
            $notification->markAsSent();

            Log::info('[SendNotificationEmailJob] Email sent successfully', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        } catch (Throwable $e) {
            Log::error('[SendNotificationEmailJob] Failed to send email', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Mark as failed on final attempt
            if ($this->attempts() >= $this->tries) {
                $notification->markAsFailed($e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Resolve the notification instance.
     */
    private function resolveNotification(): ?Notification
    {
        if ($this->notification instanceof Notification) {
            return $this->notification;
        }

        return Notification::find($this->notification);
    }

    /**
     * Check if email should be sent based on user preferences.
     */
    private function shouldSendEmail(User $user, Notification $notification): bool
    {
        // Get user's preference for this notification type
        $preference = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('notification_type', $notification->type)
            ->first();

        // If no preference exists, check if email is enabled by default
        if ($preference === null) {
            return NotificationChannel::EMAIL->isEnabledByDefault();
        }

        return $preference->email_enabled;
    }

    /**
     * Send the email to the user.
     */
    private function sendEmail(User $user, Notification $notification): void
    {
        Mail::to($user->email)->send(new NotificationMail($notification, $user));
    }

    /**
     * Handle a job failure.
     *
     * @param Throwable|null $exception
     * @return void
     */
    public function failed(?Throwable $exception): void
    {
        $notification = $this->resolveNotification();

        if ($notification !== null && !$notification->hasFailed()) {
            $notification->markAsFailed($exception?->getMessage() ?? 'Unknown error');
        }

        Log::error('[SendNotificationEmailJob] Job failed permanently', [
            'notification_id' => $notification?->id ?? ($this->notificationId ?? 'unknown'),
            'error' => $exception?->getMessage(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        $notification = $this->resolveNotification();

        if ($notification === null) {
            return ['notification'];
        }

        return [
            'notification',
            'notification:' . $notification->id,
            'user:' . $notification->user_id,
            'type:' . $notification->type->value,
        ];
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(24);
    }

    /**
     * Get the unique ID for the job.
     *
     * @return string
     */
    public function uniqueId(): string
    {
        $id = $this->notificationId ?? ($this->notification instanceof Notification ? $this->notification->id : 'unknown');

        return 'notification-email-' . $id;
    }
}
