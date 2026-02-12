<?php

declare(strict_types=1);

namespace Database\Seeders\Audit;

use App\Enums\Audit\SecurityEventType;
use App\Enums\Audit\SecuritySeverity;
use App\Models\Audit\SecurityEvent;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder for SecurityEvent.
 *
 * Creates sample security events.
 */
final class SecurityEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Security Events...');

        $tenant = Tenant::first();
        $user = User::first();
        $admin = SuperAdminUser::first();

        if (!$tenant || !$user) {
            $this->command->warn('No tenant or user found. Skipping security event seeding.');
            return;
        }

        $events = $this->getSecurityEvents();

        foreach ($events as $eventData) {
            $resolveData = [];
            if ($eventData['is_resolved'] ?? false) {
                $resolveData = [
                    'resolved_by' => $admin?->id,
                    'resolved_at' => now(),
                    'resolution_notes' => $eventData['resolution_notes'] ?? 'Issue resolved',
                ];
            }

            SecurityEvent::create([
                ...$eventData,
                ...$resolveData,
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
            ]);
        }

        // Create some random security events with factory
        SecurityEvent::factory()
            ->count(5)
            ->forTenant($tenant)
            ->forUser($user)
            ->create();

        // Create some unresolved critical events
        SecurityEvent::factory()
            ->count(2)
            ->forTenant($tenant)
            ->forUser($user)
            ->suspiciousActivity()
            ->unresolved()
            ->create();

        $this->command->info('Security Events seeded successfully!');
    }

    /**
     * Get the list of security events to create.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getSecurityEvents(): array
    {
        return [
            [
                'event_type' => SecurityEventType::LOGIN_SUCCESS,
                'severity' => SecuritySeverity::INFO,
                'description' => 'User logged in from known device',
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                'country_code' => 'US',
                'city' => 'New York',
            ],
            [
                'event_type' => SecurityEventType::LOGIN_FAILURE,
                'severity' => SecuritySeverity::MEDIUM,
                'description' => 'Failed login attempt - incorrect password',
                'ip_address' => '203.0.113.50',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'country_code' => 'CN',
                'city' => 'Beijing',
                'metadata' => ['attempt_count' => 1],
            ],
            [
                'event_type' => SecurityEventType::PASSWORD_CHANGE,
                'severity' => SecuritySeverity::LOW,
                'description' => 'User changed password',
                'ip_address' => '192.168.1.100',
                'country_code' => 'US',
                'city' => 'New York',
            ],
            [
                'event_type' => SecurityEventType::MFA_ENABLED,
                'severity' => SecuritySeverity::INFO,
                'description' => 'User enabled two-factor authentication',
                'ip_address' => '192.168.1.100',
                'country_code' => 'US',
                'city' => 'New York',
            ],
            [
                'event_type' => SecurityEventType::SUSPICIOUS_ACTIVITY,
                'severity' => SecuritySeverity::HIGH,
                'description' => 'Multiple failed login attempts from different locations',
                'ip_address' => '198.51.100.25',
                'country_code' => 'RU',
                'city' => 'Moscow',
                'metadata' => [
                    'attempt_count' => 5,
                    'locations' => ['Moscow', 'Beijing', 'Lagos'],
                ],
                'is_resolved' => true,
                'resolution_notes' => 'Confirmed as legitimate user traveling. Added locations to trusted list.',
            ],
            [
                'event_type' => SecurityEventType::SESSION_INVALIDATED,
                'severity' => SecuritySeverity::LOW,
                'description' => 'User invalidated all active sessions',
                'ip_address' => '192.168.1.100',
                'country_code' => 'US',
                'city' => 'New York',
            ],
            [
                'event_type' => SecurityEventType::API_KEY_CREATED,
                'severity' => SecuritySeverity::INFO,
                'description' => 'New API key created for integration',
                'ip_address' => '192.168.1.100',
                'metadata' => ['key_name' => 'Zapier Integration'],
            ],
            [
                'event_type' => SecurityEventType::IP_WHITELISTED,
                'severity' => SecuritySeverity::INFO,
                'description' => 'Office IP address added to whitelist',
                'ip_address' => '192.168.1.100',
                'metadata' => ['whitelisted_ip' => '10.0.0.0/24', 'label' => 'Office Network'],
            ],
        ];
    }
}
