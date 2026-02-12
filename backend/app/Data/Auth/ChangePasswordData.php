<?php

declare(strict_types=1);

namespace App\Data\Auth;

use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class ChangePasswordData extends Data
{
    public function __construct(
        #[Required]
        public string $current_password,
        #[Required, Min(8)]
        public string $password,
        #[Required]
        public string $password_confirmation,
    ) {}
}
