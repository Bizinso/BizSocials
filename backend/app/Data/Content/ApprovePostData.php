<?php

declare(strict_types=1);

namespace App\Data\Content;

use Spatie\LaravelData\Data;

final class ApprovePostData extends Data
{
    public function __construct(
        public ?string $comment = null,
    ) {}
}
