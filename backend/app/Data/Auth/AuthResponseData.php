<?php

declare(strict_types=1);

namespace App\Data\Auth;

use App\Data\User\UserData;
use Spatie\LaravelData\Data;

final class AuthResponseData extends Data
{
    public function __construct(
        public UserData $user,
        public string $token,
        public string $token_type = 'Bearer',
        public ?int $expires_in = null,
    ) {}
}
