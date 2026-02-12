<?php

declare(strict_types=1);

namespace App\Data\WhatsApp;

use App\Models\WhatsApp\WhatsAppTemplate;
use Spatie\LaravelData\Data;

final class WhatsAppTemplateData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $workspace_id,
        public readonly string $whatsapp_phone_number_id,
        public readonly ?string $meta_template_id,
        public readonly string $name,
        public readonly string $language,
        public readonly string $category,
        public readonly string $status,
        public readonly ?string $rejection_reason,
        public readonly string $header_type,
        public readonly ?string $header_content,
        public readonly string $body_text,
        public readonly ?string $footer_text,
        public readonly ?array $buttons,
        public readonly ?array $sample_values,
        public readonly int $usage_count,
        public readonly ?string $last_used_at,
        public readonly ?string $submitted_at,
        public readonly ?string $approved_at,
        public readonly ?string $phone_number,
        public readonly ?string $phone_display_name,
        public readonly string $created_at,
        public readonly string $updated_at,
    ) {}

    public static function fromModel(WhatsAppTemplate $template): self
    {
        return new self(
            id: $template->id,
            workspace_id: $template->workspace_id,
            whatsapp_phone_number_id: $template->whatsapp_phone_number_id,
            meta_template_id: $template->meta_template_id,
            name: $template->name,
            language: $template->language,
            category: $template->category->value,
            status: $template->status->value,
            rejection_reason: $template->rejection_reason,
            header_type: $template->header_type,
            header_content: $template->header_content,
            body_text: $template->body_text,
            footer_text: $template->footer_text,
            buttons: $template->buttons,
            sample_values: $template->sample_values,
            usage_count: $template->usage_count,
            last_used_at: $template->last_used_at?->toIso8601String(),
            submitted_at: $template->submitted_at?->toIso8601String(),
            approved_at: $template->approved_at?->toIso8601String(),
            phone_number: $template->phoneNumber?->phone_number,
            phone_display_name: $template->phoneNumber?->display_name,
            created_at: $template->created_at->toIso8601String(),
            updated_at: $template->updated_at->toIso8601String(),
        );
    }

    /** @return array<self> */
    public static function collection(iterable $templates): array
    {
        $items = [];
        foreach ($templates as $template) {
            $items[] = self::fromModel($template);
        }

        return $items;
    }
}
