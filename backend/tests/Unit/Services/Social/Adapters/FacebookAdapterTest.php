<?php

declare(strict_types=1);

/**
 * FacebookAdapter Unit Tests
 *
 * Tests for the Facebook publishing adapter, specifically:
 * - Video publishing endpoint selection (GAP-4)
 * - Media URL resolution using getUrl() (GAP-3)
 *
 * @see \App\Services\Social\Adapters\FacebookAdapter
 */

use App\Data\Social\PlatformCredentials;
use App\Enums\Content\PostTargetStatus;
use App\Models\Content\Post;
use App\Models\Content\PostMedia;
use App\Models\Content\PostTarget;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Social\Adapters\FacebookAdapter;
use App\Services\Social\Contracts\PublishResult;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

describe('FacebookAdapter::publishPost', function () {
    beforeEach(function () {
        $this->tenant = Tenant::factory()->active()->create();
        $this->workspace = Workspace::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->credentials = new PlatformCredentials(
            appId: 'test_app_id',
            appSecret: 'test_app_secret',
            redirectUri: 'https://example.com/callback',
            apiVersion: 'v24.0',
            scopes: ['pages_manage_posts'],
        );

        $this->fbAccount = SocialAccount::factory()->facebook()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);
    });

    it('uses /feed endpoint for text-only posts', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => '123_456'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $adapter = new FacebookAdapter($client, $this->credentials);

        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Hello world',
        ]);

        $target = PostTarget::factory()->publishing()->create([
            'post_id' => $post->id,
            'social_account_id' => $this->fbAccount->id,
            'platform_code' => 'facebook',
        ]);

        $result = $adapter->publishPost($target, $post, collect());

        expect($result->success)->toBeTrue();
        expect($result->externalPostId)->toBe('123_456');
        expect($requestHistory)->toHaveCount(1);

        $uri = (string) $requestHistory[0]['request']->getUri();
        expect($uri)->toContain('/feed');
        expect($uri)->not->toContain('/photos');
        expect($uri)->not->toContain('/videos');
    });

    it('uses /photos endpoint for image posts', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'photo_789'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $adapter = new FacebookAdapter($client, $this->credentials);

        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Check out this image',
        ]);

        $target = PostTarget::factory()->publishing()->create([
            'post_id' => $post->id,
            'social_account_id' => $this->fbAccount->id,
            'platform_code' => 'facebook',
        ]);

        $media = PostMedia::factory()->create([
            'post_id' => $post->id,
            'mime_type' => 'image/jpeg',
            'cdn_url' => 'https://cdn.example.com/photo.jpg',
            'storage_path' => 'posts/photo.jpg',
        ]);

        $result = $adapter->publishPost($target, $post, collect([$media]));

        expect($result->success)->toBeTrue();
        expect($result->externalPostId)->toBe('photo_789');
        expect($requestHistory)->toHaveCount(1);

        $uri = (string) $requestHistory[0]['request']->getUri();
        expect($uri)->toContain('/photos');

        // Verify it used getUrl() (cdn_url takes priority)
        $body = (string) $requestHistory[0]['request']->getBody();
        expect($body)->toContain('cdn.example.com%2Fphoto.jpg');
    });

    it('uses /videos endpoint for video posts', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'video_101'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $adapter = new FacebookAdapter($client, $this->credentials);

        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Watch this video',
        ]);

        $target = PostTarget::factory()->publishing()->create([
            'post_id' => $post->id,
            'social_account_id' => $this->fbAccount->id,
            'platform_code' => 'facebook',
        ]);

        $media = PostMedia::factory()->create([
            'post_id' => $post->id,
            'mime_type' => 'video/mp4',
            'cdn_url' => 'https://cdn.example.com/video.mp4',
            'storage_path' => 'posts/video.mp4',
        ]);

        $result = $adapter->publishPost($target, $post, collect([$media]));

        expect($result->success)->toBeTrue();
        expect($result->externalPostId)->toBe('video_101');
        expect($requestHistory)->toHaveCount(1);

        $uri = (string) $requestHistory[0]['request']->getUri();
        expect($uri)->toContain('/videos');
        expect($uri)->not->toContain('/feed');
        expect($uri)->not->toContain('/photos');

        // Verify it sends file_url and description (not message)
        $body = (string) $requestHistory[0]['request']->getBody();
        expect($body)->toContain('file_url');
        expect($body)->toContain('description');
        expect($body)->not->toContain('message=');
    });

    it('returns video URL format for video posts', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'video_202'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $adapter = new FacebookAdapter($client, $this->credentials);

        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $target = PostTarget::factory()->publishing()->create([
            'post_id' => $post->id,
            'social_account_id' => $this->fbAccount->id,
            'platform_code' => 'facebook',
        ]);

        $media = PostMedia::factory()->create([
            'post_id' => $post->id,
            'mime_type' => 'video/mp4',
            'cdn_url' => 'https://cdn.example.com/video.mp4',
        ]);

        $result = $adapter->publishPost($target, $post, collect([$media]));

        expect($result->externalPostUrl)->toContain('/videos/video_202');
    });

    it('uses getUrl fallback to storage_path when cdn_url is null', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'photo_303'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $adapter = new FacebookAdapter($client, $this->credentials);

        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $target = PostTarget::factory()->publishing()->create([
            'post_id' => $post->id,
            'social_account_id' => $this->fbAccount->id,
            'platform_code' => 'facebook',
        ]);

        $media = PostMedia::factory()->create([
            'post_id' => $post->id,
            'mime_type' => 'image/png',
            'cdn_url' => null,
            'storage_path' => 'posts/fallback-image.png',
        ]);

        $result = $adapter->publishPost($target, $post, collect([$media]));

        expect($result->success)->toBeTrue();

        $body = (string) $requestHistory[0]['request']->getBody();
        expect($body)->toContain('fallback-image.png');
    });

    it('returns failure on Guzzle exception', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('POST', '/feed'),
                new Response(400, [], json_encode([
                    'error' => ['code' => 190, 'message' => 'Invalid access token'],
                ]))
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $adapter = new FacebookAdapter($client, $this->credentials);

        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $target = PostTarget::factory()->publishing()->create([
            'post_id' => $post->id,
            'social_account_id' => $this->fbAccount->id,
            'platform_code' => 'facebook',
        ]);

        $result = $adapter->publishPost($target, $post, collect());

        expect($result->success)->toBeFalse();
        expect($result->errorCode)->toBe('190');
        expect($result->errorMessage)->toBe('Invalid access token');
    });
});
