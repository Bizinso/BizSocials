<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Notification\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class NotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Notification $notification,
        public readonly User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->notification->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notification',
            with: [
                'notification' => $this->notification,
                'user' => $this->user,
                'actionUrl' => $this->notification->action_url
                    ? config('app.frontend_url', config('app.url')) . $this->notification->action_url
                    : null,
                'isUrgent' => $this->notification->type->isUrgent(),
                'preferencesUrl' => config('app.frontend_url', config('app.url')) . '/settings/notifications',
            ],
        );
    }
}
