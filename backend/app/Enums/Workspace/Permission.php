<?php

declare(strict_types=1);

namespace App\Enums\Workspace;

/**
 * Permission Enum
 *
 * Defines all workspace-scoped permissions for the BizSocials RBAC system.
 * Naming convention: {domain}.{action}
 *
 * 64 permissions across 8 domains:
 * - workspace (10): settings, members, teams, social accounts, audit, delete
 * - content (15): posts CRUD, approval, publishing, calendar, media, categories
 * - inbox (11): items, contacts, automation, saved replies
 * - whatsapp (11): conversations, templates, campaigns, contacts, automation, setup
 * - analytics (6): dashboard, reports, demographics, hashtags
 * - billing (4): subscription, invoices, payment
 * - settings (6): security, webhooks, API keys
 * - ai (1): assist
 */
enum Permission: string
{
    // ── Workspace Domain (10) ──────────────────────────────────────────
    case WORKSPACE_SETTINGS_VIEW = 'workspace.settings.view';
    case WORKSPACE_SETTINGS_UPDATE = 'workspace.settings.update';
    case WORKSPACE_MEMBERS_VIEW = 'workspace.members.view';
    case WORKSPACE_MEMBERS_MANAGE = 'workspace.members.manage';
    case WORKSPACE_TEAMS_VIEW = 'workspace.teams.view';
    case WORKSPACE_TEAMS_MANAGE = 'workspace.teams.manage';
    case WORKSPACE_SOCIAL_ACCOUNTS_VIEW = 'workspace.social_accounts.view';
    case WORKSPACE_SOCIAL_ACCOUNTS_MANAGE = 'workspace.social_accounts.manage';
    case WORKSPACE_AUDIT_VIEW = 'workspace.audit.view';
    case WORKSPACE_DELETE = 'workspace.delete';

    // ── Content Domain (15) ────────────────────────────────────────────
    case CONTENT_POSTS_VIEW = 'content.posts.view';
    case CONTENT_POSTS_CREATE = 'content.posts.create';
    case CONTENT_POSTS_EDIT_ANY = 'content.posts.edit_any';
    case CONTENT_POSTS_DELETE = 'content.posts.delete';
    case CONTENT_POSTS_SUBMIT = 'content.posts.submit';
    case CONTENT_POSTS_APPROVE = 'content.posts.approve';
    case CONTENT_POSTS_PUBLISH = 'content.posts.publish';
    case CONTENT_POSTS_SCHEDULE = 'content.posts.schedule';
    case CONTENT_CALENDAR_VIEW = 'content.calendar.view';
    case CONTENT_CALENDAR_MANAGE = 'content.calendar.manage';
    case CONTENT_MEDIA_VIEW = 'content.media.view';
    case CONTENT_MEDIA_UPLOAD = 'content.media.upload';
    case CONTENT_MEDIA_DELETE = 'content.media.delete';
    case CONTENT_CATEGORIES_VIEW = 'content.categories.view';
    case CONTENT_CATEGORIES_MANAGE = 'content.categories.manage';

    // ── Inbox Domain (11) ──────────────────────────────────────────────
    case INBOX_ITEMS_VIEW = 'inbox.items.view';
    case INBOX_ITEMS_REPLY = 'inbox.items.reply';
    case INBOX_ITEMS_ASSIGN = 'inbox.items.assign';
    case INBOX_ITEMS_RESOLVE = 'inbox.items.resolve';
    case INBOX_ITEMS_ARCHIVE = 'inbox.items.archive';
    case INBOX_CONTACTS_VIEW = 'inbox.contacts.view';
    case INBOX_CONTACTS_MANAGE = 'inbox.contacts.manage';
    case INBOX_AUTOMATION_VIEW = 'inbox.automation.view';
    case INBOX_AUTOMATION_MANAGE = 'inbox.automation.manage';
    case INBOX_SAVED_REPLIES_VIEW = 'inbox.saved_replies.view';
    case INBOX_SAVED_REPLIES_MANAGE = 'inbox.saved_replies.manage';

    // ── WhatsApp Domain (11) ───────────────────────────────────────────
    case WHATSAPP_CONVERSATIONS_VIEW = 'whatsapp.conversations.view';
    case WHATSAPP_CONVERSATIONS_REPLY = 'whatsapp.conversations.reply';
    case WHATSAPP_TEMPLATES_VIEW = 'whatsapp.templates.view';
    case WHATSAPP_TEMPLATES_MANAGE = 'whatsapp.templates.manage';
    case WHATSAPP_CAMPAIGNS_VIEW = 'whatsapp.campaigns.view';
    case WHATSAPP_CAMPAIGNS_MANAGE = 'whatsapp.campaigns.manage';
    case WHATSAPP_CONTACTS_VIEW = 'whatsapp.contacts.view';
    case WHATSAPP_CONTACTS_MANAGE = 'whatsapp.contacts.manage';
    case WHATSAPP_AUTOMATION_VIEW = 'whatsapp.automation.view';
    case WHATSAPP_AUTOMATION_MANAGE = 'whatsapp.automation.manage';
    case WHATSAPP_SETUP_MANAGE = 'whatsapp.setup.manage';

    // ── Analytics Domain (6) ───────────────────────────────────────────
    case ANALYTICS_DASHBOARD_VIEW = 'analytics.dashboard.view';
    case ANALYTICS_REPORTS_VIEW = 'analytics.reports.view';
    case ANALYTICS_REPORTS_CREATE = 'analytics.reports.create';
    case ANALYTICS_REPORTS_EXPORT = 'analytics.reports.export';
    case ANALYTICS_DEMOGRAPHICS_VIEW = 'analytics.demographics.view';
    case ANALYTICS_HASHTAGS_VIEW = 'analytics.hashtags.view';

    // ── Billing Domain (4) ─────────────────────────────────────────────
    case BILLING_SUBSCRIPTION_VIEW = 'billing.subscription.view';
    case BILLING_SUBSCRIPTION_MANAGE = 'billing.subscription.manage';
    case BILLING_INVOICES_VIEW = 'billing.invoices.view';
    case BILLING_PAYMENT_MANAGE = 'billing.payment.manage';

    // ── Settings Domain (6) ────────────────────────────────────────────
    case SETTINGS_SECURITY_VIEW = 'settings.security.view';
    case SETTINGS_SECURITY_MANAGE = 'settings.security.manage';
    case SETTINGS_WEBHOOKS_VIEW = 'settings.webhooks.view';
    case SETTINGS_WEBHOOKS_MANAGE = 'settings.webhooks.manage';
    case SETTINGS_API_KEYS_VIEW = 'settings.api_keys.view';
    case SETTINGS_API_KEYS_MANAGE = 'settings.api_keys.manage';

    // ── AI Domain (1) ──────────────────────────────────────────────────
    case AI_ASSIST_USE = 'ai.assist.use';

    /**
     * Get the domain portion of the permission (e.g. "workspace", "content").
     */
    public function domain(): string
    {
        return explode('.', $this->value)[0];
    }

    /**
     * Get the action portion of the permission (everything after the domain).
     * e.g. "settings.view", "posts.create"
     */
    public function action(): string
    {
        return implode('.', array_slice(explode('.', $this->value), 1));
    }

    /**
     * Get a human-readable description of the permission.
     */
    public function description(): string
    {
        return match ($this) {
            // Workspace
            self::WORKSPACE_SETTINGS_VIEW => 'View workspace settings',
            self::WORKSPACE_SETTINGS_UPDATE => 'Update workspace settings',
            self::WORKSPACE_MEMBERS_VIEW => 'View member list',
            self::WORKSPACE_MEMBERS_MANAGE => 'Add/remove members, change roles',
            self::WORKSPACE_TEAMS_VIEW => 'View teams and team members',
            self::WORKSPACE_TEAMS_MANAGE => 'Create/update/delete teams, manage team members',
            self::WORKSPACE_SOCIAL_ACCOUNTS_VIEW => 'View connected social accounts',
            self::WORKSPACE_SOCIAL_ACCOUNTS_MANAGE => 'Connect/disconnect social accounts',
            self::WORKSPACE_AUDIT_VIEW => 'View audit log',
            self::WORKSPACE_DELETE => 'Delete workspace',

            // Content
            self::CONTENT_POSTS_VIEW => 'View posts',
            self::CONTENT_POSTS_CREATE => 'Create and edit own posts',
            self::CONTENT_POSTS_EDIT_ANY => 'Edit any post (not just own)',
            self::CONTENT_POSTS_DELETE => 'Delete posts',
            self::CONTENT_POSTS_SUBMIT => 'Submit post for approval',
            self::CONTENT_POSTS_APPROVE => 'Approve/reject submitted posts',
            self::CONTENT_POSTS_PUBLISH => 'Publish directly (bypass approval)',
            self::CONTENT_POSTS_SCHEDULE => 'Schedule posts for future publish',
            self::CONTENT_CALENDAR_VIEW => 'View content calendar',
            self::CONTENT_CALENDAR_MANAGE => 'Reschedule/cancel scheduled posts',
            self::CONTENT_MEDIA_VIEW => 'View media library',
            self::CONTENT_MEDIA_UPLOAD => 'Upload media',
            self::CONTENT_MEDIA_DELETE => 'Delete media',
            self::CONTENT_CATEGORIES_VIEW => 'View categories and hashtag groups',
            self::CONTENT_CATEGORIES_MANAGE => 'Create/update/delete categories',

            // Inbox
            self::INBOX_ITEMS_VIEW => 'View inbox items',
            self::INBOX_ITEMS_REPLY => 'Reply to inbox items',
            self::INBOX_ITEMS_ASSIGN => 'Assign items to users',
            self::INBOX_ITEMS_RESOLVE => 'Mark items resolved/unresolve',
            self::INBOX_ITEMS_ARCHIVE => 'Archive items',
            self::INBOX_CONTACTS_VIEW => 'View inbox contacts',
            self::INBOX_CONTACTS_MANAGE => 'Edit contact details',
            self::INBOX_AUTOMATION_VIEW => 'View automation rules',
            self::INBOX_AUTOMATION_MANAGE => 'Create/edit/delete automation rules',
            self::INBOX_SAVED_REPLIES_VIEW => 'View saved replies',
            self::INBOX_SAVED_REPLIES_MANAGE => 'Create/edit/delete saved replies',

            // WhatsApp
            self::WHATSAPP_CONVERSATIONS_VIEW => 'View conversations',
            self::WHATSAPP_CONVERSATIONS_REPLY => 'Reply to conversations',
            self::WHATSAPP_TEMPLATES_VIEW => 'View message templates',
            self::WHATSAPP_TEMPLATES_MANAGE => 'Create/edit/delete templates',
            self::WHATSAPP_CAMPAIGNS_VIEW => 'View campaigns',
            self::WHATSAPP_CAMPAIGNS_MANAGE => 'Create/edit/send campaigns',
            self::WHATSAPP_CONTACTS_VIEW => 'View contacts',
            self::WHATSAPP_CONTACTS_MANAGE => 'Import/export/edit contacts',
            self::WHATSAPP_AUTOMATION_VIEW => 'View automation/quick replies',
            self::WHATSAPP_AUTOMATION_MANAGE => 'Manage automation rules',
            self::WHATSAPP_SETUP_MANAGE => 'Configure WhatsApp account',

            // Analytics
            self::ANALYTICS_DASHBOARD_VIEW => 'View analytics dashboard',
            self::ANALYTICS_REPORTS_VIEW => 'View reports',
            self::ANALYTICS_REPORTS_CREATE => 'Create custom/scheduled reports',
            self::ANALYTICS_REPORTS_EXPORT => 'Export reports',
            self::ANALYTICS_DEMOGRAPHICS_VIEW => 'View audience demographics',
            self::ANALYTICS_HASHTAGS_VIEW => 'View hashtag tracking',

            // Billing
            self::BILLING_SUBSCRIPTION_VIEW => 'View subscription details',
            self::BILLING_SUBSCRIPTION_MANAGE => 'Change plan, cancel, reactivate',
            self::BILLING_INVOICES_VIEW => 'View invoices',
            self::BILLING_PAYMENT_MANAGE => 'Update payment method',

            // Settings
            self::SETTINGS_SECURITY_VIEW => 'View security settings',
            self::SETTINGS_SECURITY_MANAGE => 'Update security settings',
            self::SETTINGS_WEBHOOKS_VIEW => 'View webhooks',
            self::SETTINGS_WEBHOOKS_MANAGE => 'Create/edit/delete webhooks',
            self::SETTINGS_API_KEYS_VIEW => 'View API keys',
            self::SETTINGS_API_KEYS_MANAGE => 'Create/revoke API keys',

            // AI
            self::AI_ASSIST_USE => 'Use AI assist features',
        };
    }

    /**
     * Try to resolve a Permission from a string value.
     * Returns null for unknown permission strings (default-deny).
     */
    public static function fromString(string $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * Get all permissions for a given domain.
     *
     * @return array<self>
     */
    public static function forDomain(string $domain): array
    {
        return array_filter(self::cases(), fn (self $p) => $p->domain() === $domain);
    }

    /**
     * Get all permission values as strings.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all unique domain names.
     *
     * @return array<string>
     */
    public static function domains(): array
    {
        return array_values(array_unique(array_map(fn (self $p) => $p->domain(), self::cases())));
    }
}
