<?php

declare(strict_types=1);

namespace App\Events\Workspace;

use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MemberAdded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Workspace $workspace,
        public readonly User $user,
    ) {}
}
