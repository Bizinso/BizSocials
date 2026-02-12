<?php

declare(strict_types=1);

namespace Tests\Stubs\Services;

use App\Enums\Social\SocialPlatform;
use App\Services\Social\Contracts\SocialPlatformAdapter;
use App\Services\Social\SocialPlatformAdapterFactory;

/**
 * Fake adapter factory that returns FakeSocialPlatformAdapter instances.
 *
 * Swap this into the container during tests to avoid real HTTP calls.
 */
class FakeSocialPlatformAdapterFactory extends SocialPlatformAdapterFactory
{
    private FakeSocialPlatformAdapter $adapter;

    public function __construct()
    {
        $this->adapter = new FakeSocialPlatformAdapter();
    }

    public function create(SocialPlatform $platform): SocialPlatformAdapter
    {
        return $this->adapter;
    }

    public function getAdapter(): FakeSocialPlatformAdapter
    {
        return $this->adapter;
    }
}
