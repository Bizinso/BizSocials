<?php

declare(strict_types=1);

namespace App\Data\Feedback;

use App\Enums\Feedback\ReleaseType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class CreateReleaseNoteData extends Data
{
    /**
     * @param array<array{title: string, description?: string, change_type: string}>|null $items
     */
    public function __construct(
        #[Required, Max(50)]
        public string $version,
        #[Required, Max(200)]
        public string $title,
        #[Required]
        public string $content,
        #[Max(100)]
        public ?string $version_name = null,
        public ?string $summary = null,
        public ReleaseType $release_type = ReleaseType::MINOR,
        public ?array $items = null,
    ) {}
}
