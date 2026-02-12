<?php

declare(strict_types=1);

namespace App\Events\Tenant;

use App\Models\Tenant\Tenant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TenantCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Tenant $tenant,
    ) {}
}
