<?php

declare(strict_types=1);

namespace App\Data\Billing;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

final class ChangePlanData extends Data
{
    public function __construct(
        #[Required]
        #[Uuid]
        public string $plan_id,
    ) {}
}
