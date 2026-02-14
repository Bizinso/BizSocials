<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Enums\Social\SocialPlatform;
use App\Services\Social\Adapters\FacebookAdapter;
use App\Services\Social\Adapters\InstagramAdapter;
use App\Services\Social\Adapters\LinkedInAdapter;
use App\Services\Social\Adapters\TwitterAdapter;
use App\Services\Social\Adapters\WhatsAppAdapter;
use App\Services\Social\Adapters\YouTubeAdapter;
use App\Services\Social\Contracts\SocialPlatformAdapter;
use GuzzleHttp\Client;

class SocialPlatformAdapterFactory
{
    public function __construct(
        private readonly PlatformCredentialResolver $credentialResolver,
    ) {}

    public function create(SocialPlatform $platform): SocialPlatformAdapter
    {
        $client = new Client(['timeout' => 30]);

        return match ($platform) {
            SocialPlatform::LINKEDIN => new LinkedInAdapter($client),
            SocialPlatform::FACEBOOK => new FacebookAdapter($client, $this->credentialResolver->resolve($platform)),
            SocialPlatform::INSTAGRAM => new InstagramAdapter($client, $this->credentialResolver->resolve($platform)),
            SocialPlatform::TWITTER => new TwitterAdapter($client),
            SocialPlatform::YOUTUBE => new YouTubeAdapter($client),
            SocialPlatform::WHATSAPP => new WhatsAppAdapter($client),
        };
    }
}
