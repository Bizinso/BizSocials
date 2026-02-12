<?php

declare(strict_types=1);

namespace App\Listeners\Tenant;

use App\Events\Tenant\UserInvited;
use App\Mail\InvitationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class SendInvitationEmail implements ShouldQueue
{
    public function handle(UserInvited $event): void
    {
        $invitation = $event->invitation;

        // Load relationships needed by the mailable
        $invitation->loadMissing(['tenant', 'inviter']);

        Mail::to($invitation->email)->send(new InvitationMail($invitation));

        Log::info('Invitation email sent', [
            'invitation_id' => $invitation->id,
            'tenant_id' => $invitation->tenant_id,
            'email' => $invitation->email,
            'role' => $invitation->role_in_tenant->value,
        ]);
    }
}
