<?php

declare(strict_types=1);

namespace App\Data\Audit;

use App\Models\Audit\DataExportRequest;
use Spatie\LaravelData\Data;

final class DataExportRequestData extends Data
{
    public function __construct(
        public string $id,
        public string $user_id,
        public string $status,
        public string $request_type,
        public string $format,
        public ?array $data_categories,
        public ?string $file_path,
        public ?int $file_size_bytes,
        public ?string $download_url,
        public int $download_count,
        public ?string $expires_at,
        public ?string $completed_at,
        public ?string $failure_reason,
        public string $created_at,
    ) {}

    /**
     * Create DataExportRequestData from a DataExportRequest model.
     */
    public static function fromModel(DataExportRequest $request): self
    {
        return new self(
            id: $request->id,
            user_id: $request->user_id ?? $request->requested_by,
            status: $request->status->value,
            request_type: $request->request_type->value,
            format: $request->format,
            data_categories: $request->data_categories,
            file_path: $request->file_path,
            file_size_bytes: $request->file_size_bytes,
            download_url: $request->getDownloadUrl(),
            download_count: $request->download_count,
            expires_at: $request->expires_at?->toIso8601String(),
            completed_at: $request->completed_at?->toIso8601String(),
            failure_reason: $request->failure_reason,
            created_at: $request->created_at->toIso8601String(),
        );
    }
}
