<?php

declare(strict_types=1);

namespace App\Events\Tenant;

use App\Models\User\UserInvitation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class UserInvited
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly UserInvitation $invitation,
    ) {}
}
