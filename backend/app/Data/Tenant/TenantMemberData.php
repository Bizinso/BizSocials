<?php

declare(strict_types=1);

namespace App\Data\Tenant;

use App\Models\User;
use Spatie\LaravelData\Data;

final class TenantMemberData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $role,
        public string $status,
        public ?string $avatar_url,
        public string $joined_at,
    ) {}

    /**
     * Create TenantMemberData from a User model.
     */
    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            role: $user->role_in_tenant->value,
            status: $user->status->value,
            avatar_url: $user->avatar_url,
            joined_at: $user->created_at->toIso8601String(),
        );
    }
}
