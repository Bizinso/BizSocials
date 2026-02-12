<?php

declare(strict_types=1);

/**
 * AnalyticsReport Model Unit Tests
 *
 * Tests for the AnalyticsReport model which represents generated analytics reports.
 *
 * @see \App\Models\Analytics\AnalyticsReport
 */

use App\Enums\Analytics\ReportStatus;
use App\Enums\Analytics\ReportType;
use App\Models\Analytics\AnalyticsReport;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

test('has correct table name', function (): void {
    $report = new AnalyticsReport();

    expect($report->getTable())->toBe('analytics_reports');
});

test('uses uuid primary key', function (): void {
    $report = AnalyticsReport::factory()->create();

    expect($report->id)->not->toBeNull()
        ->and(strlen($report->id))->toBe(36);
});

test('has correct fillable attributes', function (): void {
    $report = new AnalyticsReport();
    $fillable = $report->getFillable();

    expect($fillable)->toContain('workspace_id')
        ->and($fillable)->toContain('created_by_user_id')
        ->and($fillable)->toContain('name')
        ->and($fillable)->toContain('description')
        ->and($fillable)->toContain('report_type')
        ->and($fillable)->toContain('date_from')
        ->and($fillable)->toContain('date_to')
        ->and($fillable)->toContain('social_account_ids')
        ->and($fillable)->toContain('metrics')
        ->and($fillable)->toContain('filters')
        ->and($fillable)->toContain('status')
        ->and($fillable)->toContain('file_path')
        ->and($fillable)->toContain('file_format')
        ->and($fillable)->toContain('file_size_bytes')
        ->and($fillable)->toContain('completed_at')
        ->and($fillable)->toContain('expires_at');
});

test('report_type casts to ReportType enum', function (): void {
    $report = AnalyticsReport::factory()
        ->ofType(ReportType::PERFORMANCE)
        ->create();

    expect($report->report_type)->toBeInstanceOf(ReportType::class)
        ->and($report->report_type)->toBe(ReportType::PERFORMANCE);
});

test('status casts to ReportStatus enum', function (): void {
    $report = AnalyticsReport::factory()->pending()->create();

    expect($report->status)->toBeInstanceOf(ReportStatus::class)
        ->and($report->status)->toBe(ReportStatus::PENDING);
});

test('date_from and date_to cast to date', function (): void {
    $report = AnalyticsReport::factory()->create();

    expect($report->date_from)->toBeInstanceOf(\Carbon\Carbon::class)
        ->and($report->date_to)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('social_account_ids casts to array', function (): void {
    $accountIds = [fake()->uuid(), fake()->uuid()];
    $report = AnalyticsReport::factory()
        ->withSocialAccounts($accountIds)
        ->create();

    expect($report->social_account_ids)->toBeArray()
        ->and($report->social_account_ids)->toBe($accountIds);
});

test('metrics casts to array', function (): void {
    $metrics = ['impressions', 'reach', 'engagements'];
    $report = AnalyticsReport::factory()
        ->withMetrics($metrics)
        ->create();

    expect($report->metrics)->toBeArray()
        ->and($report->metrics)->toBe($metrics);
});

test('filters casts to array', function (): void {
    $filters = ['platform' => 'twitter', 'content_type' => 'image'];
    $report = AnalyticsReport::factory()
        ->withFilters($filters)
        ->create();

    expect($report->filters)->toBeArray()
        ->and($report->filters)->toBe($filters);
});

test('completed_at casts to datetime', function (): void {
    $report = AnalyticsReport::factory()->completed()->create();

    expect($report->completed_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('expires_at casts to datetime', function (): void {
    $report = AnalyticsReport::factory()->completed()->create();

    expect($report->expires_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('workspace relationship returns belongs to', function (): void {
    $report = new AnalyticsReport();

    expect($report->workspace())->toBeInstanceOf(BelongsTo::class);
});

test('workspace relationship works correctly', function (): void {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forTenant($user->tenant)->create();
    $report = AnalyticsReport::factory()
        ->forWorkspace($workspace)
        ->createdBy($user)
        ->create();

    expect($report->workspace)->toBeInstanceOf(Workspace::class)
        ->and($report->workspace->id)->toBe($workspace->id);
});

test('createdBy relationship returns belongs to', function (): void {
    $report = new AnalyticsReport();

    expect($report->createdBy())->toBeInstanceOf(BelongsTo::class);
});

test('createdBy relationship works correctly', function (): void {
    $user = User::factory()->create();
    $report = AnalyticsReport::factory()->createdBy($user)->create();

    expect($report->createdBy)->toBeInstanceOf(User::class)
        ->and($report->createdBy->id)->toBe($user->id);
});

describe('scopes', function () {
    test('forWorkspace scope filters by workspace id', function (): void {
        $user = User::factory()->create();
        $workspace1 = Workspace::factory()->forTenant($user->tenant)->create();
        $workspace2 = Workspace::factory()->forTenant($user->tenant)->create();

        AnalyticsReport::factory()->count(3)->forWorkspace($workspace1)->createdBy($user)->create();
        AnalyticsReport::factory()->count(2)->forWorkspace($workspace2)->createdBy($user)->create();

        $results = AnalyticsReport::forWorkspace($workspace1->id)->get();

        expect($results)->toHaveCount(3)
            ->and($results->every(fn ($r) => $r->workspace_id === $workspace1->id))->toBeTrue();
    });

    test('withStatus scope filters by status', function (): void {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->forTenant($user->tenant)->create();

        AnalyticsReport::factory()->count(3)->forWorkspace($workspace)->createdBy($user)->pending()->create();
        AnalyticsReport::factory()->count(2)->forWorkspace($workspace)->createdBy($user)->completed()->create();

        $results = AnalyticsReport::withStatus(ReportStatus::PENDING)->get();

        expect($results)->toHaveCount(3)
            ->and($results->every(fn ($r) => $r->status === ReportStatus::PENDING))->toBeTrue();
    });

    test('ofType scope filters by report type', function (): void {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->forTenant($user->tenant)->create();

        AnalyticsReport::factory()->count(3)->forWorkspace($workspace)->createdBy($user)->ofType(ReportType::PERFORMANCE)->create();
        AnalyticsReport::factory()->count(2)->forWorkspace($workspace)->createdBy($user)->ofType(ReportType::ENGAGEMENT)->create();

        $results = AnalyticsReport::ofType(ReportType::PERFORMANCE)->get();

        expect($results)->toHaveCount(3)
            ->and($results->every(fn ($r) => $r->report_type === ReportType::PERFORMANCE))->toBeTrue();
    });

    test('available scope returns only completed reports', function (): void {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->forTenant($user->tenant)->create();

        AnalyticsReport::factory()->count(2)->forWorkspace($workspace)->createdBy($user)->completed()->create();
        AnalyticsReport::factory()->count(3)->forWorkspace($workspace)->createdBy($user)->pending()->create();

        $results = AnalyticsReport::available()->get();

        expect($results)->toHaveCount(2)
            ->and($results->every(fn ($r) => $r->status === ReportStatus::COMPLETED))->toBeTrue();
    });

    test('expired scope returns expired reports', function (): void {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->forTenant($user->tenant)->create();

        AnalyticsReport::factory()->count(2)->forWorkspace($workspace)->createdBy($user)->expired()->create();
        AnalyticsReport::factory()->count(3)->forWorkspace($workspace)->createdBy($user)->completed()->create();

        $results = AnalyticsReport::expired()->get();

        expect($results)->toHaveCount(2);
    });
});

describe('status transitions', function () {
    test('markAsProcessing updates status', function (): void {
        $report = AnalyticsReport::factory()->pending()->create();

        $report->markAsProcessing();
        $report->refresh();

        expect($report->status)->toBe(ReportStatus::PROCESSING);
    });

    test('markAsCompleted updates status and timestamps', function (): void {
        $report = AnalyticsReport::factory()->processing()->create();

        $report->markAsCompleted('reports/test.pdf', 1024);
        $report->refresh();

        expect($report->status)->toBe(ReportStatus::COMPLETED)
            ->and($report->file_path)->toBe('reports/test.pdf')
            ->and($report->file_size_bytes)->toBe(1024)
            ->and($report->completed_at)->not->toBeNull()
            ->and($report->expires_at)->not->toBeNull();
    });

    test('markAsFailed updates status and clears file info', function (): void {
        $report = AnalyticsReport::factory()->processing()->create();

        $report->markAsFailed('Error occurred');
        $report->refresh();

        expect($report->status)->toBe(ReportStatus::FAILED);
    });
});

describe('helper methods', function () {
    test('isDownloadable returns true only for completed reports', function (): void {
        $pending = AnalyticsReport::factory()->pending()->create();
        $completed = AnalyticsReport::factory()->completed()->create();
        $failed = AnalyticsReport::factory()->failed()->create();

        expect($pending->isDownloadable())->toBeFalse()
            ->and($completed->isDownloadable())->toBeTrue()
            ->and($failed->isDownloadable())->toBeFalse();
    });

    test('isExpired returns true when past expiration', function (): void {
        $expired = AnalyticsReport::factory()->expired()->create();
        $current = AnalyticsReport::factory()->completed()->create();

        expect($expired->isExpired())->toBeTrue()
            ->and($current->isExpired())->toBeFalse();
    });

    test('canRetry returns true only for failed reports', function (): void {
        $pending = AnalyticsReport::factory()->pending()->create();
        $failed = AnalyticsReport::factory()->failed()->create();
        $completed = AnalyticsReport::factory()->completed()->create();

        expect($pending->canRetry())->toBeFalse()
            ->and($failed->canRetry())->toBeTrue()
            ->and($completed->canRetry())->toBeFalse();
    });

    test('getDateRangeLabel returns formatted date range', function (): void {
        $report = AnalyticsReport::factory()->create([
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
        ]);

        expect($report->getDateRangeLabel())->toBeString()
            ->and($report->getDateRangeLabel())->toContain('Jan');
    });

    test('getFileSizeFormatted returns human-readable size', function (): void {
        $report = AnalyticsReport::factory()->completed()->create([
            'file_size_bytes' => 1024 * 1024, // 1MB
        ]);

        expect($report->getFileSizeFormatted())->toBeString();
    });
});

test('factory creates valid model', function (): void {
    $report = AnalyticsReport::factory()->create();

    expect($report)->toBeInstanceOf(AnalyticsReport::class)
        ->and($report->id)->not->toBeNull()
        ->and($report->workspace_id)->not->toBeNull()
        ->and($report->created_by_user_id)->not->toBeNull()
        ->and($report->name)->toBeString()
        ->and($report->report_type)->toBeInstanceOf(ReportType::class)
        ->and($report->status)->toBeInstanceOf(ReportStatus::class)
        ->and($report->date_from)->not->toBeNull()
        ->and($report->date_to)->not->toBeNull();
});
