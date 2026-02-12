<?php

declare(strict_types=1);

namespace App\Data\Shared;

use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Data;

final class PaginationData extends Data
{
    public function __construct(
        public int $current_page,
        public int $last_page,
        public int $per_page,
        public int $total,
        public ?int $from,
        public ?int $to,
    ) {}

    /**
     * Create pagination data from a Laravel paginator.
     */
    public static function fromPaginator(LengthAwarePaginator $paginator): self
    {
        return new self(
            current_page: $paginator->currentPage(),
            last_page: $paginator->lastPage(),
            per_page: $paginator->perPage(),
            total: $paginator->total(),
            from: $paginator->firstItem(),
            to: $paginator->lastItem(),
        );
    }
}
