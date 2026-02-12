<?php

declare(strict_types=1);

namespace App\Data\WhatsApp;

use App\Models\WhatsApp\WhatsAppMessage;
use Spatie\LaravelData\Data;

final class WhatsAppMessageData extends Data
{
    public function __construct(
        public string $id,
        public string $conversation_id,
        public ?string $wamid,
        public string $direction,
        public string $type,
        public ?string $content_text,
        public ?array $content_payload,
        public ?string $media_url,
        public ?string $media_mime_type,
        public ?string $sent_by_user_id,
        public ?string $sent_by_name,
        public string $status,
        public ?string $error_code,
        public ?string $error_message,
        public string $platform_timestamp,
        public string $created_at,
    ) {}

    public static function fromModel(WhatsAppMessage $message): self
    {
        return new self(
            id: $message->id,
            conversation_id: $message->conversation_id,
            wamid: $message->wamid,
            direction: $message->direction->value,
            type: $message->type->value,
            content_text: $message->content_text,
            content_payload: $message->content_payload,
            media_url: $message->media_url,
            media_mime_type: $message->media_mime_type,
            sent_by_user_id: $message->sent_by_user_id,
            sent_by_name: $message->sentByUser?->name,
            status: $message->status->value,
            error_code: $message->error_code,
            error_message: $message->error_message,
            platform_timestamp: $message->platform_timestamp->toIso8601String(),
            created_at: $message->created_at->toIso8601String(),
        );
    }
}
