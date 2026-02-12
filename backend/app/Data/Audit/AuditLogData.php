<?php

declare(strict_types=1);

namespace App\Data\Audit;

use App\Models\Audit\AuditLog;
use Spatie\LaravelData\Data;

final class AuditLogData extends Data
{
    public function __construct(
        public string $id,
        public string $action,
        public string $auditable_type,
        public ?string $auditable_id,
        public ?string $user_id,
        public ?string $user_name,
        public ?string $admin_id,
        public ?string $admin_name,
        public ?string $description,
        public ?array $old_values,
        public ?array $new_values,
        public ?array $metadata,
        public ?string $ip_address,
        public ?string $user_agent,
        public ?string $request_id,
        public string $created_at,
    ) {}

    /**
     * Create AuditLogData from an AuditLog model.
     */
    public static function fromModel(AuditLog $log): self
    {
        $log->loadMissing(['user', 'admin']);

        return new self(
            id: $log->id,
            action: $log->action->value,
            auditable_type: $log->auditable_type->value,
            auditable_id: $log->auditable_id,
            user_id: $log->user_id,
            user_name: $log->user?->name,
            admin_id: $log->admin_id,
            admin_name: $log->admin?->name,
            description: $log->description,
            old_values: $log->old_values,
            new_values: $log->new_values,
            metadata: $log->metadata,
            ip_address: $log->ip_address,
            user_agent: $log->user_agent,
            request_id: $log->request_id,
            created_at: $log->created_at->toIso8601String(),
        );
    }
}
