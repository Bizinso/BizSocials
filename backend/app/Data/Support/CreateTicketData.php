<?php

declare(strict_types=1);

namespace App\Data\Support;

use App\Enums\Support\SupportTicketPriority;
use App\Enums\Support\SupportTicketType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class CreateTicketData extends Data
{
    public function __construct(
        #[Required, Max(200)]
        public string $subject,
        #[Required]
        public string $description,
        public SupportTicketType $type = SupportTicketType::QUESTION,
        public SupportTicketPriority $priority = SupportTicketPriority::MEDIUM,
        public ?string $category_id = null,
    ) {}
}
