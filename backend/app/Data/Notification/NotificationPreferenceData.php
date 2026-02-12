<?php

declare(strict_types=1);

namespace App\Data\Notification;

use App\Models\Notification\NotificationPreference;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

final class NotificationPreferenceData extends Data
{
    public function __construct(
        public string $id,
        public string $user_id,
        public string $notification_type,
        public string $notification_type_label,
        public string $category,
        public bool $in_app_enabled,
        public bool $email_enabled,
        public bool $push_enabled,
        public bool $sms_enabled,
        public string $created_at,
        public string $updated_at,
    ) {}

    /**
     * Create NotificationPreferenceData from a NotificationPreference model.
     */
    public static function fromModel(NotificationPreference $preference): self
    {
        return new self(
            id: $preference->id,
            user_id: $preference->user_id,
            notification_type: $preference->notification_type->value,
            notification_type_label: $preference->notification_type->label(),
            category: $preference->notification_type->category(),
            in_app_enabled: $preference->in_app_enabled,
            email_enabled: $preference->email_enabled,
            push_enabled: $preference->push_enabled,
            sms_enabled: $preference->sms_enabled,
            created_at: $preference->created_at->toIso8601String(),
            updated_at: $preference->updated_at->toIso8601String(),
        );
    }

    /**
     * Transform a collection of NotificationPreference models to an array.
     *
     * @param Collection<int, NotificationPreference>|array<NotificationPreference> $preferences
     * @return array<int, array<string, mixed>>
     */
    public static function fromCollection(Collection|array $preferences): array
    {
        $collection = $preferences instanceof Collection ? $preferences : collect($preferences);

        return $collection->map(
            fn (NotificationPreference $preference): array => self::fromModel($preference)->toArray()
        )->values()->all();
    }
}
