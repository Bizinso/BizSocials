<?php

declare(strict_types=1);

namespace Database\Factories\Analytics;

use App\Enums\Analytics\ActivityCategory;
use App\Enums\Analytics\ActivityType;
use App\Models\Analytics\UserActivityLog;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for UserActivityLog model.
 *
 * @extends Factory<UserActivityLog>
 */
final class UserActivityLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<UserActivityLog>
     */
    protected $model = UserActivityLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $activityType = fake()->randomElement(ActivityType::cases());

        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'workspace_id' => null,
            'activity_type' => $activityType,
            'activity_category' => $activityType->category(),
            'resource_type' => fake()->boolean(50) ? fake()->randomElement(['post', 'media', 'report', 'account']) : null,
            'resource_id' => fake()->boolean(50) ? fake()->uuid() : null,
            'page_url' => fake()->boolean(70) ? fake()->url() : null,
            'referrer_url' => fake()->boolean(30) ? fake()->url() : null,
            'session_id' => Str::random(40),
            'device_type' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
            'browser' => fake()->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
            'os' => fake()->randomElement(['Windows', 'macOS', 'iOS', 'Android', 'Linux']),
            'metadata' => null,
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Associate with a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
        ]);
    }

    /**
     * Associate with a specific tenant.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes): array => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Associate with a specific workspace.
     */
    public function forWorkspace(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes): array => [
            'workspace_id' => $workspace->id,
        ]);
    }

    /**
     * Set a specific activity type.
     */
    public function ofType(ActivityType $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'activity_type' => $type,
            'activity_category' => $type->category(),
        ]);
    }

    /**
     * Set a specific activity category.
     */
    public function ofCategory(ActivityCategory $category): static
    {
        return $this->state(fn (array $attributes): array => [
            'activity_category' => $category,
        ]);
    }

    /**
     * Set a specific session.
     */
    public function forSession(string $sessionId): static
    {
        return $this->state(fn (array $attributes): array => [
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Create a content creation activity.
     */
    public function contentCreation(): static
    {
        $types = [
            ActivityType::POST_CREATED,
            ActivityType::POST_EDITED,
            ActivityType::MEDIA_UPLOADED,
        ];

        return $this->state(fn (array $attributes): array => [
            'activity_type' => fake()->randomElement($types),
            'activity_category' => ActivityCategory::CONTENT_CREATION,
        ]);
    }

    /**
     * Create a publishing activity.
     */
    public function publishing(): static
    {
        $types = [
            ActivityType::POST_SCHEDULED,
            ActivityType::POST_PUBLISHED,
        ];

        return $this->state(fn (array $attributes): array => [
            'activity_type' => fake()->randomElement($types),
            'activity_category' => ActivityCategory::PUBLISHING,
        ]);
    }

    /**
     * Create an engagement activity.
     */
    public function engagement(): static
    {
        $types = [
            ActivityType::INBOX_VIEWED,
            ActivityType::REPLY_SENT,
            ActivityType::COMMENT_LIKED,
        ];

        return $this->state(fn (array $attributes): array => [
            'activity_type' => fake()->randomElement($types),
            'activity_category' => ActivityCategory::ENGAGEMENT,
        ]);
    }

    /**
     * Create an analytics activity.
     */
    public function analytics(): static
    {
        $types = [
            ActivityType::DASHBOARD_VIEWED,
            ActivityType::REPORT_GENERATED,
            ActivityType::REPORT_EXPORTED,
        ];

        return $this->state(fn (array $attributes): array => [
            'activity_type' => fake()->randomElement($types),
            'activity_category' => ActivityCategory::ANALYTICS,
        ]);
    }

    /**
     * Create an AI feature activity.
     */
    public function aiFeature(): static
    {
        $types = [
            ActivityType::AI_CAPTION_GENERATED,
            ActivityType::AI_HASHTAG_SUGGESTED,
            ActivityType::AI_BEST_TIME_CHECKED,
        ];

        return $this->state(fn (array $attributes): array => [
            'activity_type' => fake()->randomElement($types),
            'activity_category' => ActivityCategory::AI_FEATURES,
        ]);
    }

    /**
     * Create a login activity.
     */
    public function login(): static
    {
        return $this->state(fn (array $attributes): array => [
            'activity_type' => ActivityType::USER_LOGIN,
            'activity_category' => ActivityCategory::AUTHENTICATION,
        ]);
    }

    /**
     * Create a logout activity.
     */
    public function logout(): static
    {
        return $this->state(fn (array $attributes): array => [
            'activity_type' => ActivityType::USER_LOGOUT,
            'activity_category' => ActivityCategory::AUTHENTICATION,
        ]);
    }

    /**
     * Create a post created activity.
     */
    public function postCreated(?string $postId = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'activity_type' => ActivityType::POST_CREATED,
            'activity_category' => ActivityCategory::CONTENT_CREATION,
            'resource_type' => 'post',
            'resource_id' => $postId ?? fake()->uuid(),
        ]);
    }

    /**
     * Create a post published activity.
     */
    public function postPublished(?string $postId = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'activity_type' => ActivityType::POST_PUBLISHED,
            'activity_category' => ActivityCategory::PUBLISHING,
            'resource_type' => 'post',
            'resource_id' => $postId ?? fake()->uuid(),
        ]);
    }

    /**
     * Create a report generated activity.
     */
    public function reportGenerated(?string $reportId = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'activity_type' => ActivityType::REPORT_GENERATED,
            'activity_category' => ActivityCategory::ANALYTICS,
            'resource_type' => 'report',
            'resource_id' => $reportId ?? fake()->uuid(),
        ]);
    }

    /**
     * Set device type to desktop.
     */
    public function desktop(): static
    {
        return $this->state(fn (array $attributes): array => [
            'device_type' => 'desktop',
        ]);
    }

    /**
     * Set device type to mobile.
     */
    public function mobile(): static
    {
        return $this->state(fn (array $attributes): array => [
            'device_type' => 'mobile',
        ]);
    }

    /**
     * Set device type to tablet.
     */
    public function tablet(): static
    {
        return $this->state(fn (array $attributes): array => [
            'device_type' => 'tablet',
        ]);
    }

    /**
     * Create an activity for today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_at' => now(),
        ]);
    }

    /**
     * Create a recent activity.
     */
    public function recent(int $days = 7): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_at' => fake()->dateTimeBetween("-{$days} days", 'now'),
        ]);
    }

    /**
     * Create an old activity (created exactly $days ago).
     */
    public function old(int $days = 90): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_at' => now()->subDays($days),
        ]);
    }

    /**
     * Add metadata to the activity.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function withMetadata(array $metadata): static
    {
        return $this->state(fn (array $attributes): array => [
            'metadata' => $metadata,
        ]);
    }

    /**
     * Set page URL.
     */
    public function withPageUrl(string $url): static
    {
        return $this->state(fn (array $attributes): array => [
            'page_url' => $url,
        ]);
    }

    /**
     * Associate with a specific resource.
     */
    public function forResource(string $resourceType, string $resourceId): static
    {
        return $this->state(fn (array $attributes): array => [
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
        ]);
    }
}
