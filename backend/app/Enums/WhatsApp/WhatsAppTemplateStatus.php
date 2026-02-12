<?php

declare(strict_types=1);

namespace App\Enums\WhatsApp;

enum WhatsAppTemplateStatus: string
{
    case DRAFT = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case DISABLED = 'disabled';
    case PAUSED = 'paused';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING_APPROVAL => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::DISABLED => 'Disabled',
            self::PAUSED => 'Paused',
        };
    }

    public function canSubmit(): bool
    {
        return $this === self::DRAFT || $this === self::REJECTED;
    }

    public function canSend(): bool
    {
        return $this === self::APPROVED;
    }

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
