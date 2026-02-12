<?php

declare(strict_types=1);

namespace App\Data\User;

use App\Models\User;
use Spatie\LaravelData\Data;

final class UserData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public ?string $avatar_url,
        public ?string $timezone,
        public string $status,
        public ?string $role_in_tenant,
        public ?string $email_verified_at,
        public string $created_at,
    ) {}

    /**
     * Create UserData from a User model.
     */
    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            avatar_url: $user->avatar_url,
            timezone: $user->timezone,
            status: $user->status->value,
            role_in_tenant: $user->role_in_tenant?->value,
            email_verified_at: $user->email_verified_at?->toIso8601String(),
            created_at: $user->created_at->toIso8601String(),
        );
    }
}
