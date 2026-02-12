<?php

declare(strict_types=1);

namespace App\Jobs\WhatsApp;

use App\Models\WhatsApp\WhatsAppPhoneNumber;
use App\Services\WhatsApp\WhatsAppAnalyticsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class AggregateWhatsAppDailyMetricsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        private readonly ?string $dateString = null,
    ) {
        $this->onQueue('analytics');
    }

    public function handle(WhatsAppAnalyticsService $analyticsService): void
    {
        $date = $this->dateString ? Carbon::parse($this->dateString) : Carbon::yesterday();

        WhatsAppPhoneNumber::where('status', 'active')
            ->chunk(50, function ($phones) use ($analyticsService, $date) {
                foreach ($phones as $phone) {
                    try {
                        $analyticsService->aggregateDailyMetrics($phone, $date);
                    } catch (\Throwable $e) {
                        Log::warning('Failed to aggregate WhatsApp daily metrics', [
                            'phone_id' => $phone->id,
                            'date' => $date->toDateString(),
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
    }
}
