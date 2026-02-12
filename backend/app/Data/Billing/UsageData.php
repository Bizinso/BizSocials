<?php

declare(strict_types=1);

namespace App\Data\Billing;

use Spatie\LaravelData\Data;

final class UsageData extends Data
{
    public function __construct(
        public int $workspaces_used,
        public ?int $workspaces_limit,
        public int $social_accounts_used,
        public ?int $social_accounts_limit,
        public int $team_members_used,
        public ?int $team_members_limit,
        public int $posts_this_month,
        public ?int $posts_limit,
    ) {}
}
