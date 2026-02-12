<?php

declare(strict_types=1);

namespace App\Data\Tenant;

use App\Enums\User\TenantRole;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class InviteUserData extends Data
{
    public function __construct(
        #[Required, Email]
        public string $email,
        public TenantRole $role = TenantRole::MEMBER,
        public ?array $workspace_ids = null,
    ) {}
}
