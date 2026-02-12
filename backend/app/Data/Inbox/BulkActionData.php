<?php

declare(strict_types=1);

namespace App\Data\Inbox;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class BulkActionData extends Data
{
    /**
     * @param array<string> $item_ids
     */
    public function __construct(
        #[Required]
        public array $item_ids,
    ) {}
}
