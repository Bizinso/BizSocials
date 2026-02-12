<?php

declare(strict_types=1);

namespace Database\Seeders\Content;

use App\Enums\Content\ApprovalDecisionType;
use App\Enums\Content\MediaProcessingStatus;
use App\Enums\Content\MediaType;
use App\Enums\Content\PostStatus;
use App\Enums\Content\PostTargetStatus;
use App\Enums\Content\PostType;
use App\Models\Content\ApprovalDecision;
use App\Models\Content\Post;
use App\Models\Content\PostMedia;
use App\Models\Content\PostTarget;
use App\Models\Social\SocialAccount;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Seeder;

/**
 * Seeder for Post and related models.
 *
 * Creates sample posts with various statuses, targets, media, and approval decisions.
 */
final class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $workspaces = Workspace::with(['socialAccounts'])->get()->keyBy('slug');
        $users = User::all()->keyBy('email');

        // 1. Marketing Team - Multiple posts with various statuses
        $marketingWorkspace = $workspaces->get('marketing-team');
        if ($marketingWorkspace && $marketingWorkspace->socialAccounts->isNotEmpty()) {
            $author = $users->get('john.admin@acmecorp.com') ?? User::first();
            $approver = $users->get('sarah.manager@acmecorp.com') ?? $author;

            // Draft post
            $draftPost = $this->createPost([
                'workspace_id' => $marketingWorkspace->id,
                'created_by_user_id' => $author->id,
                'content_text' => 'We\'re excited to announce our new product launch! Stay tuned for more details. #innovation #technology',
                'status' => PostStatus::DRAFT,
                'post_type' => PostType::STANDARD,
                'hashtags' => ['#innovation', '#technology'],
            ]);

            // Add media to draft
            $this->createMedia($draftPost, MediaType::IMAGE, 'product-teaser.jpg');

            // Submitted post awaiting approval
            $submittedPost = $this->createPost([
                'workspace_id' => $marketingWorkspace->id,
                'created_by_user_id' => $author->id,
                'content_text' => 'Check out our latest blog post on industry trends! Link in comments. #industrytrends #thoughtleadership',
                'status' => PostStatus::SUBMITTED,
                'post_type' => PostType::STANDARD,
                'submitted_at' => now()->subHours(2),
                'hashtags' => ['#industrytrends', '#thoughtleadership'],
                'link_url' => 'https://blog.acmecorp.com/industry-trends',
            ]);

            // Add targets for submitted post
            foreach ($marketingWorkspace->socialAccounts->take(2) as $account) {
                $this->createTarget($submittedPost, $account);
            }

            // Approved post ready to schedule
            $approvedPost = $this->createPost([
                'workspace_id' => $marketingWorkspace->id,
                'created_by_user_id' => $author->id,
                'content_text' => 'Join us for our upcoming webinar on digital transformation! Register now: [link]',
                'status' => PostStatus::APPROVED,
                'post_type' => PostType::STANDARD,
                'submitted_at' => now()->subDays(1),
            ]);

            // Add approval decision
            $this->createApprovalDecision($approvedPost, $approver, ApprovalDecisionType::APPROVED, 'Great content!');

            // Add targets
            foreach ($marketingWorkspace->socialAccounts->take(3) as $account) {
                $this->createTarget($approvedPost, $account);
            }

            // Scheduled post
            $scheduledPost = $this->createPost([
                'workspace_id' => $marketingWorkspace->id,
                'created_by_user_id' => $author->id,
                'content_text' => 'Happy Monday! Here\'s a motivational quote to start your week. #MondayMotivation #Success',
                'status' => PostStatus::SCHEDULED,
                'post_type' => PostType::STANDARD,
                'submitted_at' => now()->subDays(2),
                'scheduled_at' => now()->addDays(3)->setHour(9)->setMinute(0),
                'scheduled_timezone' => 'America/New_York',
                'hashtags' => ['#MondayMotivation', '#Success'],
            ]);

            // Add media
            $this->createMedia($scheduledPost, MediaType::IMAGE, 'motivation-quote.png');

            // Add targets
            foreach ($marketingWorkspace->socialAccounts->where('status', 'connected')->take(2) as $account) {
                $this->createTarget($scheduledPost, $account);
            }

            // Add approval
            $this->createApprovalDecision($scheduledPost, $approver, ApprovalDecisionType::APPROVED);

            // Published post
            $publishedPost = $this->createPost([
                'workspace_id' => $marketingWorkspace->id,
                'created_by_user_id' => $author->id,
                'content_text' => 'We\'re thrilled to announce that Acme Corp has been recognized as a top innovator in our industry!',
                'status' => PostStatus::PUBLISHED,
                'post_type' => PostType::STANDARD,
                'submitted_at' => now()->subWeek(),
                'scheduled_at' => now()->subDays(5),
                'published_at' => now()->subDays(5),
            ]);

            // Add targets with published status
            foreach ($marketingWorkspace->socialAccounts->where('status', 'connected')->take(2) as $account) {
                $this->createTarget($publishedPost, $account, PostTargetStatus::PUBLISHED, [
                    'external_post_id' => 'ext_' . uniqid(),
                    'external_post_url' => 'https://linkedin.com/posts/' . uniqid(),
                    'published_at' => now()->subDays(5),
                    'metrics' => [
                        'likes' => 245,
                        'comments' => 18,
                        'shares' => 12,
                        'impressions' => 5430,
                    ],
                ]);
            }

            // Rejected post
            $rejectedPost = $this->createPost([
                'workspace_id' => $marketingWorkspace->id,
                'created_by_user_id' => $author->id,
                'content_text' => 'Check out our competitor\'s failure! We\'re so much better.',
                'status' => PostStatus::REJECTED,
                'post_type' => PostType::STANDARD,
                'submitted_at' => now()->subDays(3),
                'rejection_reason' => 'Content is not aligned with brand voice. Please revise to be more professional.',
            ]);

            // Add rejection decision
            $this->createApprovalDecision($rejectedPost, $approver, ApprovalDecisionType::REJECTED, 'Content is not aligned with brand voice. Please revise to be more professional.');
        }

        // 2. StartupXYZ - Posts for startup
        $startupWorkspace = $workspaces->get('main');
        if ($startupWorkspace && $startupWorkspace->tenant?->slug === 'startupxyz' && $startupWorkspace->socialAccounts->isNotEmpty()) {
            $author = $users->get('alex@startupxyz.io') ?? User::first();

            // Thread post (Twitter)
            $threadPost = $this->createPost([
                'workspace_id' => $startupWorkspace->id,
                'created_by_user_id' => $author->id,
                'content_text' => '1/ Big news! We just closed our Series A funding round. Here\'s what we learned along the way... 🧵',
                'content_variations' => [
                    'twitter' => [
                        '1/ Big news! We just closed our Series A funding round. Here\'s what we learned along the way...',
                        '2/ First, focus on product-market fit. We spent 6 months validating our idea before writing a single line of code.',
                        '3/ Second, build relationships with investors early. Don\'t wait until you need money.',
                        '4/ Third, your team is everything. Hire people who believe in your mission.',
                        '5/ We\'re just getting started. Thanks to everyone who believed in us! 🚀',
                    ],
                ],
                'status' => PostStatus::PUBLISHED,
                'post_type' => PostType::THREAD,
                'published_at' => now()->subDays(2),
            ]);

            // Add Twitter target
            $twitterAccount = $startupWorkspace->socialAccounts->firstWhere('platform', 'twitter');
            if ($twitterAccount) {
                $this->createTarget($threadPost, $twitterAccount, PostTargetStatus::PUBLISHED, [
                    'external_post_id' => '1234567890',
                    'external_post_url' => 'https://twitter.com/StartupXYZ/status/1234567890',
                    'published_at' => now()->subDays(2),
                    'metrics' => [
                        'likes' => 1250,
                        'retweets' => 320,
                        'replies' => 85,
                        'impressions' => 45000,
                    ],
                ]);
            }

            // Scheduled reel
            $reelPost = $this->createPost([
                'workspace_id' => $startupWorkspace->id,
                'created_by_user_id' => $author->id,
                'content_text' => 'Behind the scenes at StartupXYZ! Watch our team in action. #StartupLife #BehindTheScenes',
                'status' => PostStatus::SCHEDULED,
                'post_type' => PostType::REEL,
                'scheduled_at' => now()->addDays(1),
                'scheduled_timezone' => 'UTC',
                'hashtags' => ['#StartupLife', '#BehindTheScenes'],
            ]);

            // Add video media
            $this->createMedia($reelPost, MediaType::VIDEO, 'behind-the-scenes.mp4', [
                'duration_seconds' => 30,
                'dimensions' => ['width' => 1080, 'height' => 1920],
            ]);
        }

        // 3. Fashion Brand - Visual content
        $fashionWorkspace = $workspaces->get('brand-marketing');
        if ($fashionWorkspace && $fashionWorkspace->socialAccounts->isNotEmpty()) {
            $author = $users->get('emma@fashionbrand.co') ?? User::first();

            // Carousel post
            $carouselPost = $this->createPost([
                'workspace_id' => $fashionWorkspace->id,
                'created_by_user_id' => $author->id,
                'content_text' => 'New arrivals are here! Swipe to see our Spring Collection 2026 🌸 #SpringCollection #Fashion #NewArrivals',
                'status' => PostStatus::PUBLISHED,
                'post_type' => PostType::STANDARD,
                'published_at' => now()->subDays(1),
                'hashtags' => ['#SpringCollection', '#Fashion', '#NewArrivals'],
            ]);

            // Add multiple images
            for ($i = 1; $i <= 5; $i++) {
                $this->createMedia($carouselPost, MediaType::IMAGE, "spring-collection-{$i}.jpg", [
                    'sort_order' => $i - 1,
                ]);
            }

            // Add targets
            foreach ($fashionWorkspace->socialAccounts as $account) {
                $this->createTarget($carouselPost, $account, PostTargetStatus::PUBLISHED, [
                    'external_post_id' => 'fashion_' . uniqid(),
                    'external_post_url' => 'https://instagram.com/p/' . uniqid(),
                    'published_at' => now()->subDays(1),
                ]);
            }

            // Story post
            $storyPost = $this->createPost([
                'workspace_id' => $fashionWorkspace->id,
                'created_by_user_id' => $author->id,
                'content_text' => 'Limited time offer! 24 hours only ⏰',
                'status' => PostStatus::SCHEDULED,
                'post_type' => PostType::STORY,
                'scheduled_at' => now()->addHours(6),
                'scheduled_timezone' => 'America/Los_Angeles',
            ]);

            // Add story media
            $this->createMedia($storyPost, MediaType::IMAGE, 'flash-sale-story.jpg', [
                'dimensions' => ['width' => 1080, 'height' => 1920],
            ]);
        }

        $this->command->info('Posts seeded successfully.');
    }

    /**
     * Create a post with the given attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createPost(array $attributes): Post
    {
        return Post::create($attributes);
    }

    /**
     * Create a post target.
     *
     * @param  array<string, mixed>  $extra
     */
    private function createTarget(
        Post $post,
        SocialAccount $account,
        PostTargetStatus $status = PostTargetStatus::PENDING,
        array $extra = []
    ): PostTarget {
        return PostTarget::create(array_merge([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
            'platform_code' => $account->platform->value,
            'status' => $status,
        ], $extra));
    }

    /**
     * Create post media.
     *
     * @param  array<string, mixed>  $extra
     */
    private function createMedia(
        Post $post,
        MediaType $type,
        string $fileName,
        array $extra = []
    ): PostMedia {
        $mimeType = match ($type) {
            MediaType::IMAGE => 'image/jpeg',
            MediaType::VIDEO => 'video/mp4',
            MediaType::GIF => 'image/gif',
            MediaType::DOCUMENT => 'application/pdf',
        };

        $fileSize = match ($type) {
            MediaType::IMAGE => random_int(500000, 5000000),
            MediaType::VIDEO => random_int(10000000, 100000000),
            MediaType::GIF => random_int(1000000, 10000000),
            MediaType::DOCUMENT => random_int(100000, 10000000),
        };

        return PostMedia::create(array_merge([
            'post_id' => $post->id,
            'type' => $type,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'storage_path' => 'media/posts/' . $post->id . '/' . $fileName,
            'cdn_url' => 'https://cdn.bizsocials.com/media/posts/' . $post->id . '/' . $fileName,
            'thumbnail_url' => $type !== MediaType::DOCUMENT
                ? 'https://cdn.bizsocials.com/media/posts/' . $post->id . '/thumb_' . $fileName
                : null,
            'dimensions' => $extra['dimensions'] ?? ($type !== MediaType::DOCUMENT ? ['width' => 1080, 'height' => 1080] : null),
            'duration_seconds' => $extra['duration_seconds'] ?? null,
            'alt_text' => "Media for post: " . substr($post->content_text ?? '', 0, 50),
            'sort_order' => $extra['sort_order'] ?? 0,
            'processing_status' => MediaProcessingStatus::COMPLETED,
        ], $extra));
    }

    /**
     * Create an approval decision.
     */
    private function createApprovalDecision(
        Post $post,
        User $decidedBy,
        ApprovalDecisionType $decision,
        ?string $comment = null
    ): ApprovalDecision {
        // Deactivate any existing active decisions
        ApprovalDecision::where('post_id', $post->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return ApprovalDecision::create([
            'post_id' => $post->id,
            'decided_by_user_id' => $decidedBy->id,
            'decision' => $decision,
            'comment' => $comment,
            'is_active' => true,
            'decided_at' => now(),
        ]);
    }
}
