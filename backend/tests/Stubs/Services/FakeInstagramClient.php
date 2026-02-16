<?php

declare(strict_types=1);

namespace Tests\Stubs\Services;

use App\Services\Social\InstagramClient;

/**
 * Fake Instagram Client for testing
 */
class FakeInstagramClient extends InstagramClient
{
    private array $mediaResponse = [];
    private array $commentsResponse = [];

    public function setMediaResponse(array $response): void
    {
        $this->mediaResponse = $response;
    }

    public function setCommentsResponse(array $response): void
    {
        $this->commentsResponse = $response;
    }

    public function fetchMedia(string $accountId, string $accessToken, array $options = []): array
    {
        return $this->mediaResponse;
    }

    public function fetchComments(string $mediaId, string $accessToken, array $options = []): array
    {
        return $this->commentsResponse;
    }
}
