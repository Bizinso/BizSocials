<?php

declare(strict_types=1);

namespace App\Data\Auth;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class ResetPasswordData extends Data
{
    public function __construct(
        #[Required, Email]
        public string $email,
        #[Required]
        public string $token,
        #[Required, Min(8)]
        public string $password,
        #[Required]
        public string $password_confirmation,
    ) {}
}
