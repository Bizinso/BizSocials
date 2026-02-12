<?php

declare(strict_types=1);

namespace App\Data\Feedback;

use App\Models\Feedback\ReleaseNote;
use Spatie\LaravelData\Data;

final class ReleaseNoteData extends Data
{
    /**
     * @param array<array<string, mixed>> $items
     */
    public function __construct(
        public string $id,
        public string $version,
        public ?string $version_name,
        public string $slug,
        public string $title,
        public ?string $summary,
        public string $content,
        public string $release_type,
        public string $release_type_label,
        public string $status,
        public string $status_label,
        public ?string $published_at,
        public array $items,
        public string $created_at,
    ) {}

    /**
     * Create ReleaseNoteData from a ReleaseNote model.
     */
    public static function fromModel(ReleaseNote $note): self
    {
        $note->loadMissing(['items']);

        $items = $note->items->map(
            fn ($item) => ReleaseNoteItemData::fromModel($item)->toArray()
        )->toArray();

        return new self(
            id: $note->id,
            version: $note->version,
            version_name: $note->version_name,
            slug: self::generateSlug($note->version),
            title: $note->title,
            summary: $note->summary,
            content: $note->content,
            release_type: $note->release_type->value,
            release_type_label: $note->release_type->label(),
            status: $note->status->value,
            status_label: $note->status->label(),
            published_at: $note->published_at?->toIso8601String(),
            items: $items,
            created_at: $note->created_at->toIso8601String(),
        );
    }

    /**
     * Generate a URL-friendly slug from version.
     */
    private static function generateSlug(string $version): string
    {
        return 'v' . str_replace('.', '-', $version);
    }
}
