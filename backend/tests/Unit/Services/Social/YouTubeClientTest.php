<?php

declare(strict_types=1);

/**
 * YouTubeClient Unit Tests
 *
 * Tests for the YouTube Data API v3 client service:
 * - Video upload with metadata
 * - Video metadata management (update, delete)
 * - Playlist management
 * - Analytics fetching
 * - Error handling and rate limiting
 *
 * @see \App\Services\Social\YouTubeClient
 */

use App\Services\Social\YouTubeClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\RateLimiter;

describe('YouTubeClient::uploadVideo', function () {
    beforeEach(function () {
        RateLimiter::clear('youtube_api_rate_limit:upload');
    });

    it('initializes video upload successfully', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [
                'Location' => 'https://www.googleapis.com/upload/youtube/v3/videos?uploadId=abc123',
            ], ''),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->uploadVideo(
            accessToken: 'test_token',
            videoFilePath: '/path/to/video.mp4',
            metadata: [
                'title' => 'Test Video',
                'description' => 'A test video',
                'tags' => ['test', 'demo'],
            ]
        );

        expect($result['success'])->toBeTrue();
        expect($result['upload_url'])->toContain('uploadId=abc123');
        expect($requestHistory)->toHaveCount(1);

        $uri = (string) $requestHistory[0]['request']->getUri();
        expect($uri)->toContain('/upload/youtube/v3/videos');
    });

    it('includes metadata in upload request', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [
                'Location' => 'https://www.googleapis.com/upload/youtube/v3/videos?uploadId=xyz789',
            ], ''),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->uploadVideo(
            accessToken: 'test_token',
            videoFilePath: '/path/to/video.mp4',
            metadata: [
                'title' => 'My Video',
                'description' => 'Video description',
                'tags' => ['tag1', 'tag2'],
                'category_id' => '20',
                'privacy' => 'unlisted',
                'made_for_kids' => false,
            ]
        );

        expect($result['success'])->toBeTrue();

        $body = json_decode((string) $requestHistory[0]['request']->getBody(), true);
        expect($body['snippet']['title'])->toBe('My Video');
        expect($body['snippet']['description'])->toBe('Video description');
        expect($body['snippet']['tags'])->toBe(['tag1', 'tag2']);
        expect($body['snippet']['categoryId'])->toBe('20');
        expect($body['status']['privacyStatus'])->toBe('unlisted');
        expect($body['status']['selfDeclaredMadeForKids'])->toBeFalse();
    });

    it('handles upload initialization errors', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('POST', '/videos'),
                new Response(401, [], json_encode([
                    'error' => ['message' => 'Invalid credentials'],
                ]))
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->uploadVideo(
            accessToken: 'invalid_token',
            videoFilePath: '/path/to/video.mp4',
            metadata: ['title' => 'Test']
        );

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toBe('Invalid credentials');
    });

    it('respects rate limits', function () {
        // Fill up the rate limit
        for ($i = 0; $i < 100; $i++) {
            RateLimiter::hit('youtube_api_rate_limit:upload', 3600);
        }

        $mock = new MockHandler([
            new Response(200, ['Location' => 'https://example.com/upload'], ''),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->uploadVideo(
            accessToken: 'test_token',
            videoFilePath: '/path/to/video.mp4',
            metadata: ['title' => 'Test']
        );

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toContain('Rate limit exceeded');
    });
});

describe('YouTubeClient::updateVideo', function () {
    beforeEach(function () {
        RateLimiter::clear('youtube_api_rate_limit:video123');
    });

    it('updates video metadata successfully', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'id' => 'video123',
                'snippet' => ['title' => 'Updated Title'],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->updateVideo(
            accessToken: 'test_token',
            videoId: 'video123',
            metadata: [
                'title' => 'Updated Title',
                'description' => 'Updated description',
            ]
        );

        expect($result['success'])->toBeTrue();
        expect($requestHistory)->toHaveCount(1);

        $body = json_decode((string) $requestHistory[0]['request']->getBody(), true);
        expect($body['id'])->toBe('video123');
        expect($body['snippet']['title'])->toBe('Updated Title');
        expect($body['snippet']['description'])->toBe('Updated description');
    });

    it('updates video privacy status', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'video123'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->updateVideo(
            accessToken: 'test_token',
            videoId: 'video123',
            metadata: ['privacy' => 'private']
        );

        expect($result['success'])->toBeTrue();

        $body = json_decode((string) $requestHistory[0]['request']->getBody(), true);
        expect($body['status']['privacyStatus'])->toBe('private');
    });

    it('handles update errors', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('PUT', '/videos'),
                new Response(404, [], json_encode([
                    'error' => ['message' => 'Video not found'],
                ]))
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->updateVideo(
            accessToken: 'test_token',
            videoId: 'nonexistent',
            metadata: ['title' => 'New Title']
        );

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toBe('Video not found');
    });
});

describe('YouTubeClient::deleteVideo', function () {
    beforeEach(function () {
        RateLimiter::clear('youtube_api_rate_limit:video123');
    });

    it('deletes video successfully', function () {
        $mock = new MockHandler([
            new Response(204, []),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->deleteVideo(
            accessToken: 'test_token',
            videoId: 'video123'
        );

        expect($result['success'])->toBeTrue();
    });

    it('handles delete errors', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('DELETE', '/videos'),
                new Response(403, [], json_encode([
                    'error' => ['message' => 'Insufficient permissions'],
                ]))
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->deleteVideo(
            accessToken: 'test_token',
            videoId: 'video123'
        );

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toBe('Insufficient permissions');
    });
});

describe('YouTubeClient::getVideo', function () {
    beforeEach(function () {
        RateLimiter::clear('youtube_api_rate_limit:video123');
    });

    it('fetches video details successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'items' => [
                    [
                        'id' => 'video123',
                        'snippet' => [
                            'title' => 'My Video',
                            'description' => 'Video description',
                        ],
                        'statistics' => [
                            'viewCount' => '1000',
                            'likeCount' => '50',
                        ],
                    ],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->getVideo(
            accessToken: 'test_token',
            videoId: 'video123'
        );

        expect($result['success'])->toBeTrue();
        expect($result['video']['id'])->toBe('video123');
        expect($result['video']['snippet']['title'])->toBe('My Video');
    });

    it('handles video not found', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['items' => []])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->getVideo(
            accessToken: 'test_token',
            videoId: 'nonexistent'
        );

        expect($result['success'])->toBeTrue();
        expect($result['video'])->toBeNull();
    });
});

describe('YouTubeClient::listVideos', function () {
    beforeEach(function () {
        RateLimiter::clear('youtube_api_rate_limit:channel123');
    });

    it('lists videos from channel successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'items' => [
                    ['id' => ['videoId' => 'video1'], 'snippet' => ['title' => 'Video 1']],
                    ['id' => ['videoId' => 'video2'], 'snippet' => ['title' => 'Video 2']],
                ],
                'pageInfo' => ['totalResults' => 2],
                'nextPageToken' => 'next_token',
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->listVideos(
            accessToken: 'test_token',
            channelId: 'channel123'
        );

        expect($result['success'])->toBeTrue();
        expect($result['videos'])->toHaveCount(2);
        expect($result['total_results'])->toBe(2);
        expect($result['next_page_token'])->toBe('next_token');
    });

    it('supports pagination options', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'items' => [],
                'pageInfo' => ['totalResults' => 0],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->listVideos(
            accessToken: 'test_token',
            channelId: 'channel123',
            options: [
                'max_results' => 50,
                'page_token' => 'page_token_123',
                'order' => 'viewCount',
            ]
        );

        expect($result['success'])->toBeTrue();

        $uri = (string) $requestHistory[0]['request']->getUri();
        expect($uri)->toContain('maxResults=50');
        expect($uri)->toContain('pageToken=page_token_123');
        expect($uri)->toContain('order=viewCount');
    });
});

describe('YouTubeClient::createPlaylist', function () {
    beforeEach(function () {
        RateLimiter::clear('youtube_api_rate_limit:playlist');
    });

    it('creates playlist successfully', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'id' => 'playlist123',
                'snippet' => ['title' => 'My Playlist'],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->createPlaylist(
            accessToken: 'test_token',
            title: 'My Playlist',
            options: [
                'description' => 'Playlist description',
                'privacy' => 'private',
            ]
        );

        expect($result['success'])->toBeTrue();
        expect($result['playlist_id'])->toBe('playlist123');

        $body = json_decode((string) $requestHistory[0]['request']->getBody(), true);
        expect($body['snippet']['title'])->toBe('My Playlist');
        expect($body['snippet']['description'])->toBe('Playlist description');
        expect($body['status']['privacyStatus'])->toBe('private');
    });

    it('handles playlist creation errors', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('POST', '/playlists'),
                new Response(400, [], json_encode([
                    'error' => ['message' => 'Invalid playlist data'],
                ]))
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->createPlaylist(
            accessToken: 'test_token',
            title: ''
        );

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toBe('Invalid playlist data');
    });
});

describe('YouTubeClient::addVideoToPlaylist', function () {
    beforeEach(function () {
        RateLimiter::clear('youtube_api_rate_limit:playlist123');
    });

    it('adds video to playlist successfully', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'id' => 'playlistItem123',
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->addVideoToPlaylist(
            accessToken: 'test_token',
            playlistId: 'playlist123',
            videoId: 'video456'
        );

        expect($result['success'])->toBeTrue();

        $body = json_decode((string) $requestHistory[0]['request']->getBody(), true);
        expect($body['snippet']['playlistId'])->toBe('playlist123');
        expect($body['snippet']['resourceId']['videoId'])->toBe('video456');
        expect($body['snippet']['resourceId']['kind'])->toBe('youtube#video');
    });

    it('handles add video errors', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('POST', '/playlistItems'),
                new Response(404, [], json_encode([
                    'error' => ['message' => 'Playlist not found'],
                ]))
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->addVideoToPlaylist(
            accessToken: 'test_token',
            playlistId: 'nonexistent',
            videoId: 'video456'
        );

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toBe('Playlist not found');
    });
});

describe('YouTubeClient::listPlaylists', function () {
    beforeEach(function () {
        RateLimiter::clear('youtube_api_rate_limit:channel123');
    });

    it('lists playlists successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'items' => [
                    ['id' => 'playlist1', 'snippet' => ['title' => 'Playlist 1']],
                    ['id' => 'playlist2', 'snippet' => ['title' => 'Playlist 2']],
                ],
                'nextPageToken' => 'next_token',
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->listPlaylists(
            accessToken: 'test_token',
            channelId: 'channel123'
        );

        expect($result['success'])->toBeTrue();
        expect($result['playlists'])->toHaveCount(2);
        expect($result['next_page_token'])->toBe('next_token');
    });
});

describe('YouTubeClient::getVideoAnalytics', function () {
    beforeEach(function () {
        RateLimiter::clear('youtube_api_rate_limit:video123');
    });

    it('fetches video analytics successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'items' => [
                    [
                        'statistics' => [
                            'viewCount' => '10000',
                            'likeCount' => '500',
                            'dislikeCount' => '10',
                            'commentCount' => '100',
                            'favoriteCount' => '50',
                        ],
                    ],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->getVideoAnalytics(
            accessToken: 'test_token',
            videoId: 'video123'
        );

        expect($result['success'])->toBeTrue();
        expect($result['analytics']['views'])->toBe(10000);
        expect($result['analytics']['likes'])->toBe(500);
        expect($result['analytics']['dislikes'])->toBe(10);
        expect($result['analytics']['comments'])->toBe(100);
        expect($result['analytics']['favorites'])->toBe(50);
    });

    it('handles missing statistics gracefully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'items' => [
                    ['statistics' => []],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->getVideoAnalytics(
            accessToken: 'test_token',
            videoId: 'video123'
        );

        expect($result['success'])->toBeTrue();
        expect($result['analytics']['views'])->toBe(0);
        expect($result['analytics']['likes'])->toBe(0);
    });
});

describe('YouTubeClient::getChannelAnalytics', function () {
    beforeEach(function () {
        RateLimiter::clear('youtube_api_rate_limit:channel123');
    });

    it('fetches channel analytics successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'items' => [
                    [
                        'statistics' => [
                            'subscriberCount' => '50000',
                            'viewCount' => '1000000',
                            'videoCount' => '250',
                        ],
                    ],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->getChannelAnalytics(
            accessToken: 'test_token',
            channelId: 'channel123'
        );

        expect($result['success'])->toBeTrue();
        expect($result['analytics']['subscribers'])->toBe(50000);
        expect($result['analytics']['total_views'])->toBe(1000000);
        expect($result['analytics']['total_videos'])->toBe(250);
    });

    it('handles analytics fetch errors', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('GET', '/channels'),
                new Response(403, [], json_encode([
                    'error' => ['message' => 'Access denied'],
                ]))
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->getChannelAnalytics(
            accessToken: 'test_token',
            channelId: 'channel123'
        );

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toBe('Access denied');
    });
});

describe('YouTubeClient::getChannel', function () {
    beforeEach(function () {
        RateLimiter::clear('youtube_api_rate_limit:channel');
    });

    it('fetches channel information successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'items' => [
                    [
                        'id' => 'channel123',
                        'snippet' => [
                            'title' => 'My Channel',
                            'description' => 'Channel description',
                        ],
                        'statistics' => [
                            'subscriberCount' => '10000',
                        ],
                    ],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->getChannel(accessToken: 'test_token');

        expect($result['success'])->toBeTrue();
        expect($result['channel']['id'])->toBe('channel123');
        expect($result['channel']['snippet']['title'])->toBe('My Channel');
    });

    it('handles channel not found', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['items' => []])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $youtubeClient = new YouTubeClient($client);

        $result = $youtubeClient->getChannel(accessToken: 'test_token');

        expect($result['success'])->toBeTrue();
        expect($result['channel'])->toBeNull();
    });
});
