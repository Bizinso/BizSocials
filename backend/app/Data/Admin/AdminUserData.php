<?php

declare(strict_types=1);

namespace App\Data\Admin;

use App\Models\User;
use Spatie\LaravelData\Data;

final class AdminUserData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $status,
        public string $status_label,
        public string $role_in_tenant,
        public string $role_label,
        public ?string $tenant_id,
        public ?string $tenant_name,
        public ?string $avatar_url,
        public ?string $phone,
        public ?string $timezone,
        public string $language,
        public bool $mfa_enabled,
        public ?string $email_verified_at,
        public ?string $last_login_at,
        public ?string $last_active_at,
        public string $created_at,
        public string $updated_at,
    ) {}

    /**
     * Create AdminUserData from a User model.
     */
    public static function fromModel(User $user): self
    {
        $user->loadMissing(['tenant']);

        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            status: $user->status->value,
            status_label: $user->status->label(),
            role_in_tenant: $user->role_in_tenant->value,
            role_label: $user->role_in_tenant->label(),
            tenant_id: $user->tenant_id,
            tenant_name: $user->tenant?->name,
            avatar_url: $user->avatar_url,
            phone: $user->phone,
            timezone: $user->timezone,
            language: $user->language,
            mfa_enabled: $user->mfa_enabled,
            email_verified_at: $user->email_verified_at?->toIso8601String(),
            last_login_at: $user->last_login_at?->toIso8601String(),
            last_active_at: $user->last_active_at?->toIso8601String(),
            created_at: $user->created_at->toIso8601String(),
            updated_at: $user->updated_at->toIso8601String(),
        );
    }
}
