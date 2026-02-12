<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\KnowledgeBase\KBArticleFeedbackSeeder;
use Database\Seeders\KnowledgeBase\KBArticleSeeder;
use Database\Seeders\KnowledgeBase\KBCategorySeeder;
use Database\Seeders\KnowledgeBase\KBTagSeeder;
use Illuminate\Database\Seeder;

/**
 * Main seeder for Knowledge Base domain.
 *
 * Calls all knowledge base-related seeders in the correct order.
 * Order matters due to foreign key relationships.
 */
final class KnowledgeBaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Knowledge Base seeders...');

        // 1. Categories first (articles depend on them)
        $this->call(KBCategorySeeder::class);

        // 2. Tags (articles can be tagged)
        $this->call(KBTagSeeder::class);

        // 3. Articles (depend on categories and author)
        $this->call(KBArticleSeeder::class);

        // 4. Feedback (depends on articles)
        $this->call(KBArticleFeedbackSeeder::class);

        $this->command->info('Knowledge Base seeders completed successfully!');
    }
}
