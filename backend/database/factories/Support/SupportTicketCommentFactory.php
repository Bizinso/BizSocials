<?php

declare(strict_types=1);

namespace Database\Factories\Support;

use App\Enums\Support\SupportCommentType;
use App\Models\Platform\SuperAdminUser;
use App\Models\Support\SupportTicket;
use App\Models\Support\SupportTicketComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for SupportTicketComment model.
 *
 * @extends Factory<SupportTicketComment>
 */
final class SupportTicketCommentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<SupportTicketComment>
     */
    protected $model = SupportTicketComment::class;

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
            'author_name' => fake()->name(),
            'author_email' => fake()->email(),
            'content' => fake()->paragraphs(2, true),
            'comment_type' => SupportCommentType::REPLY,
            'is_internal' => false,
            'metadata' => null,
        ];
    }

    /**
     * Set as a reply.
     */
    public function reply(): static
    {
        return $this->state(fn (array $attributes): array => [
            'comment_type' => SupportCommentType::REPLY,
            'is_internal' => false,
        ]);
    }

    /**
     * Set as an internal note.
     */
    public function note(): static
    {
        return $this->state(fn (array $attributes): array => [
            'comment_type' => SupportCommentType::NOTE,
            'is_internal' => true,
        ]);
    }

    /**
     * Set as a status change comment.
     */
    public function statusChange(): static
    {
        return $this->state(fn (array $attributes): array => [
            'comment_type' => SupportCommentType::STATUS_CHANGE,
            'is_internal' => false,
            'content' => 'Status changed from Open to In Progress',
        ]);
    }

    /**
     * Set as an assignment comment.
     */
    public function assignment(): static
    {
        return $this->state(fn (array $attributes): array => [
            'comment_type' => SupportCommentType::ASSIGNMENT,
            'is_internal' => true,
            'content' => 'Ticket assigned to support team',
        ]);
    }

    /**
     * Set as a system comment.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes): array => [
            'comment_type' => SupportCommentType::SYSTEM,
            'is_internal' => false,
            'author_name' => 'System',
            'author_email' => null,
        ]);
    }

    /**
     * Set as internal.
     */
    public function internal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_internal' => true,
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
     * Set as from a user.
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
            'admin_id' => null,
            'author_name' => $user->name,
            'author_email' => $user->email,
        ]);
    }

    /**
     * Set as from an admin.
     */
    public function byAdmin(SuperAdminUser $admin): static
    {
        return $this->state(fn (array $attributes): array => [
            'admin_id' => $admin->id,
            'user_id' => null,
            'author_name' => $admin->name,
            'author_email' => $admin->email,
        ]);
    }
}
