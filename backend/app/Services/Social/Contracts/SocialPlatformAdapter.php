<?php

declare(strict_types=1);

namespace App\Services\Social\Contracts;

use App\Data\Social\OAuthTokenData;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\Social\SocialAccount;
use Illuminate\Support\Collection;

interface SocialPlatformAdapter
{
    /**
     * Exchange an authorization code for OAuth tokens.
     */
    public function exchangeCode(string $code, string $redirectUri): OAuthTokenData;

    /**
     * Refresh an existing token using a refresh token.
     */
    public function refreshToken(string $refreshToken): OAuthTokenData;

    /**
     * Revoke an access token.
     */
    public function revokeToken(string $accessToken): void;

    /**
     * Publish a post to the platform.
     *
     * @param Collection<int, \App\Models\Content\PostMedia> $media
     */
    public function publishPost(PostTarget $target, Post $post, Collection $media): PublishResult;

    /**
     * Fetch inbox items (comments, mentions) from the platform.
     *
     * @return array<array<string, mixed>>
     */
    public function fetchInboxItems(SocialAccount $account, ?\DateTimeInterface $since = null): array;

    /**
     * Fetch engagement metrics for a specific post.
     *
     * @return array<string, int>
     */
    public function fetchPostMetrics(SocialAccount $account, string $externalPostId): array;

    /**
     * Get the authenticated user/page profile.
     *
     * @return array<string, mixed>
     */
    public function getProfile(string $accessToken): array;
}
