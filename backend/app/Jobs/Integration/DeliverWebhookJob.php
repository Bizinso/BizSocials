<?php

declare(strict_types=1);

namespace App\Jobs\Integration;

use App\Models\Integration\WebhookDelivery;
use App\Models\Integration\WebhookEndpoint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class DeliverWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string $endpointUrl,
        private readonly string $secret,
        private readonly string $event,
        private readonly array $payload,
        private readonly string $endpointId,
    ) {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $payloadData = [
            'event' => $this->event,
            'timestamp' => now()->toIso8601String(),
            'data' => $this->payload,
        ];

        $payloadJson = (string) json_encode($payloadData);
        $signature = hash_hmac('sha256', $payloadJson, $this->secret);
        $deliveryId = Str::uuid()->toString();

        $startTime = microtime(true);
        $responseCode = null;
        $responseBody = null;

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-BizSocials-Event' => $this->event,
                    'X-BizSocials-Delivery' => $deliveryId,
                ])
                ->post($this->endpointUrl, $payloadData);

            $responseCode = $response->status();
            $responseBody = Str::limit($response->body(), 5000);
        } catch (\Exception $e) {
            $responseBody = $e->getMessage();

            Log::channel('service')->error('[DeliverWebhookJob] Delivery failed', [
                'endpoint_id' => $this->endpointId,
                'event' => $this->event,
                'error' => $e->getMessage(),
            ]);
        }

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        WebhookDelivery::create([
            'webhook_endpoint_id' => $this->endpointId,
            'event' => $this->event,
            'payload' => $payloadData,
            'response_code' => $responseCode,
            'response_body' => $responseBody,
            'duration_ms' => $durationMs,
            'delivered_at' => $responseCode !== null ? now() : null,
            'created_at' => now(),
        ]);

        // Update endpoint metadata
        $endpoint = WebhookEndpoint::find($this->endpointId);

        if ($endpoint !== null) {
            $updateData = ['last_triggered_at' => now()];

            if ($responseCode === null || $responseCode >= 400) {
                $updateData['failure_count'] = $endpoint->failure_count + 1;
            } else {
                $updateData['failure_count'] = 0;
            }

            $endpoint->update($updateData);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::channel('service')->error('[DeliverWebhookJob] Job failed permanently', [
            'endpoint_id' => $this->endpointId,
            'event' => $this->event,
            'error' => $exception?->getMessage(),
        ]);

        $endpoint = WebhookEndpoint::find($this->endpointId);

        if ($endpoint !== null) {
            $endpoint->increment('failure_count');
        }
    }
}
