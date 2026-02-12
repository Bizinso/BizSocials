<?php

declare(strict_types=1);

namespace App\Data\Workspace;

use App\Models\Workspace\Workspace;
use Spatie\LaravelData\Data;

final class WorkspaceData extends Data
{
    public function __construct(
        public string $id,
        public string $tenant_id,
        public string $name,
        public string $slug,
        public ?string $description,
        public string $status,
        public ?string $icon,
        public ?string $color,
        public array $settings,
        public int $member_count,
        public ?string $current_user_role,
        public string $created_at,
    ) {}

    /**
     * Create WorkspaceData from a Workspace model.
     */
    public static function fromModel(Workspace $workspace, ?string $currentUserRole = null): self
    {
        return new self(
            id: $workspace->id,
            tenant_id: $workspace->tenant_id,
            name: $workspace->name,
            slug: $workspace->slug,
            description: $workspace->description,
            status: $workspace->status->value,
            icon: $workspace->getSetting('icon'),
            color: $workspace->getSetting('color'),
            settings: $workspace->settings ?? [],
            member_count: $workspace->getMemberCount(),
            current_user_role: $currentUserRole,
            created_at: $workspace->created_at->toIso8601String(),
        );
    }
}
