<?php

declare(strict_types=1);

/**
 * InstagramAdapter Unit Tests
 *
 * Tests for the Instagram publishing adapter, specifically:
 * - Media URL resolution using getUrl() (GAP-3)
 * - Container-based publishing flow
 *
 * @see \App\Services\Social\Adapters\InstagramAdapter
 */

use App\Data\Social\PlatformCredentials;
use App\Models\Content\Post;
use App\Models\Content\PostMedia;
use App\Models\Content\PostTarget;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Social\Adapters\InstagramAdapter;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

describe('InstagramAdapter::publishPost', function () {
    beforeEach(function () {
        $this->tenant = Tenant::factory()->active()->create();
        $this->workspace = Workspace::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->credentials = new PlatformCredentials(
            appId: 'test_app_id',
            appSecret: 'test_app_secret',
            redirectUri: 'https://example.com/callback',
            apiVersion: 'v24.0',
            scopes: ['instagram_basic', 'instagram_content_publish'],
        );

        $this->igAccount = SocialAccount::factory()->instagram()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);
    });

    it('publishes single image using getUrl and container flow', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            // 1. Create media container
            new Response(200, [], json_encode(['id' => 'container_001'])),
            // 2. Publish container
            new Response(200, [], json_encode(['id' => 'media_001'])),
            // 3. Get permalink
            new Response(200, [], json_encode(['permalink' => 'https://www.instagram.com/p/test123/'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $adapter = new InstagramAdapter($client, $this->credentials);

        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Test IG post',
        ]);

        $target = PostTarget::factory()->publishing()->create([
            'post_id' => $post->id,
            'social_account_id' => $this->igAccount->id,
            'platform_code' => 'instagram',
        ]);

        $media = PostMedia::factory()->create([
            'post_id' => $post->id,
            'mime_type' => 'image/jpeg',
            'cdn_url' => 'https://cdn.example.com/ig-photo.jpg',
            'storage_path' => 'posts/ig-photo.jpg',
        ]);

        $result = $adapter->publishPost($target, $post, collect([$media]));

        expect($result->success)->toBeTrue();
        expect($result->externalPostId)->toBe('media_001');
        expect($result->externalPostUrl)->toBe('https://www.instagram.com/p/test123/');
        expect($requestHistory)->toHaveCount(3);

        // Verify first request (container creation) uses cdn_url via getUrl()
        $containerBody = (string) $requestHistory[0]['request']->getBody();
        expect($containerBody)->toContain('cdn.example.com%2Fig-photo.jpg');
    });

    it('publishes single video with VIDEO media_type', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'container_002'])),
            new Response(200, [], json_encode(['id' => 'media_002'])),
            new Response(200, [], json_encode(['permalink' => 'https://www.instagram.com/p/vid123/'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $adapter = new InstagramAdapter($client, $this->credentials);

        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $target = PostTarget::factory()->publishing()->create([
            'post_id' => $post->id,
            'social_account_id' => $this->igAccount->id,
            'platform_code' => 'instagram',
        ]);

        $media = PostMedia::factory()->video()->create([
            'post_id' => $post->id,
            'cdn_url' => 'https://cdn.example.com/ig-video.mp4',
        ]);

        $result = $adapter->publishPost($target, $post, collect([$media]));

        expect($result->success)->toBeTrue();

        // Verify container creation includes video_url and media_type
        $containerBody = (string) $requestHistory[0]['request']->getBody();
        expect($containerBody)->toContain('video_url');
        expect($containerBody)->toContain('VIDEO');
    });

    it('falls back to storage_path when cdn_url is null', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'container_003'])),
            new Response(200, [], json_encode(['id' => 'media_003'])),
            new Response(200, [], json_encode(['permalink' => 'https://www.instagram.com/p/fb123/'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $adapter = new InstagramAdapter($client, $this->credentials);

        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $target = PostTarget::factory()->publishing()->create([
            'post_id' => $post->id,
            'social_account_id' => $this->igAccount->id,
            'platform_code' => 'instagram',
        ]);

        $media = PostMedia::factory()->create([
            'post_id' => $post->id,
            'mime_type' => 'image/png',
            'cdn_url' => null,
            'storage_path' => 'posts/fallback-ig.png',
        ]);

        $result = $adapter->publishPost($target, $post, collect([$media]));

        expect($result->success)->toBeTrue();

        $containerBody = (string) $requestHistory[0]['request']->getBody();
        expect($containerBody)->toContain('fallback-ig.png');
    });

    it('returns failure on Guzzle exception', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'IG API Error',
                new \GuzzleHttp\Psr7\Request('POST', '/media'),
                new Response(400, [], json_encode([
                    'error' => ['code' => 36003, 'message' => 'Rate limit reached'],
                ]))
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $adapter = new InstagramAdapter($client, $this->credentials);

        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $target = PostTarget::factory()->publishing()->create([
            'post_id' => $post->id,
            'social_account_id' => $this->igAccount->id,
            'platform_code' => 'instagram',
        ]);

        $media = PostMedia::factory()->image()->create([
            'post_id' => $post->id,
            'cdn_url' => 'https://cdn.example.com/photo.jpg',
        ]);

        $result = $adapter->publishPost($target, $post, collect([$media]));

        expect($result->success)->toBeFalse();
        expect($result->errorCode)->toBe('36003');
        expect($result->errorMessage)->toBe('Rate limit reached');
    });
});
