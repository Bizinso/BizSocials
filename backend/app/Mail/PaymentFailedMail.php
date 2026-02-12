<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Billing\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class PaymentFailedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Subscription $subscription,
        public readonly User $user,
        public readonly string $reason,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Failed — Action Required',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-failed',
            with: [
                'user' => $this->user,
                'subscription' => $this->subscription,
                'reason' => $this->reason,
                'planName' => $this->subscription->plan?->name ?? 'Your Plan',
                'billingUrl' => config('app.frontend_url', config('app.url')) . '/settings/billing',
            ],
        );
    }
}
