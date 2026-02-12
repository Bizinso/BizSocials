<?php

declare(strict_types=1);

namespace App\Services\Social\Contracts;

final class PublishResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $externalPostId = null,
        public readonly ?string $externalPostUrl = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function success(string $externalPostId, ?string $externalPostUrl = null): self
    {
        return new self(
            success: true,
            externalPostId: $externalPostId,
            externalPostUrl: $externalPostUrl,
        );
    }

    public static function failure(string $errorCode, string $errorMessage): self
    {
        return new self(
            success: false,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
        );
    }
}
