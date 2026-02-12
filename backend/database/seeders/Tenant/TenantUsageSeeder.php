<?php

declare(strict_types=1);

namespace Database\Seeders\Tenant;

use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantUsage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeder for TenantUsage model.
 *
 * Creates usage metrics for active tenants.
 */
final class TenantUsageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $periodStart = Carbon::now()->startOfMonth()->toDateString();
        $periodEnd = Carbon::now()->endOfMonth()->toDateString();

        $usageData = [
            // Acme Corporation - High usage enterprise
            'acme-corporation' => [
                'workspaces_count' => 15,
                'users_count' => 45,
                'social_accounts_count' => 30,
                'posts_published' => 450,
                'posts_scheduled' => 120,
                'storage_bytes_used' => 5368709120, // 5 GB
                'api_calls' => 25000,
                'ai_requests' => 1500,
            ],
            // StartupXYZ - New startup, low usage
            'startupxyz' => [
                'workspaces_count' => 2,
                'users_count' => 5,
                'social_accounts_count' => 4,
                'posts_published' => 25,
                'posts_scheduled' => 10,
                'storage_bytes_used' => 104857600, // 100 MB
                'api_calls' => 500,
                'ai_requests' => 50,
            ],
            // Fashion Brand Co - Medium-high usage brand
            'fashion-brand-co' => [
                'workspaces_count' => 8,
                'users_count' => 20,
                'social_accounts_count' => 25,
                'posts_published' => 350,
                'posts_scheduled' => 80,
                'storage_bytes_used' => 10737418240, // 10 GB
                'api_calls' => 15000,
                'ai_requests' => 800,
            ],
            // John Freelancer - Low usage free plan
            'john-freelancer' => [
                'workspaces_count' => 1,
                'users_count' => 1,
                'social_accounts_count' => 2,
                'posts_published' => 15,
                'posts_scheduled' => 5,
                'storage_bytes_used' => 52428800, // 50 MB
                'api_calls' => 100,
                'ai_requests' => 10,
            ],
            // Sarah Lifestyle - Active influencer
            'sarah-lifestyle' => [
                'workspaces_count' => 3,
                'users_count' => 2,
                'social_accounts_count' => 12,
                'posts_published' => 180,
                'posts_scheduled' => 45,
                'storage_bytes_used' => 3221225472, // 3 GB
                'api_calls' => 8000,
                'ai_requests' => 600,
            ],
            // Green Earth Foundation - Moderate non-profit usage
            'green-earth-foundation' => [
                'workspaces_count' => 2,
                'users_count' => 8,
                'social_accounts_count' => 6,
                'posts_published' => 60,
                'posts_scheduled' => 20,
                'storage_bytes_used' => 524288000, // 500 MB
                'api_calls' => 2000,
                'ai_requests' => 100,
            ],
            // Suspended Inc - Historical usage (before suspension)
            'suspended-inc' => [
                'workspaces_count' => 25,
                'users_count' => 100,
                'social_accounts_count' => 50,
                'posts_published' => 0, // Suspended, no new posts
                'posts_scheduled' => 200, // Had scheduled posts
                'storage_bytes_used' => 21474836480, // 20 GB
                'api_calls' => 0, // Suspended
                'ai_requests' => 0, // Suspended
            ],
        ];

        foreach ($usageData as $tenantSlug => $metrics) {
            $tenant = Tenant::where('slug', $tenantSlug)->first();

            if (! $tenant) {
                continue;
            }

            foreach ($metrics as $metricKey => $metricValue) {
                TenantUsage::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'period_start' => $periodStart,
                        'metric_key' => $metricKey,
                    ],
                    [
                        'period_end' => $periodEnd,
                        'metric_value' => $metricValue,
                    ]
                );
            }
        }

        $this->command->info('Tenant usage metrics seeded successfully.');
    }
}
