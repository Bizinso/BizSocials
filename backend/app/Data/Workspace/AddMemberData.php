<?php

declare(strict_types=1);

namespace App\Data\Workspace;

use App\Enums\Workspace\WorkspaceRole;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

final class AddMemberData extends Data
{
    public function __construct(
        #[Required, Uuid]
        public string $user_id,
        public WorkspaceRole $role = WorkspaceRole::VIEWER,
    ) {}
}
