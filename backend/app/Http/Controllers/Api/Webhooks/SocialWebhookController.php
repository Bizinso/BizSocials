<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Inbox\WebhookHandlerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * SocialWebhookController
 *
 * Handles incoming webhooks from social media platforms.
 */
final class SocialWebhookController extends Controller
{
    public function __construct(
        private readonly WebhookHandlerService $webhookHandler,
    ) {}

    /**
     * Handle Facebook webhook verification (GET request).
     */
    public function verifyFacebook(Request $request): JsonResponse|string
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('services.facebook.webhook_verify_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('Facebook webhook verified');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response()->json(['error' => 'Verification failed'], 403);
    }

    /**
     * Handle Facebook webhook events (POST request).
     */
    public function handleFacebook(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $signature = $request->header('X-Hub-Signature-256', '');
            $appSecret = config('services.facebook.client_secret');

            $result = $this->webhookHandler->handleFacebookWebhook(
                $payload,
                $signature,
                $appSecret
            );

            Log::info('Facebook webhook processed', $result);

            return response()->json([
                'success' => true,
                'processed' => $result['processed'],
            ]);
        } catch (ValidationException $e) {
            Log::error('Facebook webhook validation failed', [
                'errors' => $e->errors(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Invalid webhook signature',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Facebook webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Webhook processing failed',
            ], 500);
        }
    }

    /**
     * Handle Instagram webhook verification (GET request).
     */
    public function verifyInstagram(Request $request): JsonResponse|string
    {
        // Instagram uses the same verification as Facebook
        return $this->verifyFacebook($request);
    }

    /**
     * Handle Instagram webhook events (POST request).
     */
    public function handleInstagram(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $signature = $request->header('X-Hub-Signature-256', '');
            $appSecret = config('services.facebook.client_secret'); // Instagram uses Facebook app

            $result = $this->webhookHandler->handleInstagramWebhook(
                $payload,
                $signature,
                $appSecret
            );

            Log::info('Instagram webhook processed', $result);

            return response()->json([
                'success' => true,
                'processed' => $result['processed'],
            ]);
        } catch (ValidationException $e) {
            Log::error('Instagram webhook validation failed', [
                'errors' => $e->errors(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Invalid webhook signature',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Instagram webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Webhook processing failed',
            ], 500);
        }
    }

    /**
     * Handle Twitter webhook CRC check (GET request).
     */
    public function verifyTwitter(Request $request): JsonResponse
    {
        $crcToken = $request->query('crc_token');
        
        if (!$crcToken) {
            return response()->json(['error' => 'Missing crc_token'], 400);
        }

        $consumerSecret = config('services.twitter.consumer_secret');
        $responseToken = base64_encode(hash_hmac('sha256', $crcToken, $consumerSecret, true));

        Log::info('Twitter webhook CRC verified');

        return response()->json([
            'response_token' => 'sha256=' . $responseToken,
        ]);
    }

    /**
     * Handle Twitter webhook events (POST request).
     */
    public function handleTwitter(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $signature = $request->header('X-Twitter-Webhooks-Signature', '');
            $consumerSecret = config('services.twitter.consumer_secret');

            $result = $this->webhookHandler->handleTwitterWebhook(
                $payload,
                $signature,
                $consumerSecret
            );

            Log::info('Twitter webhook processed', $result);

            return response()->json([
                'success' => true,
                'processed' => $result['processed'],
            ]);
        } catch (ValidationException $e) {
            Log::error('Twitter webhook validation failed', [
                'errors' => $e->errors(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Invalid webhook signature',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Twitter webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Webhook processing failed',
            ], 500);
        }
    }
}
