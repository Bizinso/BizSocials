<?php

declare(strict_types=1);

namespace App\Data\Workspace;

use App\Enums\Workspace\WorkspaceRole;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class UpdateMemberRoleData extends Data
{
    public function __construct(
        #[Required]
        public WorkspaceRole $role,
    ) {}
}
