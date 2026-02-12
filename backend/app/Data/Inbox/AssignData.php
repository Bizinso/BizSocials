<?php

declare(strict_types=1);

namespace App\Data\Inbox;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

final class AssignData extends Data
{
    public function __construct(
        #[Required, Uuid]
        public string $user_id,
    ) {}
}
