<?php

declare(strict_types=1);

namespace App\Events\Workspace;

use App\Models\Workspace\Workspace;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MemberRemoved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Workspace $workspace,
        public readonly string $userId,
    ) {}
}
