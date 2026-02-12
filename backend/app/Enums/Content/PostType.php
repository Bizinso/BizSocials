<?php

declare(strict_types=1);

namespace App\Enums\Content;

use App\Enums\Social\SocialPlatform;

/**
 * PostType Enum
 *
 * Defines the type/format of a social media post.
 *
 * - STANDARD: Regular text/image post
 * - REEL: Short-form video (Instagram Reels, TikTok-style)
 * - STORY: Ephemeral content (Instagram/Facebook Stories)
 * - THREAD: Multi-part post (Twitter threads)
 * - ARTICLE: Long-form content (LinkedIn articles)
 */
enum PostType: string
{
    case STANDARD = 'standard';
    case REEL = 'reel';
    case STORY = 'story';
    case THREAD = 'thread';
    case ARTICLE = 'article';

    /**
     * Get human-readable label for the post type.
     */
    public function label(): string
    {
        return match ($this) {
            self::STANDARD => 'Standard Post',
            self::REEL => 'Reel',
            self::STORY => 'Story',
            self::THREAD => 'Thread',
            self::ARTICLE => 'Article',
        };
    }

    /**
     * Get the platforms that support this post type.
     *
     * @return array<SocialPlatform>
     */
    public function supportedPlatforms(): array
    {
        return match ($this) {
            self::STANDARD => [
                SocialPlatform::LINKEDIN,
                SocialPlatform::FACEBOOK,
                SocialPlatform::INSTAGRAM,
                SocialPlatform::TWITTER,
            ],
            self::REEL => [
                SocialPlatform::INSTAGRAM,
                SocialPlatform::FACEBOOK,
            ],
            self::STORY => [
                SocialPlatform::INSTAGRAM,
                SocialPlatform::FACEBOOK,
            ],
            self::THREAD => [
                SocialPlatform::TWITTER,
            ],
            self::ARTICLE => [
                SocialPlatform::LINKEDIN,
            ],
        };
    }

    /**
     * Get all post types as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
