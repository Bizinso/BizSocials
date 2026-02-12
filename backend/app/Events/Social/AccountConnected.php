<?php

declare(strict_types=1);

namespace App\Events\Social;

use App\Models\Social\SocialAccount;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AccountConnected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly SocialAccount $socialAccount,
    ) {}
}
