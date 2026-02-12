<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Audit;

use App\Enums\Audit\SessionStatus;
use App\Models\Audit\LoginHistory;
use App\Models\Audit\SessionHistory;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\Audit\LoginHistoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class LoginHistoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private LoginHistoryService $service;
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LoginHistoryService();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_log_successful_login(): void
    {
        $history = $this->service->logLogin(
            user: $this->user,
            ip: '192.168.1.1',
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        );

        $this->assertInstanceOf(LoginHistory::class, $history);
        $this->assertEquals($this->user->id, $history->user_id);
        $this->assertTrue($history->successful);
        $this->assertEquals('192.168.1.1', $history->ip_address);
    }

    public function test_can_log_failed_login(): void
    {
        $history = $this->service->logFailedLogin(
            email: $this->user->email,
            ip: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
            reason: 'Invalid credentials',
        );

        $this->assertInstanceOf(LoginHistory::class, $history);
        $this->assertFalse($history->successful);
        $this->assertEquals('Invalid credentials', $history->failure_reason);
        $this->assertEquals($this->user->id, $history->user_id);
    }

    public function test_login_creates_session(): void
    {
        $this->service->logLogin(
            user: $this->user,
            ip: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
        );

        $this->assertDatabaseHas('session_history', [
            'user_id' => $this->user->id,
            'status' => SessionStatus::ACTIVE->value,
            'is_current' => true,
        ]);
    }

    public function test_can_list_login_history(): void
    {
        LoginHistory::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $history = $this->service->listForUser($this->user);

        $this->assertCount(5, $history);
    }

    public function test_can_get_active_sessions(): void
    {
        SessionHistory::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'status' => SessionStatus::ACTIVE,
        ]);

        SessionHistory::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'status' => SessionStatus::REVOKED,
        ]);

        $sessions = $this->service->getActiveSessions($this->user);

        $this->assertCount(3, $sessions);
    }

    public function test_can_terminate_session(): void
    {
        $session = SessionHistory::factory()->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'status' => SessionStatus::ACTIVE,
            'is_current' => false,
        ]);

        $this->service->terminateSession($this->user, $session->id);

        $this->assertDatabaseHas('session_history', [
            'id' => $session->id,
            'status' => SessionStatus::REVOKED->value,
        ]);
    }

    public function test_cannot_terminate_current_session(): void
    {
        $session = SessionHistory::factory()->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'status' => SessionStatus::ACTIVE,
            'is_current' => true,
        ]);

        $this->expectException(ValidationException::class);

        $this->service->terminateSession($this->user, $session->id);
    }

    public function test_cannot_terminate_nonexistent_session(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->terminateSession($this->user, 'nonexistent-id');
    }

    public function test_can_terminate_all_sessions(): void
    {
        SessionHistory::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'status' => SessionStatus::ACTIVE,
            'is_current' => false,
        ]);

        $this->service->terminateAllSessions($this->user);

        $this->assertEquals(
            0,
            SessionHistory::forUser($this->user->id)->active()->count()
        );
    }

    public function test_terminate_all_preserves_current_session(): void
    {
        $currentSession = SessionHistory::factory()->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'status' => SessionStatus::ACTIVE,
            'is_current' => true,
        ]);

        SessionHistory::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'status' => SessionStatus::ACTIVE,
            'is_current' => false,
        ]);

        $this->service->terminateAllSessions($this->user);

        // Current session should still be active
        $this->assertDatabaseHas('session_history', [
            'id' => $currentSession->id,
            'status' => SessionStatus::ACTIVE->value,
        ]);
    }
}
