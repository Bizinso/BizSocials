<?php

declare(strict_types=1);

namespace Tests\Stubs\Services;

use App\Services\Social\FacebookClient;

/**
 * Fake Facebook Client for testing
 */
class FakeFacebookClient extends FacebookClient
{
    private array $postsResponse = [];
    private array $commentsResponse = [];

    public function setPostsResponse(array $response): void
    {
        $this->postsResponse = $response;
    }

    public function setCommentsResponse(array $response): void
    {
        $this->commentsResponse = $response;
    }

    public function fetchPosts(string $pageId, string $accessToken, array $options = []): array
    {
        return $this->postsResponse;
    }

    public function fetchComments(string $postId, string $accessToken, array $options = []): array
    {
        return $this->commentsResponse;
    }
}
