<?php

declare(strict_types=1);

namespace Database\Factories\Notification;

use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationType;
use App\Models\Notification\Notification;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for Notification model.
 *
 * @extends Factory<Notification>
 */
final class NotificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Notification>
     */
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(NotificationType::cases());

        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'type' => $type,
            'channel' => NotificationChannel::IN_APP,
            'title' => fake()->sentence(4),
            'message' => fake()->paragraph(1),
            'data' => fake()->boolean(50) ? [
                'entity_id' => fake()->uuid(),
                'entity_type' => fake()->randomElement(['post', 'comment', 'workspace']),
            ] : null,
            'action_url' => fake()->boolean(70) ? fake()->url() : null,
            'icon' => null,
            'read_at' => null,
            'sent_at' => fake()->boolean(90) ? now() : null,
            'failed_at' => null,
            'failure_reason' => null,
        ];
    }

    /**
     * Set notification as read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes): array => [
            'read_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Set notification as unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes): array => [
            'read_at' => null,
        ]);
    }

    /**
     * Set notification as sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'sent_at' => now(),
            'failed_at' => null,
        ]);
    }

    /**
     * Set notification as pending (not yet sent).
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'sent_at' => null,
            'failed_at' => null,
        ]);
    }

    /**
     * Set notification as failed.
     */
    public function failed(?string $reason = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'sent_at' => null,
            'failed_at' => now(),
            'failure_reason' => $reason ?? 'Delivery failed',
        ]);
    }

    /**
     * Set the notification type.
     */
    public function ofType(NotificationType $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => $type,
        ]);
    }

    /**
     * Set the notification channel.
     */
    public function viaChannel(NotificationChannel $channel): static
    {
        return $this->state(fn (array $attributes): array => [
            'channel' => $channel,
        ]);
    }

    /**
     * Set channel to in-app.
     */
    public function inApp(): static
    {
        return $this->viaChannel(NotificationChannel::IN_APP);
    }

    /**
     * Set channel to email.
     */
    public function email(): static
    {
        return $this->viaChannel(NotificationChannel::EMAIL);
    }

    /**
     * Set channel to push.
     */
    public function push(): static
    {
        return $this->viaChannel(NotificationChannel::PUSH);
    }

    /**
     * Set channel to SMS.
     */
    public function sms(): static
    {
        return $this->viaChannel(NotificationChannel::SMS);
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
     * Create a post-related notification.
     */
    public function postPublished(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => NotificationType::POST_PUBLISHED,
            'title' => 'Post Published Successfully',
            'message' => 'Your post has been published to the selected platforms.',
            'icon' => 'globe-alt',
        ]);
    }

    /**
     * Create a post rejected notification.
     */
    public function postRejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => NotificationType::POST_REJECTED,
            'title' => 'Post Rejected',
            'message' => 'Your post has been rejected and requires changes.',
            'icon' => 'x-circle',
        ]);
    }

    /**
     * Create a payment failed notification.
     */
    public function paymentFailed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => NotificationType::PAYMENT_FAILED,
            'title' => 'Payment Failed',
            'message' => 'Your payment could not be processed. Please update your payment method.',
            'icon' => 'exclamation-circle',
        ]);
    }

    /**
     * Create an invitation received notification.
     */
    public function invitationReceived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => NotificationType::INVITATION_RECEIVED,
            'title' => 'Invitation Received',
            'message' => 'You have been invited to join a workspace.',
            'icon' => 'envelope',
        ]);
    }

    /**
     * Create a notification with specific data.
     *
     * @param array<string, mixed> $data
     */
    public function withData(array $data): static
    {
        return $this->state(fn (array $attributes): array => [
            'data' => $data,
        ]);
    }

    /**
     * Create an old notification (for cleanup testing).
     */
    public function old(int $days = 100): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_at' => now()->subDays($days),
            'updated_at' => now()->subDays($days),
        ]);
    }

    /**
     * Create a recent notification.
     */
    public function recent(int $days = 7): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_at' => now()->subDays(fake()->numberBetween(1, $days)),
        ]);
    }
}
