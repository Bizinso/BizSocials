<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Social;

use App\Http\Controllers\Api\V1\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Handles incoming webhooks from social media platforms.
 *
 * These endpoints are unauthenticated — verification is done
 * via platform-specific mechanisms (hub signatures, CRC tokens, etc.).
 */
final class PlatformWebhookController extends Controller
{
    /**
     * Facebook/Instagram webhook verification (GET).
     */
    public function facebookVerify(Request $request): Response
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $expectedToken = config('services.facebook.webhook_verify_token');

        if ($mode === 'subscribe' && $token === $expectedToken) {
            Log::info('[PlatformWebhook] Facebook verification successful');

            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('[PlatformWebhook] Facebook verification failed', [
            'mode' => $mode,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Facebook/Instagram webhook event handler (POST).
     */
    public function facebookHandle(Request $request): JsonResponse
    {
        // Verify signature
        $signature = $request->header('X-Hub-Signature-256');
        $payload = $request->getContent();
        $secret = config('services.facebook.app_secret');

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSignature, $signature ?? '')) {
            Log::warning('[PlatformWebhook] Facebook invalid signature');

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $data = $request->all();
        $object = $data['object'] ?? '';

        Log::info('[PlatformWebhook] Facebook event received', [
            'object' => $object,
            'entries' => count($data['entry'] ?? []),
        ]);

        // Process entries
        foreach ($data['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $this->processFacebookChange($object, $change);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Twitter CRC challenge response (GET).
     */
    public function twitterCrc(Request $request): JsonResponse
    {
        $crcToken = $request->query('crc_token');

        if (!$crcToken) {
            return response()->json(['error' => 'Missing crc_token'], 400);
        }

        $secret = config('services.twitter.client_secret');
        $hash = hash_hmac('sha256', $crcToken, $secret, true);
        $responseToken = 'sha256=' . base64_encode($hash);

        return response()->json(['response_token' => $responseToken]);
    }

    /**
     * Twitter webhook event handler (POST).
     */
    public function twitterHandle(Request $request): JsonResponse
    {
        Log::info('[PlatformWebhook] Twitter event received', [
            'keys' => array_keys($request->all()),
        ]);

        // Handle various Twitter event types
        $data = $request->all();

        if (isset($data['tweet_create_events'])) {
            Log::info('[PlatformWebhook] Twitter: new tweet events', [
                'count' => count($data['tweet_create_events']),
            ]);
        }

        if (isset($data['direct_message_events'])) {
            Log::info('[PlatformWebhook] Twitter: DM events', [
                'count' => count($data['direct_message_events']),
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * LinkedIn webhook handler (POST).
     */
    public function linkedinHandle(Request $request): JsonResponse
    {
        Log::info('[PlatformWebhook] LinkedIn event received', [
            'keys' => array_keys($request->all()),
        ]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Process a Facebook/Instagram change event.
     *
     * @param array<string, mixed> $change
     */
    private function processFacebookChange(string $object, array $change): void
    {
        $field = $change['field'] ?? 'unknown';

        Log::info('[PlatformWebhook] Processing Facebook change', [
            'object' => $object,
            'field' => $field,
        ]);

        // These would dispatch jobs for real processing:
        // - comments: create InboxItem via SyncInboxJob
        // - messages: create InboxItem for DMs
        // - mentions: create InboxItem for mentions
    }
}
