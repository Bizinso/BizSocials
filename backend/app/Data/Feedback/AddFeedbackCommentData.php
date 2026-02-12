<?php

declare(strict_types=1);

namespace App\Data\Feedback;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class AddFeedbackCommentData extends Data
{
    public function __construct(
        #[Required]
        public string $content,
        #[Max(100)]
        public ?string $commenter_name = null,
    ) {}
}
