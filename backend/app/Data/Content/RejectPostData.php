<?php

declare(strict_types=1);

namespace App\Data\Content;

use Spatie\LaravelData\Data;

final class RejectPostData extends Data
{
    public function __construct(
        public string $reason,
        public ?string $comment = null,
    ) {}
}
