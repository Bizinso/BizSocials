<?php

declare(strict_types=1);

namespace Database\Seeders\Audit;

use App\Enums\Audit\AuditAction;
use App\Enums\Audit\AuditableType;
use App\Models\Audit\AuditLog;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder for AuditLog.
 *
 * Creates sample audit log entries.
 */
final class AuditLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Audit Logs...');

        $tenant = Tenant::first();
        $user = User::first();
        $admin = SuperAdminUser::first();

        if (!$tenant || !$user) {
            $this->command->warn('No tenant or user found. Skipping audit log seeding.');
            return;
        }

        $logs = $this->getAuditLogs();

        foreach ($logs as $logData) {
            $byAdmin = $logData['by_admin'] ?? false;
            unset($logData['by_admin']);
            AuditLog::create([
                ...$logData,
                'tenant_id' => $tenant->id,
                'user_id' => $logData['action'] === AuditAction::LOGIN || $logData['action'] === AuditAction::LOGOUT
                    ? $user->id
                    : ($logData['user_id'] ?? $user->id),
                'admin_id' => $byAdmin ? $admin?->id : null,
            ]);
        }

        // Create some random audit logs with factory
        AuditLog::factory()
            ->count(10)
            ->forTenant($tenant)
            ->forUser($user)
            ->create();

        $this->command->info('Audit Logs seeded successfully!');
    }

    /**
     * Get the list of audit logs to create.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getAuditLogs(): array
    {
        return [
            [
                'action' => AuditAction::LOGIN,
                'auditable_type' => AuditableType::USER,
                'description' => 'User logged in successfully',
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                'request_id' => fake()->uuid(),
            ],
            [
                'action' => AuditAction::CREATE,
                'auditable_type' => AuditableType::POST,
                'auditable_id' => fake()->uuid(),
                'description' => 'Created new social media post',
                'new_values' => ['title' => 'Welcome post', 'status' => 'draft'],
                'ip_address' => '192.168.1.100',
                'request_id' => fake()->uuid(),
            ],
            [
                'action' => AuditAction::UPDATE,
                'auditable_type' => AuditableType::SETTINGS,
                'description' => 'Updated tenant settings',
                'old_values' => ['timezone' => 'UTC'],
                'new_values' => ['timezone' => 'America/New_York'],
                'ip_address' => '192.168.1.100',
                'request_id' => fake()->uuid(),
            ],
            [
                'action' => AuditAction::SUBSCRIPTION_CHANGE,
                'auditable_type' => AuditableType::SUBSCRIPTION,
                'auditable_id' => fake()->uuid(),
                'description' => 'Upgraded subscription plan',
                'old_values' => ['plan' => 'starter'],
                'new_values' => ['plan' => 'professional'],
                'ip_address' => '192.168.1.100',
                'request_id' => fake()->uuid(),
            ],
            [
                'action' => AuditAction::DELETE,
                'auditable_type' => AuditableType::SOCIAL_ACCOUNT,
                'auditable_id' => fake()->uuid(),
                'description' => 'Removed Twitter account connection',
                'old_values' => ['platform' => 'twitter', 'username' => '@example'],
                'ip_address' => '192.168.1.100',
                'request_id' => fake()->uuid(),
            ],
            [
                'action' => AuditAction::EXPORT,
                'auditable_type' => AuditableType::USER,
                'description' => 'Exported user data for GDPR request',
                'metadata' => ['format' => 'json', 'categories' => ['profile', 'posts']],
                'ip_address' => '192.168.1.100',
                'request_id' => fake()->uuid(),
                'by_admin' => true,
            ],
            [
                'action' => AuditAction::PERMISSION_CHANGE,
                'auditable_type' => AuditableType::USER,
                'auditable_id' => fake()->uuid(),
                'description' => 'Changed user role to admin',
                'old_values' => ['role' => 'member'],
                'new_values' => ['role' => 'admin'],
                'ip_address' => '192.168.1.100',
                'request_id' => fake()->uuid(),
            ],
            [
                'action' => AuditAction::LOGOUT,
                'auditable_type' => AuditableType::USER,
                'description' => 'User logged out',
                'ip_address' => '192.168.1.100',
                'request_id' => fake()->uuid(),
            ],
        ];
    }
}
