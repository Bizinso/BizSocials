<?php

declare(strict_types=1);

namespace App\Data\Support;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class AddCommentData extends Data
{
    public function __construct(
        #[Required]
        public string $content,
        public bool $is_internal = false,
    ) {}
}
