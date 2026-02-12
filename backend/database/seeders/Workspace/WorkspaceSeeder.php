<?php

declare(strict_types=1);

namespace Database\Seeders\Workspace;

use App\Enums\Workspace\WorkspaceStatus;
use App\Models\Tenant\Tenant;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Seeder;

/**
 * Seeder for Workspace model.
 *
 * Creates sample workspaces for each seeded tenant based on their business type.
 */
final class WorkspaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = Tenant::all()->keyBy('slug');

        // 1. Acme Corporation (Enterprise) - Multiple workspaces
        $acmeTenant = $tenants->get('acme-corporation');
        if ($acmeTenant) {
            $this->createWorkspace([
                'tenant_id' => $acmeTenant->id,
                'name' => 'Marketing Team',
                'slug' => 'marketing-team',
                'description' => 'Social media management for marketing campaigns',
                'status' => WorkspaceStatus::ACTIVE,
                'settings' => $this->getDefaultSettings([
                    'content_categories' => ['Campaigns', 'Brand Awareness', 'Product Launch'],
                ]),
            ]);

            $this->createWorkspace([
                'tenant_id' => $acmeTenant->id,
                'name' => 'Sales Team',
                'slug' => 'sales-team',
                'description' => 'Social selling and lead generation content',
                'status' => WorkspaceStatus::ACTIVE,
                'settings' => $this->getDefaultSettings([
                    'content_categories' => ['Lead Gen', 'Customer Success', 'Case Studies'],
                ]),
            ]);

            $this->createWorkspace([
                'tenant_id' => $acmeTenant->id,
                'name' => 'Product Team',
                'slug' => 'product-team',
                'description' => 'Product announcements and feature updates',
                'status' => WorkspaceStatus::ACTIVE,
                'settings' => $this->getDefaultSettings([
                    'content_categories' => ['Product Updates', 'Feature Launches', 'Roadmap'],
                ]),
            ]);
        }

        // 2. StartupXYZ (SMB) - Single main workspace
        $startupTenant = $tenants->get('startupxyz');
        if ($startupTenant) {
            $this->createWorkspace([
                'tenant_id' => $startupTenant->id,
                'name' => 'Main',
                'slug' => 'main',
                'description' => 'Primary workspace for all social media activities',
                'status' => WorkspaceStatus::ACTIVE,
                'settings' => $this->getDefaultSettings([
                    'approval_workflow' => [
                        'enabled' => false,
                        'required_for_roles' => [],
                    ],
                ]),
            ]);
        }

        // 3. Fashion Brand Co (B2C) - Brand and partnerships workspaces
        $fashionTenant = $tenants->get('fashion-brand-co');
        if ($fashionTenant) {
            $this->createWorkspace([
                'tenant_id' => $fashionTenant->id,
                'name' => 'Brand Marketing',
                'slug' => 'brand-marketing',
                'description' => 'Brand awareness and lifestyle content',
                'status' => WorkspaceStatus::ACTIVE,
                'settings' => $this->getDefaultSettings([
                    'timezone' => 'America/New_York',
                    'content_categories' => ['Lifestyle', 'Fashion', 'Seasonal'],
                ]),
            ]);

            $this->createWorkspace([
                'tenant_id' => $fashionTenant->id,
                'name' => 'Influencer Partnerships',
                'slug' => 'influencer-partnerships',
                'description' => 'Collaboration content with influencers',
                'status' => WorkspaceStatus::ACTIVE,
                'settings' => $this->getDefaultSettings([
                    'timezone' => 'America/New_York',
                    'content_categories' => ['Collaborations', 'Sponsored', 'UGC'],
                ]),
            ]);
        }

        // 4. John Freelancer (Individual) - Single personal workspace
        $freelancerTenant = $tenants->get('john-freelancer');
        if ($freelancerTenant) {
            $this->createWorkspace([
                'tenant_id' => $freelancerTenant->id,
                'name' => 'My Business',
                'slug' => 'my-business',
                'description' => 'Personal brand and freelance services',
                'status' => WorkspaceStatus::ACTIVE,
                'settings' => $this->getDefaultSettings([
                    'timezone' => 'Europe/London',
                    'approval_workflow' => [
                        'enabled' => false,
                        'required_for_roles' => [],
                    ],
                ]),
            ]);
        }

        // 5. Sarah Lifestyle (Influencer) - Content creation workspace
        $influencerTenant = $tenants->get('sarah-lifestyle');
        if ($influencerTenant) {
            $this->createWorkspace([
                'tenant_id' => $influencerTenant->id,
                'name' => 'Content Creation',
                'slug' => 'content-creation',
                'description' => 'Lifestyle and personal brand content',
                'status' => WorkspaceStatus::ACTIVE,
                'settings' => $this->getDefaultSettings([
                    'timezone' => 'America/Los_Angeles',
                    'content_categories' => ['Lifestyle', 'Travel', 'Fashion', 'Beauty'],
                ]),
            ]);
        }

        // 6. Green Earth Foundation (Non-Profit) - Campaign and community workspaces
        $ngoTenant = $tenants->get('green-earth-foundation');
        if ($ngoTenant) {
            $this->createWorkspace([
                'tenant_id' => $ngoTenant->id,
                'name' => 'Campaigns',
                'slug' => 'campaigns',
                'description' => 'Environmental awareness campaigns',
                'status' => WorkspaceStatus::ACTIVE,
                'settings' => $this->getDefaultSettings([
                    'content_categories' => ['Awareness', 'Fundraising', 'Events'],
                ]),
            ]);

            $this->createWorkspace([
                'tenant_id' => $ngoTenant->id,
                'name' => 'Community',
                'slug' => 'community',
                'description' => 'Community engagement and volunteer coordination',
                'status' => WorkspaceStatus::ACTIVE,
                'settings' => $this->getDefaultSettings([
                    'content_categories' => ['Volunteer', 'Community Stories', 'Impact'],
                ]),
            ]);
        }

        $this->command->info('Workspaces seeded successfully.');
    }

    /**
     * Create a workspace with the given attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createWorkspace(array $attributes): Workspace
    {
        return Workspace::firstOrCreate(
            ['tenant_id' => $attributes['tenant_id'], 'slug' => $attributes['slug']],
            $attributes
        );
    }

    /**
     * Get default settings with optional overrides.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function getDefaultSettings(array $overrides = []): array
    {
        $defaults = [
            'timezone' => 'Asia/Kolkata',
            'date_format' => 'DD/MM/YYYY',
            'approval_workflow' => [
                'enabled' => true,
                'required_for_roles' => ['editor'],
            ],
            'default_social_accounts' => [],
            'content_categories' => ['Marketing', 'Product', 'Support'],
            'hashtag_groups' => [
                'brand' => ['#BizSocials', '#SocialMedia'],
                'campaign' => [],
            ],
        ];

        return array_merge($defaults, $overrides);
    }
}
