<?php

declare(strict_types=1);

namespace App\Data\WhatsApp;

use App\Models\WhatsApp\WhatsAppCampaign;
use Spatie\LaravelData\Data;

final class WhatsAppCampaignData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $workspace_id,
        public readonly string $whatsapp_phone_number_id,
        public readonly string $template_id,
        public readonly string $name,
        public readonly string $status,
        public readonly ?string $scheduled_at,
        public readonly ?string $started_at,
        public readonly ?string $completed_at,
        public readonly int $total_recipients,
        public readonly int $sent_count,
        public readonly int $delivered_count,
        public readonly int $read_count,
        public readonly int $failed_count,
        public readonly float $delivery_rate,
        public readonly float $read_rate,
        public readonly ?array $template_params_mapping,
        public readonly ?array $audience_filter,
        public readonly ?string $template_name,
        public readonly ?string $created_by_name,
        public readonly string $created_at,
        public readonly string $updated_at,
    ) {}

    public static function fromModel(WhatsAppCampaign $campaign): self
    {
        return new self(
            id: $campaign->id,
            workspace_id: $campaign->workspace_id,
            whatsapp_phone_number_id: $campaign->whatsapp_phone_number_id,
            template_id: $campaign->template_id,
            name: $campaign->name,
            status: $campaign->status->value,
            scheduled_at: $campaign->scheduled_at?->toIso8601String(),
            started_at: $campaign->started_at?->toIso8601String(),
            completed_at: $campaign->completed_at?->toIso8601String(),
            total_recipients: $campaign->total_recipients,
            sent_count: $campaign->sent_count,
            delivered_count: $campaign->delivered_count,
            read_count: $campaign->read_count,
            failed_count: $campaign->failed_count,
            delivery_rate: $campaign->getDeliveryRate(),
            read_rate: $campaign->getReadRate(),
            template_params_mapping: $campaign->template_params_mapping,
            audience_filter: $campaign->audience_filter,
            template_name: $campaign->template?->name,
            created_by_name: $campaign->createdBy?->name,
            created_at: $campaign->created_at->toIso8601String(),
            updated_at: $campaign->updated_at->toIso8601String(),
        );
    }

    /** @return array<self> */
    public static function collection(iterable $campaigns): array
    {
        $items = [];
        foreach ($campaigns as $campaign) {
            $items[] = self::fromModel($campaign);
        }

        return $items;
    }
}
