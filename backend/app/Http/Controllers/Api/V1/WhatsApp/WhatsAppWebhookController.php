<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Http\Controllers\Api\V1\Controller;
use App\Jobs\WhatsApp\ProcessWhatsAppWebhookJob;
use App\Services\WhatsApp\WhatsAppAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private readonly WhatsAppAccountService $accountService,
    ) {}

    /**
     * Verify webhook subscription (Meta hub challenge).
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode', '');
        $token = $request->query('hub_verify_token', '');
        $challenge = $request->query('hub_challenge', '');

        $result = $this->accountService->verifyWebhookChallenge($mode, $token, $challenge);

        if ($result !== null) {
            return response($result, 200);
        }

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming webhook payload.
     */
    public function handle(Request $request): JsonResponse
    {
        // Verify X-Hub-Signature-256
        $signature = $request->header('X-Hub-Signature-256', '');
        $payload = $request->getContent();
        $appSecret = config('services.whatsapp.app_secret', '');

        if ($appSecret !== '') {
            $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);

            if (!hash_equals($expectedSignature, $signature)) {
                return response()->json(['error' => 'Invalid signature'], 403);
            }
        }

        // Dispatch to queue for async processing
        ProcessWhatsAppWebhookJob::dispatch($request->all());

        return response()->json(['status' => 'ok']);
    }
}
