<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Audit;

use App\Data\Audit\LoginHistoryData;
use App\Data\Audit\SessionData;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Audit\LoginHistory;
use App\Models\Audit\SessionHistory;
use App\Models\User;
use App\Services\Audit\LoginHistoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class SessionController extends Controller
{
    public function __construct(
        private readonly LoginHistoryService $loginHistoryService,
    ) {}

    /**
     * Get active sessions for the current user.
     * GET /security/sessions
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $sessions = $this->loginHistoryService->getActiveSessions($user);

        $transformedItems = $sessions->map(
            fn (SessionHistory $session) => SessionData::fromModel($session)->toArray()
        );

        return $this->success(
            $transformedItems->toArray(),
            'Active sessions retrieved successfully'
        );
    }

    /**
     * Get login history for the current user.
     * GET /security/login-history
     */
    public function loginHistory(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $limit = min((int) $request->query('limit', 20), 100);

        $history = $this->loginHistoryService->listForUser($user, $limit);

        $transformedItems = $history->map(
            fn (LoginHistory $record) => LoginHistoryData::fromModel($record)->toArray()
        );

        return $this->success(
            $transformedItems->toArray(),
            'Login history retrieved successfully'
        );
    }

    /**
     * Terminate a specific session.
     * DELETE /security/sessions/{session}
     */
    public function terminate(Request $request, string $session): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $this->loginHistoryService->terminateSession($user, $session);

            return $this->success(
                null,
                'Session terminated successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Session not found');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Terminate all sessions except the current one.
     * POST /security/sessions/terminate-all
     */
    public function terminateAll(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Get the current session ID from the request context
        $currentSessionId = $request->query('except');

        $this->loginHistoryService->terminateAllSessions($user, $currentSessionId);

        return $this->success(
            null,
            'All other sessions terminated successfully'
        );
    }
}
