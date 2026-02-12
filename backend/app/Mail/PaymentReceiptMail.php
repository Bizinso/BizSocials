<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Billing\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class PaymentReceiptMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Payment $payment,
        public readonly User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Receipt — ' . config('app.name', 'BizSocials'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-receipt',
            with: [
                'user' => $this->user,
                'payment' => $this->payment,
                'amount' => number_format((float) $this->payment->amount, 2),
                'currency' => $this->payment->currency->value ?? 'INR',
                'invoiceUrl' => config('app.frontend_url', config('app.url')) . '/settings/billing/invoices',
            ],
        );
    }
}
