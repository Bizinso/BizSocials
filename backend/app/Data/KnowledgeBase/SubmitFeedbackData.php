<?php

declare(strict_types=1);

namespace App\Data\KnowledgeBase;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class SubmitFeedbackData extends Data
{
    public function __construct(
        #[Required]
        public bool $is_helpful,
        public ?string $category = null,
        public ?string $comment = null,
        public ?string $email = null,
    ) {}
}
