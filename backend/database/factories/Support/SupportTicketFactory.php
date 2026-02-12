<?php

declare(strict_types=1);

namespace Database\Factories\Support;

use App\Enums\Support\SupportChannel;
use App\Enums\Support\SupportTicketPriority;
use App\Enums\Support\SupportTicketStatus;
use App\Enums\Support\SupportTicketType;
use App\Models\Platform\SuperAdminUser;
use App\Models\Support\SupportCategory;
use App\Models\Support\SupportTicket;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for SupportTicket model.
 *
 * @extends Factory<SupportTicket>
 */
final class SupportTicketFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<SupportTicket>
     */
    protected $model = SupportTicket::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_number' => 'TKT-' . strtoupper(fake()->unique()->lexify('??????')),
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'requester_email' => fake()->email(),
            'requester_name' => fake()->name(),
            'category_id' => null,
            'subject' => fake()->sentence(6),
            'description' => fake()->paragraphs(2, true),
            'ticket_type' => SupportTicketType::QUESTION,
            'priority' => SupportTicketPriority::MEDIUM,
            'status' => SupportTicketStatus::NEW,
            'channel' => SupportChannel::WEB_FORM,
            'assigned_to' => null,
            'assigned_team_id' => null,
            'first_response_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
            'last_activity_at' => now(),
            'sla_due_at' => now()->addHours(24),
            'is_sla_breached' => false,
            'comment_count' => 0,
            'attachment_count' => 0,
            'custom_fields' => null,
            'browser_info' => null,
            'page_url' => fake()->url(),
        ];
    }

    /**
     * Set status to new.
     */
    public function newStatus(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SupportTicketStatus::NEW,
            'assigned_to' => null,
        ]);
    }

    /**
     * Set status to open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SupportTicketStatus::OPEN,
        ]);
    }

    /**
     * Set status to in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SupportTicketStatus::IN_PROGRESS,
        ]);
    }

    /**
     * Set status to waiting on customer.
     */
    public function waitingCustomer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SupportTicketStatus::WAITING_CUSTOMER,
        ]);
    }

    /**
     * Set status to waiting internal.
     */
    public function waitingInternal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SupportTicketStatus::WAITING_INTERNAL,
        ]);
    }

    /**
     * Set status to resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SupportTicketStatus::RESOLVED,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Set status to closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SupportTicketStatus::CLOSED,
            'resolved_at' => now()->subHour(),
            'closed_at' => now(),
        ]);
    }

    /**
     * Set status to reopened.
     */
    public function reopened(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SupportTicketStatus::REOPENED,
        ]);
    }

    /**
     * Set priority to low.
     */
    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => SupportTicketPriority::LOW,
            'sla_due_at' => now()->addHours(72),
        ]);
    }

    /**
     * Set priority to medium.
     */
    public function mediumPriority(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => SupportTicketPriority::MEDIUM,
            'sla_due_at' => now()->addHours(24),
        ]);
    }

    /**
     * Set priority to high.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => SupportTicketPriority::HIGH,
            'sla_due_at' => now()->addHours(8),
        ]);
    }

    /**
     * Set priority to urgent.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => SupportTicketPriority::URGENT,
            'sla_due_at' => now()->addHours(4),
        ]);
    }

    /**
     * Set the ticket type.
     */
    public function ofType(SupportTicketType $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'ticket_type' => $type,
        ]);
    }

    /**
     * Set the channel.
     */
    public function fromChannel(SupportChannel $channel): static
    {
        return $this->state(fn (array $attributes): array => [
            'channel' => $channel,
        ]);
    }

    /**
     * Set for a specific tenant.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes): array => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Set for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
            'requester_email' => $user->email,
            'requester_name' => $user->name,
        ]);
    }

    /**
     * Set for a specific category.
     */
    public function inCategory(SupportCategory $category): static
    {
        return $this->state(fn (array $attributes): array => [
            'category_id' => $category->id,
        ]);
    }

    /**
     * Assign to an admin.
     */
    public function assignedTo(SuperAdminUser $admin): static
    {
        return $this->state(fn (array $attributes): array => [
            'assigned_to' => $admin->id,
            'status' => SupportTicketStatus::OPEN,
        ]);
    }

    /**
     * Set as overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes): array => [
            'sla_due_at' => now()->subHours(2),
            'is_sla_breached' => true,
        ]);
    }
}
