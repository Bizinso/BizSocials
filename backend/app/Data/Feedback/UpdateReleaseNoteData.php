<?php

declare(strict_types=1);

namespace App\Data\Feedback;

use App\Enums\Feedback\ReleaseType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;

final class UpdateReleaseNoteData extends Data
{
    /**
     * @param array<array{title: string, description?: string, change_type: string}>|null $items
     */
    public function __construct(
        #[Max(50)]
        public ?string $version = null,
        #[Max(200)]
        public ?string $title = null,
        public ?string $content = null,
        #[Max(100)]
        public ?string $version_name = null,
        public ?string $summary = null,
        public ?ReleaseType $release_type = null,
        public ?array $items = null,
    ) {}
}
