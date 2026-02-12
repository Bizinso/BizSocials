<?php

declare(strict_types=1);

namespace App\Data\WhatsApp;

use App\Models\WhatsApp\WhatsAppPhoneNumber;
use Illuminate\Database\Eloquent\Collection;
use Spatie\LaravelData\Data;

final class WhatsAppPhoneNumberData extends Data
{
    public function __construct(
        public string $id,
        public string $phone_number_id,
        public string $phone_number,
        public string $display_name,
        public ?string $verified_name,
        public string $quality_rating,
        public string $status,
        public bool $is_primary,
        public int $daily_send_count,
        public int $daily_send_limit,
        public string $created_at,
    ) {}

    public static function fromModel(WhatsAppPhoneNumber $phone): self
    {
        return new self(
            id: $phone->id,
            phone_number_id: $phone->phone_number_id,
            phone_number: $phone->phone_number,
            display_name: $phone->display_name,
            verified_name: $phone->verified_name,
            quality_rating: $phone->quality_rating->value,
            status: $phone->status->value,
            is_primary: $phone->is_primary,
            daily_send_count: $phone->daily_send_count,
            daily_send_limit: $phone->daily_send_limit,
            created_at: $phone->created_at->toIso8601String(),
        );
    }

    /** @return array<int, self> */
    public static function collection(Collection $phones): array
    {
        return $phones->map(fn (WhatsAppPhoneNumber $p) => self::fromModel($p))->toArray();
    }
}
