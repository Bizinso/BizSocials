<?php

declare(strict_types=1);

namespace App\Data\Inbox;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class CreateReplyData extends Data
{
    public function __construct(
        #[Required, StringType, Max(1000)]
        public string $content_text,
    ) {}
}
