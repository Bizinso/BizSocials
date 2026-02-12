<?php

declare(strict_types=1);

namespace App\Enums\Social;

/**
 * SocialPlatform Enum
 *
 * Defines the supported social media platforms.
 *
 * - LINKEDIN: Professional networking platform
 * - FACEBOOK: General social networking
 * - INSTAGRAM: Photo and video sharing
 * - TWITTER: Microblogging platform
 * - WHATSAPP: Conversational messaging platform
 */
enum SocialPlatform: string
{
    case LINKEDIN = 'linkedin';
    case FACEBOOK = 'facebook';
    case INSTAGRAM = 'instagram';
    case TWITTER = 'twitter';
    case WHATSAPP = 'whatsapp';

    /**
     * Get human-readable label for the platform.
     */
    public function label(): string
    {
        return match ($this) {
            self::LINKEDIN => 'LinkedIn',
            self::FACEBOOK => 'Facebook',
            self::INSTAGRAM => 'Instagram',
            self::TWITTER => 'Twitter',
            self::WHATSAPP => 'WhatsApp',
        };
    }

    /**
     * Get the icon name for UI display.
     */
    public function icon(): string
    {
        return match ($this) {
            self::LINKEDIN => 'linkedin',
            self::FACEBOOK => 'facebook',
            self::INSTAGRAM => 'instagram',
            self::TWITTER => 'twitter',
            self::WHATSAPP => 'whatsapp',
        };
    }

    /**
     * Get the brand color hex code.
     */
    public function color(): string
    {
        return match ($this) {
            self::LINKEDIN => '#0A66C2',
            self::FACEBOOK => '#1877F2',
            self::INSTAGRAM => '#E4405F',
            self::TWITTER => '#1DA1F2',
            self::WHATSAPP => '#25D366',
        };
    }

    /**
     * Check if the platform supports scheduling posts.
     */
    public function supportsScheduling(): bool
    {
        return match ($this) {
            self::LINKEDIN, self::FACEBOOK, self::INSTAGRAM, self::TWITTER => true,
            self::WHATSAPP => false,
        };
    }

    /**
     * Check if the platform supports image attachments.
     */
    public function supportsImages(): bool
    {
        return true;
    }

    /**
     * Check if the platform supports video attachments.
     */
    public function supportsVideos(): bool
    {
        return true;
    }

    /**
     * Check if the platform supports carousel posts.
     */
    public function supportsCarousel(): bool
    {
        return match ($this) {
            self::LINKEDIN, self::INSTAGRAM => true,
            self::FACEBOOK, self::TWITTER, self::WHATSAPP => false,
        };
    }

    /**
     * Get the maximum post length (character limit) for the platform.
     */
    public function maxPostLength(): int
    {
        return match ($this) {
            self::LINKEDIN => 3000,
            self::FACEBOOK => 63206,
            self::INSTAGRAM => 2200,
            self::TWITTER => 280,
            self::WHATSAPP => 4096,
        };
    }

    /**
     * Get the required OAuth scopes for the platform.
     *
     * @return array<string>
     */
    public function oauthScopes(): array
    {
        return match ($this) {
            self::LINKEDIN => [
                'r_liteprofile',
                'r_emailaddress',
                'w_member_social',
                'r_organization_social',
                'w_organization_social',
            ],
            self::FACEBOOK => [
                'pages_manage_posts',
                'pages_read_engagement',
                'pages_show_list',
                'public_profile',
            ],
            self::INSTAGRAM => [
                'instagram_basic',
                'instagram_content_publish',
                'pages_show_list',
            ],
            self::TWITTER => [
                'tweet.read',
                'tweet.write',
                'users.read',
                'offline.access',
            ],
            self::WHATSAPP => [
                'business_management',
                'whatsapp_business_management',
                'whatsapp_business_messaging',
            ],
        };
    }

    /**
     * Check if the platform is conversational (messaging-based rather than feed-based).
     */
    public function isConversational(): bool
    {
        return match ($this) {
            self::WHATSAPP => true,
            self::LINKEDIN, self::FACEBOOK, self::INSTAGRAM, self::TWITTER => false,
        };
    }

    /**
     * Get all platforms as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
