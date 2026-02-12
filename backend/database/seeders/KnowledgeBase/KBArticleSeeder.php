<?php

declare(strict_types=1);

namespace Database\Seeders\KnowledgeBase;

use App\Enums\KnowledgeBase\KBArticleStatus;
use App\Enums\KnowledgeBase\KBArticleType;
use App\Enums\KnowledgeBase\KBContentFormat;
use App\Enums\KnowledgeBase\KBDifficultyLevel;
use App\Enums\KnowledgeBase\KBVisibility;
use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBCategory;
use App\Models\KnowledgeBase\KBTag;
use App\Models\Platform\SuperAdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder for KB Articles.
 *
 * Creates sample knowledge base articles.
 */
final class KBArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding KB Articles...');

        $author = SuperAdminUser::first();
        if (!$author) {
            $this->command->warn('No SuperAdminUser found. Skipping article seeding.');
            return;
        }

        $articles = $this->getArticles();

        foreach ($articles as $articleData) {
            $category = KBCategory::where('slug', $articleData['category_slug'])->first();
            if (!$category) {
                continue;
            }

            $tags = [];
            if (isset($articleData['tags'])) {
                $tags = KBTag::whereIn('slug', array_map(fn ($t) => Str::slug($t), $articleData['tags']))
                    ->pluck('id')
                    ->toArray();
            }

            unset($articleData['category_slug'], $articleData['tags']);

            $article = KBArticle::create([
                ...$articleData,
                'slug' => Str::slug($articleData['title']),
                'category_id' => $category->id,
                'author_id' => $author->id,
                'content_format' => KBContentFormat::MARKDOWN,
                'visibility' => KBVisibility::ALL,
                'is_public' => true,
            ]);

            if (!empty($tags)) {
                $article->tags()->attach($tags);
                foreach ($tags as $tagId) {
                    KBTag::find($tagId)?->incrementUsageCount();
                }
            }

            if ($article->status === KBArticleStatus::PUBLISHED) {
                $category->incrementArticleCount();
            }
        }

        $this->command->info('KB Articles seeded successfully!');
    }

    /**
     * Get the list of articles to create.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getArticles(): array
    {
        return [
            // Getting Started articles
            [
                'title' => 'Welcome to BizSocials - Your Complete Guide',
                'category_slug' => 'quick-start-guide',
                'excerpt' => 'Get started with BizSocials and learn how to manage all your social media accounts from one platform.',
                'content' => $this->getWelcomeContent(),
                'article_type' => KBArticleType::GETTING_STARTED,
                'difficulty_level' => KBDifficultyLevel::BEGINNER,
                'status' => KBArticleStatus::PUBLISHED,
                'is_featured' => true,
                'published_at' => now()->subDays(30),
                'view_count' => 5432,
                'helpful_count' => 234,
                'not_helpful_count' => 12,
                'tags' => ['Beginner', 'Best Practices'],
            ],
            [
                'title' => 'Connecting Your First Social Account',
                'category_slug' => 'account-setup',
                'excerpt' => 'Learn how to connect your social media accounts to BizSocials step by step.',
                'content' => $this->getConnectAccountContent(),
                'article_type' => KBArticleType::HOW_TO,
                'difficulty_level' => KBDifficultyLevel::BEGINNER,
                'status' => KBArticleStatus::PUBLISHED,
                'published_at' => now()->subDays(25),
                'view_count' => 3421,
                'helpful_count' => 156,
                'not_helpful_count' => 8,
                'tags' => ['Beginner', 'Integration'],
            ],

            // Content Management articles
            [
                'title' => 'How to Create Your First Post',
                'category_slug' => 'creating-posts',
                'excerpt' => 'A step-by-step guide to creating and publishing your first social media post.',
                'content' => $this->getCreatePostContent(),
                'article_type' => KBArticleType::HOW_TO,
                'difficulty_level' => KBDifficultyLevel::BEGINNER,
                'status' => KBArticleStatus::PUBLISHED,
                'published_at' => now()->subDays(20),
                'view_count' => 4521,
                'helpful_count' => 198,
                'not_helpful_count' => 15,
                'tags' => ['Publishing', 'Beginner'],
            ],
            [
                'title' => 'Mastering the Content Calendar',
                'category_slug' => 'content-calendar',
                'excerpt' => 'Learn how to effectively plan and organize your content using the calendar view.',
                'content' => $this->getCalendarContent(),
                'article_type' => KBArticleType::TUTORIAL,
                'difficulty_level' => KBDifficultyLevel::INTERMEDIATE,
                'status' => KBArticleStatus::PUBLISHED,
                'published_at' => now()->subDays(15),
                'view_count' => 2134,
                'helpful_count' => 87,
                'not_helpful_count' => 5,
                'tags' => ['Content Calendar', 'Scheduling', 'Best Practices'],
            ],

            // Analytics articles
            [
                'title' => 'Understanding Your Analytics Dashboard',
                'category_slug' => 'dashboard-overview',
                'excerpt' => 'A comprehensive guide to reading and interpreting your social media analytics.',
                'content' => $this->getAnalyticsContent(),
                'article_type' => KBArticleType::REFERENCE,
                'difficulty_level' => KBDifficultyLevel::INTERMEDIATE,
                'status' => KBArticleStatus::PUBLISHED,
                'published_at' => now()->subDays(10),
                'view_count' => 1876,
                'helpful_count' => 76,
                'not_helpful_count' => 4,
                'tags' => ['Analytics', 'Reporting'],
            ],

            // Troubleshooting articles
            [
                'title' => 'Fixing Facebook Connection Issues',
                'category_slug' => 'connection-issues',
                'excerpt' => 'Common solutions for resolving Facebook page connection problems.',
                'content' => $this->getTroubleshootingContent(),
                'article_type' => KBArticleType::TROUBLESHOOTING,
                'difficulty_level' => KBDifficultyLevel::BEGINNER,
                'status' => KBArticleStatus::PUBLISHED,
                'published_at' => now()->subDays(5),
                'view_count' => 987,
                'helpful_count' => 45,
                'not_helpful_count' => 8,
                'tags' => ['Facebook', 'Troubleshooting', 'Integration'],
            ],

            // FAQ article
            [
                'title' => 'Frequently Asked Questions',
                'category_slug' => 'faqs',
                'excerpt' => 'Answers to the most common questions about BizSocials.',
                'content' => $this->getFaqContent(),
                'article_type' => KBArticleType::FAQ,
                'difficulty_level' => KBDifficultyLevel::BEGINNER,
                'status' => KBArticleStatus::PUBLISHED,
                'is_featured' => true,
                'published_at' => now()->subDays(28),
                'view_count' => 6543,
                'helpful_count' => 321,
                'not_helpful_count' => 18,
                'tags' => ['Beginner'],
            ],

            // Draft article
            [
                'title' => 'Advanced Automation Workflows',
                'category_slug' => 'creating-posts',
                'excerpt' => 'Take your content strategy to the next level with advanced automation.',
                'content' => '# Coming Soon\n\nThis article is currently being written.',
                'article_type' => KBArticleType::TUTORIAL,
                'difficulty_level' => KBDifficultyLevel::ADVANCED,
                'status' => KBArticleStatus::DRAFT,
                'view_count' => 0,
                'helpful_count' => 0,
                'not_helpful_count' => 0,
                'tags' => ['Automation', 'Advanced'],
            ],
        ];
    }

    private function getWelcomeContent(): string
    {
        return <<<'MARKDOWN'
# Welcome to BizSocials

BizSocials is your all-in-one social media management platform. This guide will help you get started quickly.

## What You Can Do

- **Connect multiple social accounts** - Manage Facebook, Instagram, Twitter, LinkedIn, and more from one dashboard
- **Create and schedule posts** - Plan your content weeks in advance
- **Analyze performance** - Track engagement, reach, and growth
- **Collaborate with your team** - Work together with approval workflows

## Quick Start Checklist

1. [ ] Complete your profile setup
2. [ ] Connect at least one social account
3. [ ] Create your first post
4. [ ] Explore the analytics dashboard
5. [ ] Invite team members (optional)

## Next Steps

Ready to dive in? Check out these articles:
- [Connecting Your First Social Account](#)
- [How to Create Your First Post](#)
- [Understanding Your Analytics Dashboard](#)

## Need Help?

If you run into any issues, check our [Troubleshooting](#) section or contact our support team.
MARKDOWN;
    }

    private function getConnectAccountContent(): string
    {
        return <<<'MARKDOWN'
# Connecting Your First Social Account

Follow these steps to connect your social media accounts to BizSocials.

## Before You Begin

Make sure you have:
- Admin access to the social accounts you want to connect
- Your login credentials ready
- Two-factor authentication codes if enabled

## Step 1: Navigate to Connections

1. Click on **Settings** in the left sidebar
2. Select **Social Accounts**
3. Click **Add New Account**

## Step 2: Choose Your Platform

Select the social media platform you want to connect:
- Facebook (Pages and Groups)
- Instagram (Business accounts)
- Twitter/X
- LinkedIn (Profiles and Pages)

## Step 3: Authorize BizSocials

1. Click the platform icon
2. Log in to your social media account
3. Grant the requested permissions
4. Confirm the connection

## Troubleshooting

If you encounter issues:
- Clear your browser cache
- Disable any ad blockers
- Try using an incognito window
- Check our [Connection Issues](#) guide
MARKDOWN;
    }

    private function getCreatePostContent(): string
    {
        return <<<'MARKDOWN'
# How to Create Your First Post

Creating content in BizSocials is simple and intuitive. Let's walk through the process.

## Step 1: Open the Composer

Click the **Create Post** button in the top navigation bar.

## Step 2: Write Your Content

1. Enter your post text
2. Add hashtags (we'll suggest popular ones)
3. Include @mentions if needed

## Step 3: Add Media

Click the media icon to:
- Upload images or videos
- Browse your media library
- Use our stock photo integration

## Step 4: Select Accounts

Choose which social accounts should publish this post:
- Select individual accounts
- Or choose a preset group

## Step 5: Schedule or Publish

- **Publish Now** - Post immediately
- **Schedule** - Pick a date and time
- **Add to Queue** - Use your optimal times

## Best Practices

- Keep posts concise and engaging
- Use high-quality images
- Include a clear call-to-action
- Test different posting times
MARKDOWN;
    }

    private function getCalendarContent(): string
    {
        return <<<'MARKDOWN'
# Mastering the Content Calendar

The Content Calendar helps you visualize and organize your posting schedule.

## Calendar Views

### Month View
See your entire month at a glance. Perfect for planning campaigns.

### Week View
Get a detailed look at your weekly content mix.

### Day View
Focus on a single day's posts with full details.

## Managing Posts

### Drag and Drop
Easily reschedule posts by dragging them to a new date/time.

### Quick Edit
Click any post to quickly edit content or scheduling.

### Bulk Actions
Select multiple posts to move, delete, or duplicate.

## Color Coding

Posts are color-coded by:
- **Blue** - Scheduled
- **Green** - Published
- **Yellow** - Pending Approval
- **Red** - Failed

## Tips for Success

1. Plan content 2-4 weeks ahead
2. Maintain a consistent posting schedule
3. Leave room for timely/trending content
4. Review and adjust based on analytics
MARKDOWN;
    }

    private function getAnalyticsContent(): string
    {
        return <<<'MARKDOWN'
# Understanding Your Analytics Dashboard

Your analytics dashboard provides insights into your social media performance.

## Key Metrics

### Engagement Rate
The percentage of people who interact with your content.

### Reach
The number of unique users who saw your posts.

### Impressions
Total number of times your posts were displayed.

### Follower Growth
Net change in followers over time.

## Dashboard Sections

### Overview
Quick snapshot of your most important metrics.

### Posts Performance
Detailed analytics for individual posts.

### Audience Insights
Demographics and behavior of your followers.

### Best Times
Optimal posting times based on your audience activity.

## Exporting Reports

1. Select date range
2. Choose metrics to include
3. Pick format (PDF, CSV, or Excel)
4. Click Export

## Using Data Effectively

- Compare week-over-week performance
- Identify your top-performing content types
- Adjust posting schedule based on best times
- Track progress toward your goals
MARKDOWN;
    }

    private function getTroubleshootingContent(): string
    {
        return <<<'MARKDOWN'
# Fixing Facebook Connection Issues

Having trouble connecting your Facebook page? Here are common solutions.

## Common Issues

### "Permissions Error"
**Cause:** Insufficient permissions were granted.
**Solution:**
1. Disconnect the account
2. Reconnect and grant ALL requested permissions
3. Make sure you're a Page Admin

### "Token Expired"
**Cause:** Your access has expired after 60 days.
**Solution:**
1. Go to Settings > Social Accounts
2. Click "Refresh" next to the account
3. Re-authenticate if prompted

### "Page Not Showing"
**Cause:** You may not have admin access.
**Solution:**
1. Verify you're a Page Admin in Facebook
2. Check that the Page is published (not unpublished)
3. Try disconnecting and reconnecting

## Still Having Issues?

If these solutions don't work:

1. Check our [Status Page](#) for any ongoing issues
2. Clear browser cache and cookies
3. Try a different browser
4. Contact support with:
   - Your account email
   - Screenshot of the error
   - Steps you've already tried
MARKDOWN;
    }

    private function getFaqContent(): string
    {
        return <<<'MARKDOWN'
# Frequently Asked Questions

## General Questions

### What is BizSocials?
BizSocials is a social media management platform that helps businesses manage all their social accounts from one dashboard.

### How much does BizSocials cost?
We offer plans starting at $29/month. Visit our [Pricing Page](#) for details.

### Is there a free trial?
Yes! All plans include a 14-day free trial. No credit card required.

## Account Questions

### How many social accounts can I connect?
This depends on your plan. Starter allows 5 accounts, Professional allows 15, and Enterprise is unlimited.

### Can I manage multiple businesses?
Yes, our Professional and Enterprise plans support multiple workspaces for different businesses.

## Technical Questions

### What platforms do you support?
We support Facebook, Instagram, Twitter/X, LinkedIn, TikTok, YouTube, and Pinterest.

### Is my data secure?
Absolutely. We use bank-level encryption and never store your social media passwords.

### Can I use BizSocials on mobile?
Yes! We have native apps for iOS and Android.

## Billing Questions

### Can I cancel anytime?
Yes, you can cancel your subscription at any time with no penalties.

### Do you offer refunds?
We offer a 30-day money-back guarantee for new customers.
MARKDOWN;
    }
}
