<?php

declare(strict_types=1);

namespace Database\Seeders\KnowledgeBase;

use App\Enums\KnowledgeBase\KBVisibility;
use App\Models\KnowledgeBase\KBCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder for KB Categories.
 *
 * Creates the main knowledge base category structure.
 */
final class KBCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding KB Categories...');

        $categories = $this->getCategories();

        foreach ($categories as $categoryData) {
            $this->createCategory($categoryData);
        }

        $this->command->info('KB Categories seeded successfully!');
    }

    /**
     * Create a category with optional children.
     *
     * @param  array<string, mixed>  $data
     */
    private function createCategory(array $data, ?string $parentId = null): KBCategory
    {
        $children = $data['children'] ?? [];
        unset($data['children']);

        $category = KBCategory::create([
            ...$data,
            'parent_id' => $parentId,
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'visibility' => $data['visibility'] ?? KBVisibility::ALL,
            'is_public' => $data['is_public'] ?? true,
        ]);

        foreach ($children as $childData) {
            $this->createCategory($childData, $category->id);
        }

        return $category;
    }

    /**
     * Get the category structure.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getCategories(): array
    {
        return [
            [
                'name' => 'Getting Started',
                'description' => 'Everything you need to get started with BizSocials.',
                'icon' => 'rocket',
                'color' => '#10B981',
                'sort_order' => 1,
                'children' => [
                    [
                        'name' => 'Quick Start Guide',
                        'description' => 'Get up and running in minutes.',
                        'icon' => 'play',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Platform Overview',
                        'description' => 'Learn about BizSocials features and capabilities.',
                        'icon' => 'eye',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Account Setup',
                        'description' => 'Configure your account settings.',
                        'icon' => 'user-cog',
                        'sort_order' => 3,
                    ],
                ],
            ],
            [
                'name' => 'Social Platforms',
                'description' => 'Connect and manage your social media accounts.',
                'icon' => 'share',
                'color' => '#3B82F6',
                'sort_order' => 2,
                'children' => [
                    [
                        'name' => 'Facebook',
                        'description' => 'Connect and manage Facebook pages and groups.',
                        'icon' => 'facebook',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Instagram',
                        'description' => 'Connect and manage Instagram business accounts.',
                        'icon' => 'instagram',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Twitter/X',
                        'description' => 'Connect and manage Twitter accounts.',
                        'icon' => 'twitter',
                        'sort_order' => 3,
                    ],
                    [
                        'name' => 'LinkedIn',
                        'description' => 'Connect and manage LinkedIn profiles and pages.',
                        'icon' => 'linkedin',
                        'sort_order' => 4,
                    ],
                ],
            ],
            [
                'name' => 'Content Management',
                'description' => 'Create, schedule, and publish content.',
                'icon' => 'document-text',
                'color' => '#8B5CF6',
                'sort_order' => 3,
                'children' => [
                    [
                        'name' => 'Creating Posts',
                        'description' => 'How to create engaging social media posts.',
                        'icon' => 'pencil',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Scheduling',
                        'description' => 'Schedule posts for optimal engagement times.',
                        'icon' => 'calendar',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Content Calendar',
                        'description' => 'Organize your content with the calendar view.',
                        'icon' => 'calendar-days',
                        'sort_order' => 3,
                    ],
                    [
                        'name' => 'Media Library',
                        'description' => 'Manage images, videos, and other media.',
                        'icon' => 'photo',
                        'sort_order' => 4,
                    ],
                ],
            ],
            [
                'name' => 'Analytics',
                'description' => 'Track and analyze your social media performance.',
                'icon' => 'chart-bar',
                'color' => '#F59E0B',
                'sort_order' => 4,
                'children' => [
                    [
                        'name' => 'Dashboard Overview',
                        'description' => 'Understanding your analytics dashboard.',
                        'icon' => 'presentation-chart-bar',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Reports',
                        'description' => 'Generate and export detailed reports.',
                        'icon' => 'document-chart-bar',
                        'sort_order' => 2,
                    ],
                ],
            ],
            [
                'name' => 'Team & Collaboration',
                'description' => 'Work together with your team.',
                'icon' => 'users',
                'color' => '#EC4899',
                'sort_order' => 5,
                'children' => [
                    [
                        'name' => 'Team Management',
                        'description' => 'Add and manage team members.',
                        'icon' => 'user-group',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Roles & Permissions',
                        'description' => 'Configure access controls.',
                        'icon' => 'shield-check',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Approval Workflows',
                        'description' => 'Set up content approval processes.',
                        'icon' => 'check-circle',
                        'sort_order' => 3,
                    ],
                ],
            ],
            [
                'name' => 'Troubleshooting',
                'description' => 'Solutions to common issues and problems.',
                'icon' => 'wrench-screwdriver',
                'color' => '#EF4444',
                'sort_order' => 6,
                'children' => [
                    [
                        'name' => 'Connection Issues',
                        'description' => 'Fix problems connecting social accounts.',
                        'icon' => 'link',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Publishing Errors',
                        'description' => 'Resolve posting and scheduling issues.',
                        'icon' => 'exclamation-triangle',
                        'sort_order' => 2,
                    ],
                ],
            ],
            [
                'name' => 'FAQs',
                'description' => 'Frequently asked questions.',
                'icon' => 'question-mark-circle',
                'color' => '#6366F1',
                'sort_order' => 7,
            ],
            [
                'name' => 'API Documentation',
                'description' => 'Technical documentation for developers.',
                'icon' => 'code-bracket',
                'color' => '#14B8A6',
                'sort_order' => 8,
                'visibility' => KBVisibility::AUTHENTICATED,
                'children' => [
                    [
                        'name' => 'Authentication',
                        'description' => 'API authentication and authorization.',
                        'icon' => 'key',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Endpoints',
                        'description' => 'API endpoint reference.',
                        'icon' => 'server',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Webhooks',
                        'description' => 'Configure and use webhooks.',
                        'icon' => 'arrow-path',
                        'sort_order' => 3,
                    ],
                ],
            ],
        ];
    }
}
