<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User\UserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class InvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly UserInvitation $invitation,
    ) {}

    public function envelope(): Envelope
    {
        $tenantName = $this->invitation->tenant->name ?? 'BizSocials';

        return new Envelope(
            subject: "You've been invited to join {$tenantName}",
        );
    }

    public function content(): Content
    {
        $frontendUrl = config('app.frontend_url', config('app.url'));

        return new Content(
            view: 'emails.invitation',
            with: [
                'invitation' => $this->invitation,
                'tenantName' => $this->invitation->tenant->name ?? 'BizSocials',
                'inviterName' => $this->invitation->inviter->name ?? 'A team member',
                'roleName' => $this->invitation->role_in_tenant->label(),
                'acceptUrl' => $frontendUrl . '/invitations/accept?token=' . $this->invitation->token,
                'expiresAt' => $this->invitation->expires_at->format('F j, Y'),
            ],
        );
    }
}
