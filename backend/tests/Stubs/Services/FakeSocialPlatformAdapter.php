<?php

declare(strict_types=1);

namespace Tests\Stubs\Services;

use App\Data\Social\OAuthTokenData;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\Social\SocialAccount;
use App\Services\Social\Contracts\PublishResult;
use App\Services\Social\Contracts\SocialPlatformAdapter;
use Illuminate\Support\Collection;

/**
 * Fake social platform adapter for testing.
 *
 * Returns dummy data instead of making real HTTP calls.
 */
class FakeSocialPlatformAdapter implements SocialPlatformAdapter
{
    public bool $shouldFailPublish = false;

    public function exchangeCode(string $code, string $redirectUri): OAuthTokenData
    {
        return new OAuthTokenData(
            access_token: 'fake_access_token_' . uniqid(),
            refresh_token: 'fake_refresh_token_' . uniqid(),
            expires_in: 3600,
            platform_account_id: 'fake_account_' . uniqid(),
            account_name: 'Fake Test Account',
            account_username: 'fake_user',
            profile_image_url: 'https://example.com/avatar.jpg',
            metadata: ['test' => true],
        );
    }

    public function refreshToken(string $refreshToken): OAuthTokenData
    {
        return new OAuthTokenData(
            access_token: 'refreshed_access_token_' . uniqid(),
            refresh_token: 'refreshed_refresh_token_' . uniqid(),
            expires_in: 3600,
            platform_account_id: 'fake_account_' . uniqid(),
            account_name: 'Fake Test Account',
            account_username: 'fake_user',
            profile_image_url: 'https://example.com/avatar.jpg',
            metadata: null,
        );
    }

    public function revokeToken(string $accessToken): void
    {
        // No-op in tests
    }

    public function publishPost(PostTarget $target, Post $post, Collection $media): PublishResult
    {
        if ($this->shouldFailPublish) {
            return PublishResult::failure('TEST_ERROR', 'Fake publish failure');
        }

        return PublishResult::success(
            externalPostId: 'fake_post_' . uniqid(),
            externalPostUrl: 'https://example.com/posts/fake',
        );
    }

    public function fetchInboxItems(SocialAccount $account, ?\DateTimeInterface $since = null): array
    {
        return [];
    }

    public function fetchPostMetrics(SocialAccount $account, string $externalPostId): array
    {
        return [
            'impressions' => 0,
            'reach' => 0,
            'engagements' => 0,
            'likes' => 0,
            'comments' => 0,
            'shares' => 0,
            'saves' => 0,
            'clicks' => 0,
            'video_views' => 0,
        ];
    }

    public function getProfile(string $accessToken): array
    {
        return [
            'id' => 'fake_profile_id',
            'name' => 'Fake Test Account',
        ];
    }
}
