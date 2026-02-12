<?php

declare(strict_types=1);

namespace App\Data\Admin;

use Spatie\LaravelData\Data;

final class PlatformStatsData extends Data
{
    public function __construct(
        public int $total_tenants,
        public int $active_tenants,
        public int $suspended_tenants,
        public int $total_users,
        public int $active_users,
        public int $total_workspaces,
        public int $total_subscriptions,
        public int $active_subscriptions,
        public int $trial_subscriptions,
        /** @var array<string, int> */
        public array $tenants_by_status,
        /** @var array<string, int> */
        public array $tenants_by_plan,
        /** @var array<string, int> */
        public array $users_by_status,
        /** @var array<string, int> */
        public array $signups_by_month,
        /** @var array<string, int> */
        public array $subscriptions_by_status,
        public string $generated_at,
    ) {}
}
