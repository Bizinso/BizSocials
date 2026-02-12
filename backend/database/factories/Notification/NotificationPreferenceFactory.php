<?php

declare(strict_types=1);

namespace Database\Factories\Notification;

use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationType;
use App\Models\Notification\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for NotificationPreference model.
 *
 * @extends Factory<NotificationPreference>
 */
final class NotificationPreferenceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<NotificationPreference>
     */
    protected $model = NotificationPreference::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'notification_type' => fake()->randomElement(NotificationType::cases()),
            'in_app_enabled' => true,
            'email_enabled' => true,
            'push_enabled' => false,
            'sms_enabled' => false,
        ];
    }

    /**
     * Associate with a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Set the notification type.
     */
    public function ofType(NotificationType $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'notification_type' => $type,
        ]);
    }

    /**
     * Enable all channels.
     */
    public function allEnabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'in_app_enabled' => true,
            'email_enabled' => true,
            'push_enabled' => true,
            'sms_enabled' => true,
        ]);
    }

    /**
     * Disable all channels.
     */
    public function allDisabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'in_app_enabled' => false,
            'email_enabled' => false,
            'push_enabled' => false,
            'sms_enabled' => false,
        ]);
    }

    /**
     * Enable in-app notifications.
     */
    public function inAppEnabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'in_app_enabled' => true,
        ]);
    }

    /**
     * Disable in-app notifications.
     */
    public function inAppDisabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'in_app_enabled' => false,
        ]);
    }

    /**
     * Enable email notifications.
     */
    public function emailEnabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_enabled' => true,
        ]);
    }

    /**
     * Disable email notifications.
     */
    public function emailDisabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_enabled' => false,
        ]);
    }

    /**
     * Enable push notifications.
     */
    public function pushEnabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'push_enabled' => true,
        ]);
    }

    /**
     * Disable push notifications.
     */
    public function pushDisabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'push_enabled' => false,
        ]);
    }

    /**
     * Enable SMS notifications.
     */
    public function smsEnabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'sms_enabled' => true,
        ]);
    }

    /**
     * Disable SMS notifications.
     */
    public function smsDisabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'sms_enabled' => false,
        ]);
    }

    /**
     * Set only in-app enabled (most restrictive).
     */
    public function inAppOnly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'in_app_enabled' => true,
            'email_enabled' => false,
            'push_enabled' => false,
            'sms_enabled' => false,
        ]);
    }

    /**
     * Set only email enabled.
     */
    public function emailOnly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'in_app_enabled' => false,
            'email_enabled' => true,
            'push_enabled' => false,
            'sms_enabled' => false,
        ]);
    }

    /**
     * Set default preferences (in-app and email enabled).
     */
    public function defaults(): static
    {
        return $this->state(fn (array $attributes): array => [
            'in_app_enabled' => NotificationChannel::IN_APP->isEnabledByDefault(),
            'email_enabled' => NotificationChannel::EMAIL->isEnabledByDefault(),
            'push_enabled' => NotificationChannel::PUSH->isEnabledByDefault(),
            'sms_enabled' => NotificationChannel::SMS->isEnabledByDefault(),
        ]);
    }

    /**
     * Create preferences for post notifications.
     */
    public function forPostNotifications(): static
    {
        return $this->state(fn (array $attributes): array => [
            'notification_type' => fake()->randomElement([
                NotificationType::POST_SUBMITTED,
                NotificationType::POST_APPROVED,
                NotificationType::POST_REJECTED,
                NotificationType::POST_PUBLISHED,
            ]),
        ]);
    }

    /**
     * Create preferences for billing notifications.
     */
    public function forBillingNotifications(): static
    {
        return $this->state(fn (array $attributes): array => [
            'notification_type' => fake()->randomElement([
                NotificationType::SUBSCRIPTION_CREATED,
                NotificationType::PAYMENT_FAILED,
                NotificationType::TRIAL_ENDING,
            ]),
        ]);
    }

    /**
     * Create preferences for team notifications.
     */
    public function forTeamNotifications(): static
    {
        return $this->state(fn (array $attributes): array => [
            'notification_type' => fake()->randomElement([
                NotificationType::INVITATION_RECEIVED,
                NotificationType::MEMBER_ADDED,
                NotificationType::ROLE_CHANGED,
            ]),
        ]);
    }
}
