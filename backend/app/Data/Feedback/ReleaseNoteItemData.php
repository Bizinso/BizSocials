<?php

declare(strict_types=1);

namespace App\Data\Feedback;

use App\Models\Feedback\ReleaseNoteItem;
use Spatie\LaravelData\Data;

final class ReleaseNoteItemData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $description,
        public string $change_type,
        public string $change_type_label,
        public int $sort_order,
    ) {}

    /**
     * Create ReleaseNoteItemData from a ReleaseNoteItem model.
     */
    public static function fromModel(ReleaseNoteItem $item): self
    {
        return new self(
            id: $item->id,
            title: $item->title,
            description: $item->description,
            change_type: $item->change_type->value,
            change_type_label: $item->change_type->label(),
            sort_order: $item->sort_order,
        );
    }
}
