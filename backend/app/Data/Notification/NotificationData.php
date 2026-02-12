<?php

declare(strict_types=1);

namespace App\Data\Notification;

use App\Models\Notification\Notification;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

final class NotificationData extends Data
{
    public function __construct(
        public string $id,
        public string $user_id,
        public string $type,
        public string $type_label,
        public string $category,
        public string $channel,
        public string $title,
        public string $message,
        public ?array $data,
        public ?string $action_url,
        public string $icon,
        public bool $is_read,
        public bool $is_urgent,
        public ?string $read_at,
        public ?string $sent_at,
        public string $created_at,
    ) {}

    /**
     * Create NotificationData from a Notification model.
     */
    public static function fromModel(Notification $notification): self
    {
        return new self(
            id: $notification->id,
            user_id: $notification->user_id,
            type: $notification->type->value,
            type_label: $notification->type->label(),
            category: $notification->type->category(),
            channel: $notification->channel->value,
            title: $notification->title,
            message: $notification->message,
            data: $notification->data,
            action_url: $notification->action_url,
            icon: $notification->getIcon(),
            is_read: $notification->isRead(),
            is_urgent: $notification->isUrgent(),
            read_at: $notification->read_at?->toIso8601String(),
            sent_at: $notification->sent_at?->toIso8601String(),
            created_at: $notification->created_at->toIso8601String(),
        );
    }

    /**
     * Transform a collection of Notification models to an array of NotificationData.
     *
     * @param Collection<int, Notification>|array<Notification> $notifications
     * @return array<int, array<string, mixed>>
     */
    public static function fromCollection(Collection|array $notifications): array
    {
        $collection = $notifications instanceof Collection ? $notifications : collect($notifications);

        return $collection->map(
            fn (Notification $notification): array => self::fromModel($notification)->toArray()
        )->values()->all();
    }
}
