<?php

declare(strict_types=1);

namespace Database\Factories\Analytics;

use App\Enums\Analytics\ReportStatus;
use App\Enums\Analytics\ReportType;
use App\Models\Analytics\AnalyticsReport;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for AnalyticsReport model.
 *
 * @extends Factory<AnalyticsReport>
 */
final class AnalyticsReportFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<AnalyticsReport>
     */
    protected $model = AnalyticsReport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $reportType = fake()->randomElement(ReportType::cases());
        $dateFrom = fake()->dateTimeBetween('-90 days', '-30 days');
        $dateTo = fake()->dateTimeBetween($dateFrom, 'now');

        return [
            'workspace_id' => Workspace::factory(),
            'created_by_user_id' => User::factory(),
            'name' => $reportType->label() . ' - ' . fake()->date('M Y'),
            'description' => fake()->boolean(50) ? fake()->sentence() : null,
            'report_type' => $reportType,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'social_account_ids' => null,
            'metrics' => null,
            'filters' => null,
            'status' => ReportStatus::PENDING,
            'file_path' => null,
            'file_format' => 'pdf',
            'file_size_bytes' => null,
            'completed_at' => null,
            'expires_at' => null,
        ];
    }

    /**
     * Associate with a specific workspace.
     */
    public function forWorkspace(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes): array => [
            'workspace_id' => $workspace->id,
        ]);
    }

    /**
     * Associate with a specific user.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_by_user_id' => $user->id,
        ]);
    }

    /**
     * Set the report type.
     */
    public function ofType(ReportType $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'report_type' => $type,
            'name' => $type->label() . ' - ' . fake()->date('M Y'),
        ]);
    }

    /**
     * Create a performance report.
     */
    public function performance(): static
    {
        return $this->ofType(ReportType::PERFORMANCE);
    }

    /**
     * Create an engagement report.
     */
    public function engagement(): static
    {
        return $this->ofType(ReportType::ENGAGEMENT);
    }

    /**
     * Create a growth report.
     */
    public function growth(): static
    {
        return $this->ofType(ReportType::GROWTH);
    }

    /**
     * Create a content report.
     */
    public function content(): static
    {
        return $this->ofType(ReportType::CONTENT);
    }

    /**
     * Create an audience report.
     */
    public function audience(): static
    {
        return $this->ofType(ReportType::AUDIENCE);
    }

    /**
     * Create a custom report.
     */
    public function custom(): static
    {
        return $this->ofType(ReportType::CUSTOM);
    }

    /**
     * Set the status.
     */
    public function withStatus(ReportStatus $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status,
        ]);
    }

    /**
     * Set status to pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReportStatus::PENDING,
            'file_path' => null,
            'file_size_bytes' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Set status to processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReportStatus::PROCESSING,
            'file_path' => null,
            'file_size_bytes' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Set status to completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReportStatus::COMPLETED,
            'file_path' => 'reports/' . fake()->uuid() . '.pdf',
            'file_size_bytes' => fake()->numberBetween(50000, 5000000),
            'completed_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
    }

    /**
     * Set status to failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReportStatus::FAILED,
            'file_path' => null,
            'file_size_bytes' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Set status to expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReportStatus::EXPIRED,
            'file_path' => 'reports/' . fake()->uuid() . '.pdf',
            'file_size_bytes' => fake()->numberBetween(50000, 5000000),
            'completed_at' => now()->subDays(35),
            'expires_at' => now()->subDays(5),
        ]);
    }

    /**
     * Set the date range.
     */
    public function forDateRange(\DateTimeInterface $from, \DateTimeInterface $to): static
    {
        return $this->state(fn (array $attributes): array => [
            'date_from' => $from,
            'date_to' => $to,
        ]);
    }

    /**
     * Set date range to last 7 days.
     */
    public function last7Days(): static
    {
        return $this->state(fn (array $attributes): array => [
            'date_from' => now()->subDays(7)->startOfDay(),
            'date_to' => now()->endOfDay(),
        ]);
    }

    /**
     * Set date range to last 30 days.
     */
    public function last30Days(): static
    {
        return $this->state(fn (array $attributes): array => [
            'date_from' => now()->subDays(30)->startOfDay(),
            'date_to' => now()->endOfDay(),
        ]);
    }

    /**
     * Set date range to last 90 days.
     */
    public function last90Days(): static
    {
        return $this->state(fn (array $attributes): array => [
            'date_from' => now()->subDays(90)->startOfDay(),
            'date_to' => now()->endOfDay(),
        ]);
    }

    /**
     * Set file format to PDF.
     */
    public function pdf(): static
    {
        return $this->state(fn (array $attributes): array => [
            'file_format' => 'pdf',
        ]);
    }

    /**
     * Set file format to CSV.
     */
    public function csv(): static
    {
        return $this->state(fn (array $attributes): array => [
            'file_format' => 'csv',
        ]);
    }

    /**
     * Set file format to XLSX.
     */
    public function xlsx(): static
    {
        return $this->state(fn (array $attributes): array => [
            'file_format' => 'xlsx',
        ]);
    }

    /**
     * Include specific social accounts.
     *
     * @param  array<string>  $accountIds
     */
    public function withSocialAccounts(array $accountIds): static
    {
        return $this->state(fn (array $attributes): array => [
            'social_account_ids' => $accountIds,
        ]);
    }

    /**
     * Include specific metrics.
     *
     * @param  array<string, mixed>  $metrics
     */
    public function withMetrics(array $metrics): static
    {
        return $this->state(fn (array $attributes): array => [
            'metrics' => $metrics,
        ]);
    }

    /**
     * Include specific filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function withFilters(array $filters): static
    {
        return $this->state(fn (array $attributes): array => [
            'filters' => $filters,
        ]);
    }

    /**
     * Create a report that is available for download.
     */
    public function available(): static
    {
        return $this->completed()->state(fn (array $attributes): array => [
            'expires_at' => now()->addDays(25),
        ]);
    }

    /**
     * Create a recently created report.
     */
    public function recent(int $days = 7): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_at' => fake()->dateTimeBetween("-{$days} days", 'now'),
        ]);
    }

    /**
     * Create an old report.
     */
    public function old(int $days = 60): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_at' => fake()->dateTimeBetween("-{$days} days", '-30 days'),
        ]);
    }
}
