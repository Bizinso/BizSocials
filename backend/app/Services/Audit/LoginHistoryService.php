<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Enums\Audit\SessionStatus;
use App\Models\Audit\LoginHistory;
use App\Models\Audit\SessionHistory;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class LoginHistoryService extends BaseService
{
    /**
     * Log a successful login.
     */
    public function logLogin(User $user, string $ip, string $userAgent): LoginHistory
    {
        $deviceInfo = $this->parseUserAgent($userAgent);

        $loginHistory = LoginHistory::create([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'successful' => true,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device_type' => $deviceInfo['device_type'],
            'browser' => $deviceInfo['browser'],
            'os' => $deviceInfo['os'],
        ]);

        // Create or update session history
        $this->createSession($user, $ip, $userAgent, $deviceInfo);

        $this->log('Login recorded', [
            'user_id' => $user->id,
            'ip' => $ip,
        ]);

        return $loginHistory;
    }

    /**
     * Log a failed login attempt.
     */
    public function logFailedLogin(
        string $email,
        string $ip,
        string $userAgent,
        string $reason,
    ): LoginHistory {
        $deviceInfo = $this->parseUserAgent($userAgent);

        // Find user by email if exists
        $user = User::where('email', $email)->first();

        $loginHistory = LoginHistory::create([
            'user_id' => $user?->id,
            'tenant_id' => $user?->tenant_id,
            'successful' => false,
            'failure_reason' => $reason,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device_type' => $deviceInfo['device_type'],
            'browser' => $deviceInfo['browser'],
            'os' => $deviceInfo['os'],
        ]);

        $this->log('Failed login recorded', [
            'email' => $email,
            'ip' => $ip,
            'reason' => $reason,
        ]);

        return $loginHistory;
    }

    /**
     * List login history for a user.
     */
    public function listForUser(User $user, int $limit = 20): Collection
    {
        return LoginHistory::forUser($user->id)
            ->recent($limit)
            ->get();
    }

    /**
     * Get active sessions for a user.
     */
    public function getActiveSessions(User $user): Collection
    {
        return SessionHistory::forUser($user->id)
            ->active()
            ->ordered()
            ->get();
    }

    /**
     * Terminate a specific session.
     *
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function terminateSession(User $user, string $sessionId): void
    {
        $session = SessionHistory::forUser($user->id)
            ->find($sessionId);

        if ($session === null) {
            throw new ModelNotFoundException('Session not found.');
        }

        if ($session->is_current) {
            throw ValidationException::withMessages([
                'session' => ['Cannot terminate the current session.'],
            ]);
        }

        $session->revoke($user, 'User requested termination');

        $this->log('Session terminated', [
            'user_id' => $user->id,
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Terminate all sessions for a user.
     */
    public function terminateAllSessions(User $user, ?string $exceptSessionId = null): void
    {
        $query = SessionHistory::forUser($user->id)
            ->active();

        if ($exceptSessionId !== null) {
            $query->where('id', '!=', $exceptSessionId);
        }

        $sessions = $query->get();

        foreach ($sessions as $session) {
            if (!$session->is_current) {
                $session->revoke($user, 'User terminated all sessions');
            }
        }

        $this->log('All sessions terminated', [
            'user_id' => $user->id,
            'count' => $sessions->count(),
            'except' => $exceptSessionId,
        ]);
    }

    /**
     * Create a new session for the user.
     *
     * @param array<string, string|null> $deviceInfo
     */
    private function createSession(User $user, string $ip, string $userAgent, array $deviceInfo): SessionHistory
    {
        // Mark existing sessions as not current
        SessionHistory::forUser($user->id)
            ->where('is_current', true)
            ->update(['is_current' => false]);

        return SessionHistory::create([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'session_token' => Str::random(64),
            'status' => SessionStatus::ACTIVE,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device_type' => $deviceInfo['device_type'],
            'device_name' => $deviceInfo['device_name'],
            'browser' => $deviceInfo['browser'],
            'os' => $deviceInfo['os'],
            'is_current' => true,
            'last_activity_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
    }

    /**
     * Parse user agent string to extract device info.
     *
     * @return array<string, string|null>
     */
    private function parseUserAgent(string $userAgent): array
    {
        $deviceType = 'desktop';

        // Simple mobile/tablet detection
        $mobileKeywords = ['mobile', 'android', 'iphone', 'ipod', 'blackberry', 'windows phone'];
        $tabletKeywords = ['ipad', 'tablet', 'kindle', 'playbook'];

        $userAgentLower = strtolower($userAgent);

        foreach ($tabletKeywords as $keyword) {
            if (str_contains($userAgentLower, $keyword)) {
                $deviceType = 'tablet';
                break;
            }
        }

        if ($deviceType === 'desktop') {
            foreach ($mobileKeywords as $keyword) {
                if (str_contains($userAgentLower, $keyword)) {
                    $deviceType = 'mobile';
                    break;
                }
            }
        }

        // Extract browser from user agent
        $browser = null;
        if (str_contains($userAgentLower, 'chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($userAgentLower, 'firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($userAgentLower, 'safari') && !str_contains($userAgentLower, 'chrome')) {
            $browser = 'Safari';
        } elseif (str_contains($userAgentLower, 'edge')) {
            $browser = 'Edge';
        }

        // Extract OS from user agent
        $os = null;
        if (str_contains($userAgentLower, 'windows')) {
            $os = 'Windows';
        } elseif (str_contains($userAgentLower, 'mac')) {
            $os = 'macOS';
        } elseif (str_contains($userAgentLower, 'linux')) {
            $os = 'Linux';
        } elseif (str_contains($userAgentLower, 'android')) {
            $os = 'Android';
        } elseif (str_contains($userAgentLower, 'ios') || str_contains($userAgentLower, 'iphone') || str_contains($userAgentLower, 'ipad')) {
            $os = 'iOS';
        }

        return [
            'device_type' => $deviceType,
            'device_name' => null,
            'browser' => $browser,
            'os' => $os,
        ];
    }
}
