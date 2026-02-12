<?php

declare(strict_types=1);

namespace App\Data\Tenant;

use App\Models\User\UserInvitation;
use Spatie\LaravelData\Data;

final class InvitationData extends Data
{
    public function __construct(
        public string $id,
        public string $email,
        public string $role,
        public string $status,
        public ?array $workspace_ids,
        public string $invited_by_name,
        public string $expires_at,
        public string $created_at,
    ) {}

    /**
     * Create InvitationData from a UserInvitation model.
     */
    public static function fromModel(UserInvitation $invitation): self
    {
        return new self(
            id: $invitation->id,
            email: $invitation->email,
            role: $invitation->role_in_tenant->value,
            status: $invitation->status->value,
            workspace_ids: $invitation->workspace_memberships ? array_column($invitation->workspace_memberships, 'workspace_id') : null,
            invited_by_name: $invitation->inviter?->name ?? 'Unknown',
            expires_at: $invitation->expires_at->toIso8601String(),
            created_at: $invitation->created_at->toIso8601String(),
        );
    }
}
