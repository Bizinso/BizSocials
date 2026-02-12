<?php

declare(strict_types=1);

namespace Database\Seeders\Feedback;

use App\Enums\Feedback\ChangeType;
use App\Enums\Feedback\ReleaseNoteStatus;
use App\Enums\Feedback\ReleaseType;
use App\Models\Feedback\ReleaseNote;
use App\Models\Feedback\ReleaseNoteItem;
use Illuminate\Database\Seeder;

/**
 * Seeder for ReleaseNote.
 *
 * Creates sample release notes.
 */
final class ReleaseNoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Release Notes...');

        $releases = $this->getReleaseNotes();

        foreach ($releases as $releaseData) {
            $items = $releaseData['items'];
            unset($releaseData['items']);

            $release = ReleaseNote::create($releaseData);

            $sortOrder = 1;
            foreach ($items as $item) {
                ReleaseNoteItem::create([
                    ...$item,
                    'release_note_id' => $release->id,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        $this->command->info('Release Notes seeded successfully!');
    }

    /**
     * Get the list of release notes to create.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getReleaseNotes(): array
    {
        return [
            [
                'version' => '2.5.0',
                'version_name' => 'Phoenix',
                'title' => 'TikTok Integration & Performance Improvements',
                'summary' => 'We are excited to announce full TikTok integration along with major performance improvements.',
                'content' => $this->getV250Content(),
                'content_format' => 'markdown',
                'release_type' => ReleaseType::MINOR,
                'status' => ReleaseNoteStatus::PUBLISHED,
                'is_public' => true,
                'published_at' => now()->subDays(30),
                'items' => [
                    [
                        'title' => 'TikTok Publishing Support',
                        'description' => 'Schedule and publish TikTok videos directly from your dashboard',
                        'change_type' => ChangeType::NEW_FEATURE,
                    ],
                    [
                        'title' => 'TikTok Analytics Integration',
                        'description' => 'View comprehensive TikTok performance metrics alongside your other platforms',
                        'change_type' => ChangeType::NEW_FEATURE,
                    ],
                    [
                        'title' => '50% faster dashboard loading',
                        'description' => 'Optimized API calls and caching for significantly faster page loads',
                        'change_type' => ChangeType::PERFORMANCE,
                    ],
                    [
                        'title' => 'Fixed Instagram story scheduling bug',
                        'description' => 'Resolved issue where stories would not publish at scheduled time',
                        'change_type' => ChangeType::BUG_FIX,
                    ],
                ],
            ],
            [
                'version' => '2.4.2',
                'title' => 'Security Update & Bug Fixes',
                'summary' => 'Important security patches and various bug fixes.',
                'content' => $this->getV242Content(),
                'content_format' => 'markdown',
                'release_type' => ReleaseType::PATCH,
                'status' => ReleaseNoteStatus::PUBLISHED,
                'is_public' => true,
                'published_at' => now()->subDays(45),
                'items' => [
                    [
                        'title' => 'Session Security Enhancement',
                        'description' => 'Improved session handling and token rotation',
                        'change_type' => ChangeType::SECURITY,
                    ],
                    [
                        'title' => 'Fixed duplicate post issue',
                        'description' => 'Resolved race condition that could cause duplicate posts',
                        'change_type' => ChangeType::BUG_FIX,
                    ],
                ],
            ],
            [
                'version' => '2.6.0',
                'title' => 'AI Content Assistant',
                'summary' => 'Introducing our new AI-powered content assistant to help you create better posts.',
                'content' => $this->getV260Content(),
                'content_format' => 'markdown',
                'release_type' => ReleaseType::MINOR,
                'status' => ReleaseNoteStatus::SCHEDULED,
                'is_public' => true,
                'scheduled_at' => now()->addDays(7),
                'items' => [
                    [
                        'title' => 'AI Content Suggestions',
                        'description' => 'Get AI-powered suggestions for your post content',
                        'change_type' => ChangeType::NEW_FEATURE,
                    ],
                    [
                        'title' => 'Smart Posting Times',
                        'description' => 'AI-recommended optimal posting times based on your audience',
                        'change_type' => ChangeType::NEW_FEATURE,
                    ],
                ],
            ],
        ];
    }

    private function getV250Content(): string
    {
        return <<<'MARKDOWN'
## TikTok Integration

We're thrilled to announce full TikTok integration! You can now:

- **Publish videos** directly to TikTok from your BizSocials dashboard
- **Schedule TikTok content** alongside your other platforms
- **Track performance** with comprehensive TikTok analytics

## Performance Improvements

This release includes significant performance optimizations:

- Dashboard loads up to 50% faster
- Reduced API response times
- Improved caching strategy

## Bug Fixes

- Fixed Instagram story scheduling issues
- Resolved calendar view rendering bugs
- Fixed notification preferences not saving
MARKDOWN;
    }

    private function getV242Content(): string
    {
        return <<<'MARKDOWN'
## Security Updates

This patch includes important security enhancements:

- Enhanced session security with improved token rotation
- Updated dependencies to address security vulnerabilities

## Bug Fixes

- Fixed duplicate post issue when network connection is unstable
- Resolved timezone display inconsistencies
- Fixed export functionality for analytics reports
MARKDOWN;
    }

    private function getV260Content(): string
    {
        return <<<'MARKDOWN'
## Introducing AI Content Assistant

We're excited to preview our new AI-powered features:

### Smart Content Suggestions
Let AI help you craft engaging posts with intelligent suggestions based on your brand voice and audience preferences.

### Optimal Posting Times
Our AI analyzes your audience activity to recommend the best times to post for maximum engagement.

### Coming Soon
- AI-powered hashtag suggestions
- Content performance predictions
- Automated A/B testing
MARKDOWN;
    }
}
