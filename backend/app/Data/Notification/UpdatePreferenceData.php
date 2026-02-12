<?php

declare(strict_types=1);

namespace App\Data\Notification;

use App\Enums\Notification\NotificationType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/**
 * UpdatePreferenceData DTO
 *
 * Data Transfer Object for updating a notification preference.
 * Used for validating and processing preference update requests.
 */
final class UpdatePreferenceData extends Data
{
    public function __construct(
        #[Required, Enum(NotificationType::class)]
        public string $notification_type,

        #[Required, BooleanType]
        public bool $in_app_enabled,

        #[Required, BooleanType]
        public bool $email_enabled,

        #[Required, BooleanType]
        public bool $push_enabled,
    ) {}

    /**
     * Get the NotificationType enum value.
     *
     * @return NotificationType
     */
    public function getNotificationType(): NotificationType
    {
        return NotificationType::from($this->notification_type);
    }

    /**
     * Create UpdatePreferenceData with all channels enabled.
     *
     * @param NotificationType $type
     * @return self
     */
    public static function allEnabled(NotificationType $type): self
    {
        return new self(
            notification_type: $type->value,
            in_app_enabled: true,
            email_enabled: true,
            push_enabled: true,
        );
    }

    /**
     * Create UpdatePreferenceData with default channel settings.
     *
     * @param NotificationType $type
     * @return self
     */
    public static function withDefaults(NotificationType $type): self
    {
        return new self(
            notification_type: $type->value,
            in_app_enabled: true,
            email_enabled: true,
            push_enabled: false,
        );
    }

    /**
     * Create UpdatePreferenceData with all channels disabled.
     *
     * @param NotificationType $type
     * @return self
     */
    public static function allDisabled(NotificationType $type): self
    {
        return new self(
            notification_type: $type->value,
            in_app_enabled: false,
            email_enabled: false,
            push_enabled: false,
        );
    }
}
