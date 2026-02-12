<?php

declare(strict_types=1);

namespace App\Data\Analytics;

use App\Models\Analytics\AnalyticsReport;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

final class AnalyticsReportData extends Data
{
    public function __construct(
        public string $id,
        public string $workspace_id,
        public string $created_by_user_id,
        public ?string $created_by_user_name,
        public string $name,
        public ?string $description,
        public string $report_type,
        public string $report_type_label,
        public string $date_from,
        public string $date_to,
        public ?array $social_account_ids,
        public ?array $metrics,
        public ?array $filters,
        public string $status,
        public string $status_label,
        public ?string $file_path,
        public string $file_format,
        public ?int $file_size_bytes,
        public ?string $file_size_human,
        public bool $is_available,
        public bool $is_expired,
        public ?string $completed_at,
        public ?string $expires_at,
        public string $created_at,
        public string $updated_at,
    ) {}

    /**
     * Create AnalyticsReportData from an AnalyticsReport model.
     */
    public static function fromModel(AnalyticsReport $report): self
    {
        $report->loadMissing(['createdBy']);

        return new self(
            id: $report->id,
            workspace_id: $report->workspace_id,
            created_by_user_id: $report->created_by_user_id,
            created_by_user_name: $report->createdBy?->name,
            name: $report->name,
            description: $report->description,
            report_type: $report->report_type->value,
            report_type_label: $report->report_type->label(),
            date_from: $report->date_from->toDateString(),
            date_to: $report->date_to->toDateString(),
            social_account_ids: $report->social_account_ids,
            metrics: $report->metrics,
            filters: $report->filters,
            status: $report->status->value,
            status_label: $report->status->label(),
            file_path: $report->file_path,
            file_format: $report->file_format,
            file_size_bytes: $report->file_size_bytes,
            file_size_human: $report->getHumanFileSize(),
            is_available: $report->isAvailable(),
            is_expired: $report->isExpired(),
            completed_at: $report->completed_at?->toIso8601String(),
            expires_at: $report->expires_at?->toIso8601String(),
            created_at: $report->created_at->toIso8601String(),
            updated_at: $report->updated_at->toIso8601String(),
        );
    }

    /**
     * Transform a collection of AnalyticsReport models to an array of AnalyticsReportData.
     *
     * @param Collection<int, AnalyticsReport>|array<AnalyticsReport> $reports
     * @return array<int, array<string, mixed>>
     */
    public static function fromCollection(Collection|array $reports): array
    {
        $collection = $reports instanceof Collection ? $reports : collect($reports);

        return $collection->map(
            fn (AnalyticsReport $report): array => self::fromModel($report)->toArray()
        )->values()->all();
    }
}
