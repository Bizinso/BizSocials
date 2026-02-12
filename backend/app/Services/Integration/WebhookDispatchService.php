<?php

declare(strict_types=1);

namespace App\Services\Integration;

use App\Jobs\Integration\DeliverWebhookJob;
use App\Models\Integration\WebhookDelivery;
use App\Models\Integration\WebhookEndpoint;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

final class WebhookDispatchService extends BaseService
{
    /**
     * Dispatch a webhook event to all active endpoints subscribed to it.
     *
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(string $event, array $payload, string $workspaceId): void
    {
        $endpoints = WebhookEndpoint::forWorkspace($workspaceId)
            ->active()
            ->forEvent($event)
            ->get();

        foreach ($endpoints as $endpoint) {
            DeliverWebhookJob::dispatch($endpoint, $event, $payload);
        }

        $this->log('Webhook event dispatched', [
            'event' => $event,
            'workspace_id' => $workspaceId,
            'endpoint_count' => $endpoints->count(),
        ]);
    }

    /**
     * List webhook endpoints for a workspace.
     *
     * @param  array<string, mixed>  $filters
     */
    public function listEndpoints(string $workspaceId, array $filters = []): LengthAwarePaginator
    {
        $query = WebhookEndpoint::forWorkspace($workspaceId);

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = min($perPage, 100);

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create a new webhook endpoint.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(string $workspaceId, array $data): WebhookEndpoint
    {
        $endpoint = WebhookEndpoint::create([
            'workspace_id' => $workspaceId,
            'url' => $data['url'],
            'secret' => Str::random(64),
            'events' => $data['events'],
            'is_active' => $data['is_active'] ?? true,
            'failure_count' => 0,
        ]);

        $this->log('Webhook endpoint created', ['endpoint_id' => $endpoint->id]);

        return $endpoint;
    }

    /**
     * Update a webhook endpoint.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(WebhookEndpoint $endpoint, array $data): WebhookEndpoint
    {
        $endpoint->update($data);

        $this->log('Webhook endpoint updated', ['endpoint_id' => $endpoint->id]);

        return $endpoint->fresh();
    }

    /**
     * Delete a webhook endpoint.
     */
    public function delete(WebhookEndpoint $endpoint): void
    {
        $this->transaction(function () use ($endpoint): void {
            $endpoint->deliveries()->delete();
            $endpoint->delete();

            $this->log('Webhook endpoint deleted', ['endpoint_id' => $endpoint->id]);
        });
    }

    /**
     * Get delivery log for an endpoint.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getDeliveries(string $endpointId, array $filters = []): LengthAwarePaginator
    {
        $query = WebhookDelivery::where('webhook_endpoint_id', $endpointId);

        $perPage = (int) ($filters['per_page'] ?? 20);
        $perPage = min($perPage, 100);

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Send a test event to an endpoint.
     */
    public function testEndpoint(WebhookEndpoint $endpoint): WebhookDelivery
    {
        $payload = [
            'event' => 'test',
            'timestamp' => now()->toIso8601String(),
            'message' => 'This is a test webhook delivery from BizSocials.',
        ];

        $signedPayload = json_encode($payload);
        $signature = $this->sign($signedPayload, $endpoint->secret);

        $startTime = microtime(true);
        $responseCode = null;
        $responseBody = null;

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-BizSocials-Event' => 'test',
                    'X-BizSocials-Signature' => $signature,
                    'X-BizSocials-Delivery' => Str::uuid()->toString(),
                ])
                ->post($endpoint->url, $payload);

            $responseCode = $response->status();
            $responseBody = $response->body();
        } catch (\Exception $e) {
            $responseBody = $e->getMessage();
        }

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        $delivery = WebhookDelivery::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event' => 'test',
            'payload' => $payload,
            'response_code' => $responseCode,
            'response_body' => $responseBody ? Str::limit($responseBody, 5000) : null,
            'duration_ms' => $durationMs,
            'delivered_at' => now(),
            'created_at' => now(),
        ]);

        $endpoint->update(['last_triggered_at' => now()]);

        $this->log('Webhook test delivery sent', [
            'endpoint_id' => $endpoint->id,
            'response_code' => $responseCode,
        ]);

        return $delivery;
    }

    /**
     * Generate HMAC-SHA256 signature.
     */
    public function sign(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }
}
