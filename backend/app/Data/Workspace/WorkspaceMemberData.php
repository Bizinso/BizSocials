<?php

declare(strict_types=1);

namespace App\Data\Workspace;

use App\Models\Workspace\WorkspaceMembership;
use Spatie\LaravelData\Data;

final class WorkspaceMemberData extends Data
{
    public function __construct(
        public string $id,
        public string $user_id,
        public string $name,
        public string $email,
        public string $role,
        public ?string $avatar_url,
        public string $joined_at,
    ) {}

    /**
     * Create WorkspaceMemberData from a WorkspaceMembership model.
     */
    public static function fromMembership(WorkspaceMembership $membership): self
    {
        return new self(
            id: $membership->id,
            user_id: $membership->user_id,
            name: $membership->user->name,
            email: $membership->user->email,
            role: $membership->role->value,
            avatar_url: $membership->user->avatar_url,
            joined_at: $membership->joined_at->toIso8601String(),
        );
    }
}
