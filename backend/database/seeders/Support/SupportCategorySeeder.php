<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Models\Support\SupportCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder for SupportCategory.
 *
 * Creates default support categories.
 */
final class SupportCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Support Categories...');

        $categories = $this->getCategories();

        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $category = SupportCategory::create([
                ...$categoryData,
                'slug' => Str::slug($categoryData['name']),
            ]);

            foreach ($children as $childData) {
                SupportCategory::create([
                    ...$childData,
                    'slug' => Str::slug($childData['name']),
                    'parent_id' => $category->id,
                ]);
            }
        }

        $this->command->info('Support Categories seeded successfully!');
    }

    /**
     * Get the list of categories to create.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getCategories(): array
    {
        return [
            [
                'name' => 'Technical Support',
                'description' => 'Technical issues and troubleshooting',
                'color' => '#3B82F6',
                'icon' => 'cog',
                'sort_order' => 1,
                'is_active' => true,
                'children' => [
                    [
                        'name' => 'Bug Reports',
                        'description' => 'Report software bugs and issues',
                        'color' => '#EF4444',
                        'icon' => 'bug-ant',
                        'sort_order' => 1,
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Performance Issues',
                        'description' => 'Slow loading or performance problems',
                        'color' => '#F59E0B',
                        'icon' => 'bolt',
                        'sort_order' => 2,
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Integration Issues',
                        'description' => 'Problems with third-party integrations',
                        'color' => '#8B5CF6',
                        'icon' => 'puzzle-piece',
                        'sort_order' => 3,
                        'is_active' => true,
                    ],
                ],
            ],
            [
                'name' => 'Billing & Payments',
                'description' => 'Billing inquiries and payment issues',
                'color' => '#10B981',
                'icon' => 'credit-card',
                'sort_order' => 2,
                'is_active' => true,
                'children' => [
                    [
                        'name' => 'Invoice Questions',
                        'description' => 'Questions about invoices',
                        'color' => '#10B981',
                        'icon' => 'document-text',
                        'sort_order' => 1,
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Payment Issues',
                        'description' => 'Problems with payments',
                        'color' => '#EF4444',
                        'icon' => 'exclamation-circle',
                        'sort_order' => 2,
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Subscription Changes',
                        'description' => 'Upgrade, downgrade, or cancel subscription',
                        'color' => '#6366F1',
                        'icon' => 'arrows-pointing-out',
                        'sort_order' => 3,
                        'is_active' => true,
                    ],
                ],
            ],
            [
                'name' => 'Account Management',
                'description' => 'Account settings and management',
                'color' => '#6366F1',
                'icon' => 'user-circle',
                'sort_order' => 3,
                'is_active' => true,
                'children' => [
                    [
                        'name' => 'Password & Security',
                        'description' => 'Password reset and security concerns',
                        'color' => '#EF4444',
                        'icon' => 'lock-closed',
                        'sort_order' => 1,
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Profile Settings',
                        'description' => 'Profile and account settings',
                        'color' => '#6366F1',
                        'icon' => 'cog',
                        'sort_order' => 2,
                        'is_active' => true,
                    ],
                ],
            ],
            [
                'name' => 'Feature Requests',
                'description' => 'Suggest new features and improvements',
                'color' => '#8B5CF6',
                'icon' => 'light-bulb',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'General Inquiries',
                'description' => 'General questions and other inquiries',
                'color' => '#6B7280',
                'icon' => 'question-mark-circle',
                'sort_order' => 5,
                'is_active' => true,
            ],
        ];
    }
}
