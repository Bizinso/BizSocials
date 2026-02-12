<?php

declare(strict_types=1);

namespace App\Enums\Workspace;

/**
 * WorkspaceRole Enum
 *
 * Defines the role of a user within a workspace.
 *
 * - OWNER: Full access, billing control, can delete workspace
 * - ADMIN: Team + content management, no billing or workspace deletion
 * - EDITOR: Content creation, no approvals or publishing
 * - VIEWER: Read-only access
 */
enum WorkspaceRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case EDITOR = 'editor';
    case VIEWER = 'viewer';

    /**
     * Get human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Owner',
            self::ADMIN => 'Admin',
            self::EDITOR => 'Editor',
            self::VIEWER => 'Viewer',
        };
    }

    /**
     * Check if the role can manage workspace settings.
     *
     * @deprecated Use hasPermission(Permission::WORKSPACE_SETTINGS_UPDATE) instead.
     */
    public function canManageWorkspace(): bool
    {
        return $this->hasPermission(Permission::WORKSPACE_SETTINGS_UPDATE);
    }

    /**
     * Check if the role can manage billing.
     *
     * @deprecated Use hasPermission(Permission::BILLING_SUBSCRIPTION_MANAGE) instead.
     */
    public function canManageBilling(): bool
    {
        return $this->hasPermission(Permission::BILLING_SUBSCRIPTION_MANAGE);
    }

    /**
     * Check if the role can manage workspace members.
     *
     * @deprecated Use hasPermission(Permission::WORKSPACE_MEMBERS_MANAGE) instead.
     */
    public function canManageMembers(): bool
    {
        return $this->hasPermission(Permission::WORKSPACE_MEMBERS_MANAGE);
    }

    /**
     * Check if the role can manage social accounts.
     *
     * @deprecated Use hasPermission(Permission::WORKSPACE_SOCIAL_ACCOUNTS_MANAGE) instead.
     */
    public function canManageSocialAccounts(): bool
    {
        return $this->hasPermission(Permission::WORKSPACE_SOCIAL_ACCOUNTS_MANAGE);
    }

    /**
     * Check if the role can create content (posts, drafts, etc.).
     *
     * @deprecated Use hasPermission(Permission::CONTENT_POSTS_CREATE) instead.
     */
    public function canCreateContent(): bool
    {
        return $this->hasPermission(Permission::CONTENT_POSTS_CREATE);
    }

    /**
     * Check if the role can approve content for publishing.
     *
     * @deprecated Use hasPermission(Permission::CONTENT_POSTS_APPROVE) instead.
     */
    public function canApproveContent(): bool
    {
        return $this->hasPermission(Permission::CONTENT_POSTS_APPROVE);
    }

    /**
     * Check if the role can publish content directly without approval.
     *
     * @deprecated Use hasPermission(Permission::CONTENT_POSTS_PUBLISH) instead.
     */
    public function canPublishDirectly(): bool
    {
        return $this->hasPermission(Permission::CONTENT_POSTS_PUBLISH);
    }

    /**
     * Check if the role can delete the workspace.
     *
     * @deprecated Use hasPermission(Permission::WORKSPACE_DELETE) instead.
     */
    public function canDeleteWorkspace(): bool
    {
        return $this->hasPermission(Permission::WORKSPACE_DELETE);
    }

    /**
     * Check if this role is at least as powerful as the given role.
     *
     * Hierarchy: OWNER > ADMIN > EDITOR > VIEWER
     */
    public function isAtLeast(WorkspaceRole $role): bool
    {
        return $this->hierarchy() >= $role->hierarchy();
    }

    /**
     * Get the hierarchy level of this role.
     *
     * Higher number = more permissions.
     * OWNER=4, ADMIN=3, EDITOR=2, VIEWER=1
     */
    public function hierarchy(): int
    {
        return match ($this) {
            self::VIEWER => 1,
            self::EDITOR => 2,
            self::ADMIN => 3,
            self::OWNER => 4,
        };
    }

    /**
     * Get the permissions granted to this role.
     *
     * OWNER: all 64 permissions
     * ADMIN: all except workspace.delete, billing.*.manage, settings.api_keys.manage (60)
     * EDITOR: content creation, inbox reply, analytics view, AI assist (30)
     * VIEWER: read-only across domains (18)
     *
     * @return array<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::OWNER => Permission::cases(),
            self::ADMIN => self::adminPermissions(),
            self::EDITOR => self::editorPermissions(),
            self::VIEWER => self::viewerPermissions(),
        };
    }

    /**
     * Check if this role grants the given permission.
     * Accepts a Permission enum or a string. Unknown strings are denied.
     */
    public function hasPermission(Permission|string $permission): bool
    {
        if (is_string($permission)) {
            $resolved = Permission::fromString($permission);
            if ($resolved === null) {
                return false;
            }
            $permission = $resolved;
        }

        return in_array($permission, $this->permissions(), true);
    }

    /**
     * ADMIN permissions: all except OWNER-exclusive permissions.
     *
     * @return array<Permission>
     */
    private static function adminPermissions(): array
    {
        $excluded = [
            Permission::WORKSPACE_DELETE,
            Permission::BILLING_SUBSCRIPTION_MANAGE,
            Permission::BILLING_PAYMENT_MANAGE,
            Permission::SETTINGS_API_KEYS_MANAGE,
        ];

        return array_values(array_filter(
            Permission::cases(),
            fn (Permission $p) => ! in_array($p, $excluded, true),
        ));
    }

    /**
     * EDITOR permissions: content creation, inbox reply, analytics view, AI.
     *
     * @return array<Permission>
     */
    private static function editorPermissions(): array
    {
        return [
            // Workspace (view-only for members/teams/accounts)
            Permission::WORKSPACE_MEMBERS_VIEW,
            Permission::WORKSPACE_TEAMS_VIEW,
            Permission::WORKSPACE_SOCIAL_ACCOUNTS_VIEW,

            // Content (create, submit, schedule — no approve/publish/delete/edit_any)
            Permission::CONTENT_POSTS_VIEW,
            Permission::CONTENT_POSTS_CREATE,
            Permission::CONTENT_POSTS_SUBMIT,
            Permission::CONTENT_POSTS_SCHEDULE,
            Permission::CONTENT_CALENDAR_VIEW,
            Permission::CONTENT_CALENDAR_MANAGE,
            Permission::CONTENT_MEDIA_VIEW,
            Permission::CONTENT_MEDIA_UPLOAD,
            Permission::CONTENT_CATEGORIES_VIEW,

            // Inbox (view + reply, saved replies — no assign/resolve/archive)
            Permission::INBOX_ITEMS_VIEW,
            Permission::INBOX_ITEMS_REPLY,
            Permission::INBOX_CONTACTS_VIEW,
            Permission::INBOX_SAVED_REPLIES_VIEW,
            Permission::INBOX_SAVED_REPLIES_MANAGE,

            // WhatsApp (view + reply, contacts manage — no templates/campaigns/automation manage)
            Permission::WHATSAPP_CONVERSATIONS_VIEW,
            Permission::WHATSAPP_CONVERSATIONS_REPLY,
            Permission::WHATSAPP_TEMPLATES_VIEW,
            Permission::WHATSAPP_CAMPAIGNS_VIEW,
            Permission::WHATSAPP_CONTACTS_VIEW,
            Permission::WHATSAPP_CONTACTS_MANAGE,

            // Analytics (full view + create + export)
            Permission::ANALYTICS_DASHBOARD_VIEW,
            Permission::ANALYTICS_REPORTS_VIEW,
            Permission::ANALYTICS_REPORTS_CREATE,
            Permission::ANALYTICS_REPORTS_EXPORT,
            Permission::ANALYTICS_DEMOGRAPHICS_VIEW,
            Permission::ANALYTICS_HASHTAGS_VIEW,

            // AI
            Permission::AI_ASSIST_USE,
        ];
    }

    /**
     * VIEWER permissions: read-only across all domains.
     *
     * @return array<Permission>
     */
    private static function viewerPermissions(): array
    {
        return [
            // Workspace (view members/teams/accounts only)
            Permission::WORKSPACE_MEMBERS_VIEW,
            Permission::WORKSPACE_TEAMS_VIEW,
            Permission::WORKSPACE_SOCIAL_ACCOUNTS_VIEW,

            // Content (view posts, calendar, media, categories)
            Permission::CONTENT_POSTS_VIEW,
            Permission::CONTENT_CALENDAR_VIEW,
            Permission::CONTENT_MEDIA_VIEW,
            Permission::CONTENT_CATEGORIES_VIEW,

            // Inbox (view items, contacts, saved replies)
            Permission::INBOX_ITEMS_VIEW,
            Permission::INBOX_CONTACTS_VIEW,
            Permission::INBOX_SAVED_REPLIES_VIEW,

            // WhatsApp (view conversations, templates, campaigns, contacts)
            Permission::WHATSAPP_CONVERSATIONS_VIEW,
            Permission::WHATSAPP_TEMPLATES_VIEW,
            Permission::WHATSAPP_CAMPAIGNS_VIEW,
            Permission::WHATSAPP_CONTACTS_VIEW,

            // Analytics (view dashboard, reports, demographics, hashtags)
            Permission::ANALYTICS_DASHBOARD_VIEW,
            Permission::ANALYTICS_REPORTS_VIEW,
            Permission::ANALYTICS_DEMOGRAPHICS_VIEW,
            Permission::ANALYTICS_HASHTAGS_VIEW,
        ];
    }

    /**
     * Get all roles as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
