<?php

declare(strict_types=1);

namespace App\Jobs\WhatsApp;

use App\Services\WhatsApp\WhatsAppWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class ProcessWhatsAppWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly array $payload,
    ) {
        $this->onQueue('inbox');
    }

    public function handle(WhatsAppWebhookService $service): void
    {
        Log::info('[ProcessWhatsAppWebhookJob] Processing webhook', [
            'attempt' => $this->attempts(),
        ]);

        $service->processWebhook($this->payload);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('[ProcessWhatsAppWebhookJob] Failed permanently', [
            'error' => $exception?->getMessage(),
        ]);
    }
}
