<?php

declare(strict_types=1);

namespace App\Data\Feedback;

use App\Enums\Feedback\FeedbackCategory;
use App\Enums\Feedback\FeedbackType;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class SubmitFeedbackData extends Data
{
    public function __construct(
        #[Required, Max(200)]
        public string $title,
        #[Required]
        public string $description,
        public FeedbackType $type = FeedbackType::FEATURE_REQUEST,
        public ?FeedbackCategory $category = null,
        #[Email]
        public ?string $email = null,
        #[Max(100)]
        public ?string $name = null,
        public bool $is_anonymous = false,
    ) {}
}
