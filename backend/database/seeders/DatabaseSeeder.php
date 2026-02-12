<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Main database seeder.
 *
 * Orchestrates all domain seeders in the correct order.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Platform seeders (super admins, configs, plans, etc.)
        $this->call(PlatformSeeder::class);

        // Tenant seeders (tenants, profiles, onboarding, usage)
        $this->call(TenantSeeder::class);

        // User seeders (users, sessions, invitations)
        $this->call(UserSeeder::class);

        // Billing seeders (subscriptions, invoices, payments, payment methods)
        $this->call(BillingSeeder::class);

        // Workspace seeders (workspaces, memberships)
        $this->call(WorkspaceSeeder::class);

        // Social Account seeders (social accounts)
        $this->call(SocialAccountSeeder::class);

        // Content seeders (posts, targets, media, approvals)
        $this->call(ContentSeeder::class);

        // Inbox seeders (inbox items, replies, metric snapshots)
        $this->call(InboxSeeder::class);

        // Knowledge Base seeders (categories, tags, articles, feedback)
        $this->call(KnowledgeBaseSeeder::class);

        // Feedback & Roadmap seeders (tags, roadmap items, feedback, release notes)
        $this->call(FeedbackRoadmapSeeder::class);

        // Support seeders (categories, tags, canned responses, tickets)
        $this->call(SupportSeeder::class);

        // Audit & Security seeders (audit logs, security events)
        $this->call(AuditSecuritySeeder::class);
    }
}
