<?php

declare(strict_types=1);

namespace App\Enums\Audit;

/**
 * AuditableType Enum
 *
 * Defines the type of resource being audited.
 *
 * - USER: User account
 * - TENANT: Tenant/organization
 * - WORKSPACE: Workspace
 * - SOCIAL_ACCOUNT: Social media account
 * - POST: Social media post
 * - SUBSCRIPTION: Subscription plan
 * - INVOICE: Invoice
 * - SUPPORT_TICKET: Support ticket
 * - API_KEY: API key
 * - SETTINGS: Settings
 * - OTHER: Other resource type
 */
enum AuditableType: string
{
    case USER = 'user';
    case TENANT = 'tenant';
    case WORKSPACE = 'workspace';
    case SOCIAL_ACCOUNT = 'social_account';
    case POST = 'post';
    case SUBSCRIPTION = 'subscription';
    case INVOICE = 'invoice';
    case SUPPORT_TICKET = 'support_ticket';
    case TEAM = 'team';
    case API_KEY = 'api_key';
    case SETTINGS = 'settings';
    case PLATFORM_INTEGRATION = 'platform_integration';
    case OTHER = 'other';

    /**
     * Get human-readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::USER => 'User',
            self::TENANT => 'Tenant',
            self::WORKSPACE => 'Workspace',
            self::SOCIAL_ACCOUNT => 'Social Account',
            self::POST => 'Post',
            self::SUBSCRIPTION => 'Subscription',
            self::INVOICE => 'Invoice',
            self::SUPPORT_TICKET => 'Support Ticket',
            self::TEAM => 'Team',
            self::API_KEY => 'API Key',
            self::SETTINGS => 'Settings',
            self::PLATFORM_INTEGRATION => 'Platform Integration',
            self::OTHER => 'Other',
        };
    }

    /**
     * Get the model class for this auditable type.
     */
    public function modelClass(): ?string
    {
        return match ($this) {
            self::USER => \App\Models\User::class,
            self::TENANT => \App\Models\Tenant\Tenant::class,
            self::WORKSPACE => \App\Models\Workspace\Workspace::class,
            self::SOCIAL_ACCOUNT => \App\Models\Social\SocialAccount::class,
            self::POST => \App\Models\Content\Post::class,
            self::SUBSCRIPTION => \App\Models\Billing\Subscription::class,
            self::INVOICE => \App\Models\Billing\Invoice::class,
            self::SUPPORT_TICKET => \App\Models\Support\SupportTicket::class,
            self::TEAM => \App\Models\Workspace\Team::class,
            self::API_KEY => null,
            self::SETTINGS => null,
            self::PLATFORM_INTEGRATION => \App\Models\Platform\SocialPlatformIntegration::class,
            self::OTHER => null,
        };
    }

    /**
     * Get all values as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
