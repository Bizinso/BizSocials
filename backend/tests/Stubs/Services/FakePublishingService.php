<?php

declare(strict_types=1);

namespace Tests\Stubs\Services;

use App\Enums\Content\PostStatus;
use App\Models\Content\Post;

/**
 * Fake PublishingService for testing.
 *
 * This stub allows testing jobs without mocking final classes.
 */
class FakePublishingService
{
    public bool $shouldFail = false;

    public string $failureMessage = 'Publishing failed';

    public bool $publishNowCalled = false;

    public bool $updateStatusCalled = false;

    public ?Post $lastPublishedPost = null;

    public PostStatus $resultStatus = PostStatus::PUBLISHED;

    public function publishNow(Post $post): void
    {
        $this->publishNowCalled = true;
        $this->lastPublishedPost = $post;

        if ($this->shouldFail) {
            throw new \RuntimeException($this->failureMessage);
        }
    }

    public function updatePostStatusFromTargets(Post $post): void
    {
        $this->updateStatusCalled = true;
        $post->status = $this->resultStatus;
        $post->published_at = now();
        $post->save();
    }

    /**
     * Configure the fake to simulate success.
     */
    public function shouldSucceed(): self
    {
        $this->shouldFail = false;
        $this->resultStatus = PostStatus::PUBLISHED;

        return $this;
    }

    /**
     * Configure the fake to simulate failure.
     */
    public function shouldFailWith(string $message = 'Publishing failed'): self
    {
        $this->shouldFail = true;
        $this->failureMessage = $message;
        $this->resultStatus = PostStatus::FAILED;

        return $this;
    }

    /**
     * Assert publishNow was called.
     */
    public function assertPublishNowCalled(): self
    {
        expect($this->publishNowCalled)->toBeTrue('Expected publishNow to be called');

        return $this;
    }

    /**
     * Assert publishNow was not called.
     */
    public function assertPublishNowNotCalled(): self
    {
        expect($this->publishNowCalled)->toBeFalse('Expected publishNow not to be called');

        return $this;
    }

    /**
     * Assert publishNow was called with specific post.
     */
    public function assertPublishedPost(Post $post): self
    {
        expect($this->lastPublishedPost?->id)->toBe($post->id, 'Expected post to be published');

        return $this;
    }
}
