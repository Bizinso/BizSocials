<?php

declare(strict_types=1);

namespace Database\Factories\Support;

use App\Enums\Support\SupportAttachmentType;
use App\Models\Support\SupportTicket;
use App\Models\Support\SupportTicketAttachment;
use App\Models\Support\SupportTicketComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for SupportTicketAttachment model.
 *
 * @extends Factory<SupportTicketAttachment>
 */
final class SupportTicketAttachmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<SupportTicketAttachment>
     */
    protected $model = SupportTicketAttachment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $extension = fake()->randomElement(['jpg', 'png', 'pdf', 'doc']);
        $filename = fake()->uuid() . '.' . $extension;
        $originalFilename = fake()->words(2, true) . '.' . $extension;

        return [
            'ticket_id' => SupportTicket::factory(),
            'comment_id' => null,
            'filename' => $filename,
            'original_filename' => $originalFilename,
            'file_path' => 'support/attachments/' . $filename,
            'mime_type' => $this->getMimeType($extension),
            'attachment_type' => $this->getAttachmentType($extension),
            'file_size' => fake()->numberBetween(1024, 5242880), // 1KB - 5MB
            'uploaded_by' => null,
            'is_inline' => false,
        ];
    }

    /**
     * Get the MIME type for an extension.
     */
    private function getMimeType(string $extension): string
    {
        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'zip' => 'application/zip',
            default => 'application/octet-stream',
        };
    }

    /**
     * Get the attachment type for an extension.
     */
    private function getAttachmentType(string $extension): SupportAttachmentType
    {
        return match ($extension) {
            'jpg', 'jpeg', 'png', 'gif', 'webp' => SupportAttachmentType::IMAGE,
            'pdf', 'doc', 'docx', 'xls', 'xlsx' => SupportAttachmentType::DOCUMENT,
            'mp4', 'mov', 'avi' => SupportAttachmentType::VIDEO,
            'zip', 'rar', '7z' => SupportAttachmentType::ARCHIVE,
            default => SupportAttachmentType::OTHER,
        };
    }

    /**
     * Set as an image.
     */
    public function image(): static
    {
        $filename = fake()->uuid() . '.png';

        return $this->state(fn (array $attributes): array => [
            'filename' => $filename,
            'original_filename' => fake()->words(2, true) . '.png',
            'file_path' => 'support/attachments/' . $filename,
            'mime_type' => 'image/png',
            'attachment_type' => SupportAttachmentType::IMAGE,
        ]);
    }

    /**
     * Set as a document.
     */
    public function document(): static
    {
        $filename = fake()->uuid() . '.pdf';

        return $this->state(fn (array $attributes): array => [
            'filename' => $filename,
            'original_filename' => fake()->words(2, true) . '.pdf',
            'file_path' => 'support/attachments/' . $filename,
            'mime_type' => 'application/pdf',
            'attachment_type' => SupportAttachmentType::DOCUMENT,
        ]);
    }

    /**
     * Set as inline.
     */
    public function inline(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_inline' => true,
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
     * Set for a specific comment.
     */
    public function forComment(SupportTicketComment $comment): static
    {
        return $this->state(fn (array $attributes): array => [
            'comment_id' => $comment->id,
            'ticket_id' => $comment->ticket_id,
        ]);
    }

    /**
     * Set the uploader.
     */
    public function uploadedBy(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'uploaded_by' => $user->id,
        ]);
    }
}
