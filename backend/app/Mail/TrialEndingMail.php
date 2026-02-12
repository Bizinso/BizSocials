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

final class TrialEndingMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Subscription $subscription,
        public readonly User $user,
        public readonly int $daysRemaining,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your trial ends in {$this->daysRemaining} days — " . config('app.name', 'BizSocials'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trial-ending',
            with: [
                'user' => $this->user,
                'subscription' => $this->subscription,
                'daysRemaining' => $this->daysRemaining,
                'planName' => $this->subscription->plan?->name ?? 'Your Plan',
                'trialEndDate' => $this->subscription->trial_end?->format('F j, Y'),
                'upgradeUrl' => config('app.frontend_url', config('app.url')) . '/settings/billing/plans',
            ],
        );
    }
}
