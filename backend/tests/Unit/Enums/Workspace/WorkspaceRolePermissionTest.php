<?php

declare(strict_types=1);

use App\Enums\Workspace\Permission;
use App\Enums\Workspace\WorkspaceRole;

// ── Permission Count Tests ─────────────────────────────────────────────

describe('permission counts', function () {
    it('OWNER has all 64 permissions', function () {
        expect(WorkspaceRole::OWNER->permissions())->toHaveCount(64);
    });

    it('ADMIN has 60 permissions', function () {
        expect(WorkspaceRole::ADMIN->permissions())->toHaveCount(60);
    });

    it('EDITOR has 30 permissions', function () {
        expect(WorkspaceRole::EDITOR->permissions())->toHaveCount(30);
    });

    it('VIEWER has 18 permissions', function () {
        expect(WorkspaceRole::VIEWER->permissions())->toHaveCount(18);
    });
});

// ── OWNER: has everything ──────────────────────────────────────────────

describe('OWNER permissions', function () {
    it('has every single permission', function () {
        $role = WorkspaceRole::OWNER;
        foreach (Permission::cases() as $perm) {
            expect($role->hasPermission($perm))->toBeTrue(
                "OWNER should have {$perm->value}"
            );
        }
    });
});

// ── ADMIN: all except 4 OWNER-exclusive ────────────────────────────────

describe('ADMIN permissions', function () {
    it('has all workspace permissions except delete', function () {
        $role = WorkspaceRole::ADMIN;
        expect($role->hasPermission(Permission::WORKSPACE_SETTINGS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::WORKSPACE_SETTINGS_UPDATE))->toBeTrue()
            ->and($role->hasPermission(Permission::WORKSPACE_MEMBERS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::WORKSPACE_MEMBERS_MANAGE))->toBeTrue()
            ->and($role->hasPermission(Permission::WORKSPACE_TEAMS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::WORKSPACE_TEAMS_MANAGE))->toBeTrue()
            ->and($role->hasPermission(Permission::WORKSPACE_SOCIAL_ACCOUNTS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::WORKSPACE_SOCIAL_ACCOUNTS_MANAGE))->toBeTrue()
            ->and($role->hasPermission(Permission::WORKSPACE_AUDIT_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::WORKSPACE_DELETE))->toBeFalse();
    });

    it('has all content permissions', function () {
        $role = WorkspaceRole::ADMIN;
        foreach (Permission::forDomain('content') as $perm) {
            expect($role->hasPermission($perm))->toBeTrue(
                "ADMIN should have {$perm->value}"
            );
        }
    });

    it('has all inbox permissions', function () {
        $role = WorkspaceRole::ADMIN;
        foreach (Permission::forDomain('inbox') as $perm) {
            expect($role->hasPermission($perm))->toBeTrue(
                "ADMIN should have {$perm->value}"
            );
        }
    });

    it('has all whatsapp permissions', function () {
        $role = WorkspaceRole::ADMIN;
        foreach (Permission::forDomain('whatsapp') as $perm) {
            expect($role->hasPermission($perm))->toBeTrue(
                "ADMIN should have {$perm->value}"
            );
        }
    });

    it('has all analytics permissions', function () {
        $role = WorkspaceRole::ADMIN;
        foreach (Permission::forDomain('analytics') as $perm) {
            expect($role->hasPermission($perm))->toBeTrue(
                "ADMIN should have {$perm->value}"
            );
        }
    });

    it('does NOT have OWNER-exclusive billing permissions', function () {
        $role = WorkspaceRole::ADMIN;
        expect($role->hasPermission(Permission::BILLING_SUBSCRIPTION_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::BILLING_INVOICES_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::BILLING_SUBSCRIPTION_MANAGE))->toBeFalse()
            ->and($role->hasPermission(Permission::BILLING_PAYMENT_MANAGE))->toBeFalse();
    });

    it('does NOT have settings.api_keys.manage', function () {
        $role = WorkspaceRole::ADMIN;
        expect($role->hasPermission(Permission::SETTINGS_API_KEYS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::SETTINGS_API_KEYS_MANAGE))->toBeFalse();
    });

    it('has all other settings permissions', function () {
        $role = WorkspaceRole::ADMIN;
        expect($role->hasPermission(Permission::SETTINGS_SECURITY_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::SETTINGS_SECURITY_MANAGE))->toBeTrue()
            ->and($role->hasPermission(Permission::SETTINGS_WEBHOOKS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::SETTINGS_WEBHOOKS_MANAGE))->toBeTrue();
    });

    it('has AI assist', function () {
        expect(WorkspaceRole::ADMIN->hasPermission(Permission::AI_ASSIST_USE))->toBeTrue();
    });
});

// ── EDITOR: content creation, inbox reply, analytics view, AI ──────────

describe('EDITOR permissions', function () {
    it('can view but NOT manage workspace resources', function () {
        $role = WorkspaceRole::EDITOR;
        expect($role->hasPermission(Permission::WORKSPACE_MEMBERS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::WORKSPACE_MEMBERS_MANAGE))->toBeFalse()
            ->and($role->hasPermission(Permission::WORKSPACE_TEAMS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::WORKSPACE_TEAMS_MANAGE))->toBeFalse()
            ->and($role->hasPermission(Permission::WORKSPACE_SOCIAL_ACCOUNTS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::WORKSPACE_SOCIAL_ACCOUNTS_MANAGE))->toBeFalse()
            ->and($role->hasPermission(Permission::WORKSPACE_SETTINGS_VIEW))->toBeFalse()
            ->and($role->hasPermission(Permission::WORKSPACE_SETTINGS_UPDATE))->toBeFalse()
            ->and($role->hasPermission(Permission::WORKSPACE_AUDIT_VIEW))->toBeFalse()
            ->and($role->hasPermission(Permission::WORKSPACE_DELETE))->toBeFalse();
    });

    it('can create and submit content but NOT approve or publish', function () {
        $role = WorkspaceRole::EDITOR;
        expect($role->hasPermission(Permission::CONTENT_POSTS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::CONTENT_POSTS_CREATE))->toBeTrue()
            ->and($role->hasPermission(Permission::CONTENT_POSTS_SUBMIT))->toBeTrue()
            ->and($role->hasPermission(Permission::CONTENT_POSTS_SCHEDULE))->toBeTrue()
            ->and($role->hasPermission(Permission::CONTENT_POSTS_APPROVE))->toBeFalse()
            ->and($role->hasPermission(Permission::CONTENT_POSTS_PUBLISH))->toBeFalse()
            ->and($role->hasPermission(Permission::CONTENT_POSTS_EDIT_ANY))->toBeFalse()
            ->and($role->hasPermission(Permission::CONTENT_POSTS_DELETE))->toBeFalse();
    });

    it('can view and manage calendar', function () {
        $role = WorkspaceRole::EDITOR;
        expect($role->hasPermission(Permission::CONTENT_CALENDAR_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::CONTENT_CALENDAR_MANAGE))->toBeTrue();
    });

    it('can view and upload media but NOT delete', function () {
        $role = WorkspaceRole::EDITOR;
        expect($role->hasPermission(Permission::CONTENT_MEDIA_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::CONTENT_MEDIA_UPLOAD))->toBeTrue()
            ->and($role->hasPermission(Permission::CONTENT_MEDIA_DELETE))->toBeFalse();
    });

    it('can view categories but NOT manage', function () {
        $role = WorkspaceRole::EDITOR;
        expect($role->hasPermission(Permission::CONTENT_CATEGORIES_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::CONTENT_CATEGORIES_MANAGE))->toBeFalse();
    });

    it('can view and reply to inbox but NOT assign/resolve/archive', function () {
        $role = WorkspaceRole::EDITOR;
        expect($role->hasPermission(Permission::INBOX_ITEMS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::INBOX_ITEMS_REPLY))->toBeTrue()
            ->and($role->hasPermission(Permission::INBOX_ITEMS_ASSIGN))->toBeFalse()
            ->and($role->hasPermission(Permission::INBOX_ITEMS_RESOLVE))->toBeFalse()
            ->and($role->hasPermission(Permission::INBOX_ITEMS_ARCHIVE))->toBeFalse();
    });

    it('can manage saved replies', function () {
        $role = WorkspaceRole::EDITOR;
        expect($role->hasPermission(Permission::INBOX_SAVED_REPLIES_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::INBOX_SAVED_REPLIES_MANAGE))->toBeTrue();
    });

    it('can view and reply to WhatsApp but NOT manage templates/campaigns', function () {
        $role = WorkspaceRole::EDITOR;
        expect($role->hasPermission(Permission::WHATSAPP_CONVERSATIONS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::WHATSAPP_CONVERSATIONS_REPLY))->toBeTrue()
            ->and($role->hasPermission(Permission::WHATSAPP_CONTACTS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::WHATSAPP_CONTACTS_MANAGE))->toBeTrue()
            ->and($role->hasPermission(Permission::WHATSAPP_TEMPLATES_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::WHATSAPP_TEMPLATES_MANAGE))->toBeFalse()
            ->and($role->hasPermission(Permission::WHATSAPP_CAMPAIGNS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::WHATSAPP_CAMPAIGNS_MANAGE))->toBeFalse()
            ->and($role->hasPermission(Permission::WHATSAPP_AUTOMATION_VIEW))->toBeFalse()
            ->and($role->hasPermission(Permission::WHATSAPP_AUTOMATION_MANAGE))->toBeFalse()
            ->and($role->hasPermission(Permission::WHATSAPP_SETUP_MANAGE))->toBeFalse();
    });

    it('has all analytics permissions', function () {
        $role = WorkspaceRole::EDITOR;
        foreach (Permission::forDomain('analytics') as $perm) {
            expect($role->hasPermission($perm))->toBeTrue(
                "EDITOR should have {$perm->value}"
            );
        }
    });

    it('has NO billing permissions', function () {
        $role = WorkspaceRole::EDITOR;
        foreach (Permission::forDomain('billing') as $perm) {
            expect($role->hasPermission($perm))->toBeFalse(
                "EDITOR should NOT have {$perm->value}"
            );
        }
    });

    it('has NO settings permissions', function () {
        $role = WorkspaceRole::EDITOR;
        foreach (Permission::forDomain('settings') as $perm) {
            expect($role->hasPermission($perm))->toBeFalse(
                "EDITOR should NOT have {$perm->value}"
            );
        }
    });

    it('has AI assist', function () {
        expect(WorkspaceRole::EDITOR->hasPermission(Permission::AI_ASSIST_USE))->toBeTrue();
    });
});

// ── VIEWER: read-only ──────────────────────────────────────────────────

describe('VIEWER permissions', function () {
    it('can only view workspace resources (members, teams, accounts)', function () {
        $role = WorkspaceRole::VIEWER;
        expect($role->hasPermission(Permission::WORKSPACE_MEMBERS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::WORKSPACE_TEAMS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::WORKSPACE_SOCIAL_ACCOUNTS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::WORKSPACE_SETTINGS_VIEW))->toBeFalse()
            ->and($role->hasPermission(Permission::WORKSPACE_SETTINGS_UPDATE))->toBeFalse()
            ->and($role->hasPermission(Permission::WORKSPACE_MEMBERS_MANAGE))->toBeFalse()
            ->and($role->hasPermission(Permission::WORKSPACE_TEAMS_MANAGE))->toBeFalse()
            ->and($role->hasPermission(Permission::WORKSPACE_SOCIAL_ACCOUNTS_MANAGE))->toBeFalse()
            ->and($role->hasPermission(Permission::WORKSPACE_AUDIT_VIEW))->toBeFalse()
            ->and($role->hasPermission(Permission::WORKSPACE_DELETE))->toBeFalse();
    });

    it('can only view content (posts, calendar, media, categories)', function () {
        $role = WorkspaceRole::VIEWER;
        expect($role->hasPermission(Permission::CONTENT_POSTS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::CONTENT_CALENDAR_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::CONTENT_MEDIA_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::CONTENT_CATEGORIES_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::CONTENT_POSTS_CREATE))->toBeFalse()
            ->and($role->hasPermission(Permission::CONTENT_POSTS_SUBMIT))->toBeFalse()
            ->and($role->hasPermission(Permission::CONTENT_POSTS_APPROVE))->toBeFalse()
            ->and($role->hasPermission(Permission::CONTENT_POSTS_PUBLISH))->toBeFalse()
            ->and($role->hasPermission(Permission::CONTENT_POSTS_SCHEDULE))->toBeFalse()
            ->and($role->hasPermission(Permission::CONTENT_POSTS_EDIT_ANY))->toBeFalse()
            ->and($role->hasPermission(Permission::CONTENT_POSTS_DELETE))->toBeFalse()
            ->and($role->hasPermission(Permission::CONTENT_CALENDAR_MANAGE))->toBeFalse()
            ->and($role->hasPermission(Permission::CONTENT_MEDIA_UPLOAD))->toBeFalse()
            ->and($role->hasPermission(Permission::CONTENT_MEDIA_DELETE))->toBeFalse()
            ->and($role->hasPermission(Permission::CONTENT_CATEGORIES_MANAGE))->toBeFalse();
    });

    it('can only view inbox items and contacts', function () {
        $role = WorkspaceRole::VIEWER;
        expect($role->hasPermission(Permission::INBOX_ITEMS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::INBOX_CONTACTS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::INBOX_SAVED_REPLIES_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::INBOX_ITEMS_REPLY))->toBeFalse()
            ->and($role->hasPermission(Permission::INBOX_ITEMS_ASSIGN))->toBeFalse()
            ->and($role->hasPermission(Permission::INBOX_ITEMS_RESOLVE))->toBeFalse()
            ->and($role->hasPermission(Permission::INBOX_ITEMS_ARCHIVE))->toBeFalse()
            ->and($role->hasPermission(Permission::INBOX_CONTACTS_MANAGE))->toBeFalse()
            ->and($role->hasPermission(Permission::INBOX_AUTOMATION_VIEW))->toBeFalse()
            ->and($role->hasPermission(Permission::INBOX_AUTOMATION_MANAGE))->toBeFalse()
            ->and($role->hasPermission(Permission::INBOX_SAVED_REPLIES_MANAGE))->toBeFalse();
    });

    it('can only view WhatsApp resources', function () {
        $role = WorkspaceRole::VIEWER;
        expect($role->hasPermission(Permission::WHATSAPP_CONVERSATIONS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::WHATSAPP_TEMPLATES_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::WHATSAPP_CAMPAIGNS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::WHATSAPP_CONTACTS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::WHATSAPP_CONVERSATIONS_REPLY))->toBeFalse()
            ->and($role->hasPermission(Permission::WHATSAPP_TEMPLATES_MANAGE))->toBeFalse()
            ->and($role->hasPermission(Permission::WHATSAPP_CAMPAIGNS_MANAGE))->toBeFalse()
            ->and($role->hasPermission(Permission::WHATSAPP_CONTACTS_MANAGE))->toBeFalse()
            ->and($role->hasPermission(Permission::WHATSAPP_AUTOMATION_VIEW))->toBeFalse()
            ->and($role->hasPermission(Permission::WHATSAPP_AUTOMATION_MANAGE))->toBeFalse()
            ->and($role->hasPermission(Permission::WHATSAPP_SETUP_MANAGE))->toBeFalse();
    });

    it('can view analytics dashboards and reports only', function () {
        $role = WorkspaceRole::VIEWER;
        expect($role->hasPermission(Permission::ANALYTICS_DASHBOARD_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::ANALYTICS_REPORTS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::ANALYTICS_DEMOGRAPHICS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::ANALYTICS_HASHTAGS_VIEW))->toBeTrue()
            ->and($role->hasPermission(Permission::ANALYTICS_REPORTS_CREATE))->toBeFalse()
            ->and($role->hasPermission(Permission::ANALYTICS_REPORTS_EXPORT))->toBeFalse();
    });

    it('has NO billing permissions', function () {
        $role = WorkspaceRole::VIEWER;
        foreach (Permission::forDomain('billing') as $perm) {
            expect($role->hasPermission($perm))->toBeFalse(
                "VIEWER should NOT have {$perm->value}"
            );
        }
    });

    it('has NO settings permissions', function () {
        $role = WorkspaceRole::VIEWER;
        foreach (Permission::forDomain('settings') as $perm) {
            expect($role->hasPermission($perm))->toBeFalse(
                "VIEWER should NOT have {$perm->value}"
            );
        }
    });

    it('has NO AI assist', function () {
        expect(WorkspaceRole::VIEWER->hasPermission(Permission::AI_ASSIST_USE))->toBeFalse();
    });
});

// ── Default-Deny ───────────────────────────────────────────────────────

describe('default-deny', function () {
    it('denies unknown permission strings for all roles', function () {
        foreach (WorkspaceRole::cases() as $role) {
            expect($role->hasPermission('nonexistent.perm'))->toBeFalse()
                ->and($role->hasPermission(''))->toBeFalse()
                ->and($role->hasPermission('fake.thing.here'))->toBeFalse();
        }
    });
});

// ── hasPermission accepts both Permission enum and string ──────────────

describe('hasPermission input types', function () {
    it('accepts Permission enum', function () {
        expect(WorkspaceRole::OWNER->hasPermission(Permission::CONTENT_POSTS_APPROVE))->toBeTrue();
    });

    it('accepts valid string', function () {
        expect(WorkspaceRole::OWNER->hasPermission('content.posts.approve'))->toBeTrue();
    });

    it('denies invalid string', function () {
        expect(WorkspaceRole::OWNER->hasPermission('totally.fake'))->toBeFalse();
    });
});

// ── Hierarchy superset invariant ───────────────────────────────────────

describe('role hierarchy superset', function () {
    it('OWNER permissions are superset of ADMIN', function () {
        $ownerPerms = WorkspaceRole::OWNER->permissions();
        foreach (WorkspaceRole::ADMIN->permissions() as $perm) {
            expect(in_array($perm, $ownerPerms, true))->toBeTrue(
                "OWNER should have ADMIN permission {$perm->value}"
            );
        }
    });

    it('ADMIN permissions are superset of EDITOR', function () {
        $adminPerms = WorkspaceRole::ADMIN->permissions();
        foreach (WorkspaceRole::EDITOR->permissions() as $perm) {
            expect(in_array($perm, $adminPerms, true))->toBeTrue(
                "ADMIN should have EDITOR permission {$perm->value}"
            );
        }
    });

    it('EDITOR permissions are superset of VIEWER', function () {
        $editorPerms = WorkspaceRole::EDITOR->permissions();
        foreach (WorkspaceRole::VIEWER->permissions() as $perm) {
            expect(in_array($perm, $editorPerms, true))->toBeTrue(
                "EDITOR should have VIEWER permission {$perm->value}"
            );
        }
    });
});
