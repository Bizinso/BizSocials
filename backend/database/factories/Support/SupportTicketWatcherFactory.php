<?php

declare(strict_types=1);

namespace Database\Factories\Support;

use App\Models\Platform\SuperAdminUser;
use App\Models\Support\SupportTicket;
use App\Models\Support\SupportTicketWatcher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for SupportTicketWatcher model.
 *
 * @extends Factory<SupportTicketWatcher>
 */
final class SupportTicketWatcherFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<SupportTicketWatcher>
     */
    protected $model = SupportTicketWatcher::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => SupportTicket::factory(),
            'user_id' => null,
            'admin_id' => null,
            'email' => fake()->email(),
            'notify_on_reply' => true,
            'notify_on_status_change' => true,
            'notify_on_assignment' => false,
        ];
    }

    /**
     * Set as a user watcher.
     */
    public function asUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
            'admin_id' => null,
            'email' => $user->email,
        ]);
    }

    /**
     * Set as an admin watcher.
     */
    public function asAdmin(SuperAdminUser $admin): static
    {
        return $this->state(fn (array $attributes): array => [
            'admin_id' => $admin->id,
            'user_id' => null,
            'email' => $admin->email,
        ]);
    }

    /**
     * Set for a specific ticket.
     */
    public function forTicket(SupportTicket $ticket): static
    {
        return $this->state(fn (array $attributes): array => [
            'ticket_id' => $ticket->id,
        ]);
    }

    /**
     * Enable all notifications.
     */
    public function allNotifications(): static
    {
        return $this->state(fn (array $attributes): array => [
            'notify_on_reply' => true,
            'notify_on_status_change' => true,
            'notify_on_assignment' => true,
        ]);
    }

    /**
     * Disable all notifications.
     */
    public function noNotifications(): static
    {
        return $this->state(fn (array $attributes): array => [
            'notify_on_reply' => false,
            'notify_on_status_change' => false,
            'notify_on_assignment' => false,
        ]);
    }

    /**
     * Enable only reply notifications.
     */
    public function replyOnly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'notify_on_reply' => true,
            'notify_on_status_change' => false,
            'notify_on_assignment' => false,
        ]);
    }
}
